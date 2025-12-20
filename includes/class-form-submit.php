<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Form_Submit {

	public function __construct() {
		add_action( 'wp_ajax_pwp_submit_form', [ $this, 'handle_submission' ] );
		add_action( 'wp_ajax_nopriv_pwp_submit_form', [ $this, 'handle_submission' ] );
	}

	/**
	 * Handle AJAX Submission
	 */
	public function handle_submission() {
		// 1. Nonce Check
		if ( ! check_ajax_referer( 'pwp_form_submit', 'security', false ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed.' ] );
		}

		// 2. Honeypot Check
		if ( ! empty( $_POST['pwp_hp_check'] ) ) {
			wp_send_json_error( [ 'message' => 'Spam detected.' ] );
		}

		// 2.5 Captcha Check
		$captcha_provider = get_option( 'pwp_captcha_provider', 'none' );
		if ( $captcha_provider === 'turnstile' ) {
			$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';
			$secret = get_option( 'pwp_turnstile_secret_key', '' );
			
			if ( empty( $token ) ) {
				wp_send_json_error( [ 'message' => 'Captcha verification failed (missing token).' ] );
			}

			// Verify with Cloudflare
			$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
				'body' => [
					'secret' => $secret,
					'response' => $token,
					'remoteip' => $_SERVER['REMOTE_ADDR']
				]
			] );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( [ 'message' => 'Captcha verification error.' ] );
			}

			$body = wp_remote_retrieve_body( $response );
			$result = json_decode( $body, true );

			if ( ! $result || ! $result['success'] ) {
				wp_send_json_error( [ 'message' => 'Captcha verification failed.' ] );
			}
		}

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( [ 'message' => 'Invalid form.' ] );
		}

		// 3. Get Form Settings & Capability Check
		$access_mode = get_post_meta( $form_id, '_pwp_form_access_mode', true ) ?: 'guest_allowed';
		$form_type = get_post_meta( $form_id, '_pwp_form_type', true ) ?: 'general';
		$uploads_enabled = get_post_meta( $form_id, '_pwp_form_uploads_enabled', true );
		$is_logged_in = is_user_logged_in();

		// SERVER-SIDE CAPABILITY ENFORCEMENT
		if ( ! $is_logged_in ) {
			// If Guest trying to access Logged-in Only form
			if ( $access_mode === 'loggedin_only' ) {
				wp_send_json_error( [ 'message' => 'You must be logged in to submit this form.' ] );
			}
			// If Guest trying to submit Support Ticket
			if ( $form_type === 'support' ) {
				wp_send_json_error( [ 'message' => 'Support tickets require a user account.' ] );
			}
			// If Guest trying to upload files (unless we add specific exception later, rule says guests blocked)
			if ( ! empty( $_FILES ) && ! empty( $uploads_enabled ) ) { // If uploads enabled generally, but user is guest -> Block? 
				// The prompt says: "Guest Users ... Guests MUST NOT be allowed to ... Upload files (unless explicitly allowed with strict limits)"
				// And later: "Upload Permission: Guest ❌ Disabled".
				// So we STRICTLY block guest uploads.
				wp_send_json_error( [ 'message' => 'Guests are not allowed to upload files.' ] );
			}
		}

		// 4. Data Sanitization & Identity Handling
		$submission_data = [];
		$user_id = null;
		$user_email = '';
		
		// Parse all POST fields excluding known system fields
		$exclude = [ 'action', 'security', 'form_id', 'pwp_hp_check', 'cf-turnstile-response', 'g-recaptcha-response' ];
		
		foreach ( $_POST as $key => $value ) {
			if ( ! in_array( $key, $exclude ) ) {
				if ( is_array( $value ) ) {
					$submission_data[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$submission_data[ $key ] = sanitize_textarea_field( $value );
				}
			}
		}

		if ( $is_logged_in ) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
			// FORCE Identity from Session (Ignore POST spoofing)
			$user_email = $current_user->user_email;
			$submission_data['name'] = $current_user->display_name; // Ensure these are set correctly
			$submission_data['email'] = $current_user->user_email;
		} else {
			// Guest: Trust POST data (sanitized)
			$user_email = isset( $submission_data['email'] ) ? sanitize_email( $submission_data['email'] ) : '';
			if ( ! is_email( $user_email ) ) {
				wp_send_json_error( [ 'message' => 'Invalid email address.' ] );
			}
		}

		// 5. Handle File Uploads
		$uploaded_files = [];
		if ( ! empty( $_FILES ) && $uploads_enabled && $is_logged_in ) {
			// We need `PWP_Upload_Handler` to implement `handle_upload`.
			// Since we haven't implemented it fully yet, we'll assume a static method exists or instantiate.
			// Ideally we catch exceptions.
			if ( class_exists( 'PWP_Upload_Handler' ) ) {
				$upload_result = PWP_Upload_Handler::handle_uploads( $_FILES, $form_id );
				if ( is_wp_error( $upload_result ) ) {
					wp_send_json_error( [ 'message' => $upload_result->get_error_message() ] );
				}
				$uploaded_files = $upload_result;
			}
		}

		// 6. Save to Database
		global $wpdb;
		$table_name = $wpdb->prefix . 'pwp_submissions';
		
		$insert_data = [
			'form_id'         => $form_id,
			'user_id'         => $user_id,
			'user_email'      => $user_email,
			'submission_type' => $form_type,
			'submission_data' => json_encode( $submission_data ),
			'uploaded_files'  => json_encode( $uploaded_files ),
			'user_ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
			'status'          => 'new'
		];

		$inserted = $wpdb->insert( $table_name, $insert_data );

		if ( ! $inserted ) {
			wp_send_json_error( [ 'message' => 'Database error. Could not save submission.' ] );
		}
		
		$submission_id = $wpdb->insert_id;

		// 7. Trigger Emails
		if ( class_exists( 'PWP_Email_Manager' ) ) {
			PWP_Email_Manager::send_notifications( $submission_id, $form_id, $submission_data, $uploaded_files );
		}

		// 8. Return Success
		$success_msg = get_post_meta( $form_id, '_pwp_form_success_message', true ) ?: 'Thank you for your submission.';
		wp_send_json_success( [ 'message' => $success_msg ] );
	}
}
