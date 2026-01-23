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

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( [ 'message' => 'Invalid form.' ] );
		}

		// Fetch Custom Messages
		$msg_spam = get_post_meta( $form_id, '_pwp_msg_spam', true ) ?: 'Spam detected. Access denied.';
		$msg_validation = get_post_meta( $form_id, '_pwp_msg_validation_error', true ) ?: 'One or more fields have an error. Please check and try again.';
		$msg_invalid_email = get_post_meta( $form_id, '_pwp_msg_invalid_email', true ) ?: 'Please enter a valid email address.';
		$msg_upload_fail = get_post_meta( $form_id, '_pwp_msg_upload_failed', true ) ?: 'There was an unknown error uploading the file.';


		// Honeypot Check (Late check to use custom message)
		if ( ! empty( $_POST['pwp_hp_check'] ) ) {
			wp_send_json_error( [ 'message' => $msg_spam ] );
		}

		// RATE LIMITING: Check IP-based submission rate (prevents spam/abuse)
		$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$rate_limit_check = $this->check_rate_limit( $user_ip );
		if ( is_wp_error( $rate_limit_check ) ) {
			wp_send_json_error( [ 'message' => $rate_limit_check->get_error_message() ] );
		}

		// 2.5 Captcha Check
		$captcha_provider = get_option( 'pwp_captcha_provider', 'none' );
		if ( $captcha_provider === 'turnstile' ) {
			// ... (Turnstile logic unchanged) ... 
			$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';
			$secret = get_option( 'pwp_turnstile_secret_key', '' );
			if ( empty( $token ) ) wp_send_json_error( [ 'message' => $msg_spam ] ); // Unified spam msg or specifics? Stick to spam msg for user simplicity.
			
			$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
				'body' => [ 'secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ]
			] );
			if ( is_wp_error( $response ) ) wp_send_json_error( [ 'message' => $msg_spam ] );
			
			$body = wp_remote_retrieve_body( $response );
			$result = json_decode( $body, true );
			if ( ! $result || ! $result['success'] ) wp_send_json_error( [ 'message' => $msg_spam ] );

		} elseif ( $captcha_provider === 'recaptcha' ) {
			$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';
			$secret = get_option( 'pwp_recaptcha_secret_key', '' );

			if ( empty( $token ) ) {
				wp_send_json_error( [ 'message' => $msg_spam ] );
			}

			// Verify with Google
			$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
				'body' => [
					'secret' => $secret,
					'response' => $token,
					'remoteip' => $_SERVER['REMOTE_ADDR']
				]
			] );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( [ 'message' => $msg_spam ] );
			}

			$body = wp_remote_retrieve_body( $response );
			$result = json_decode( $body, true );

			if ( ! $result || ! $result['success'] ) {
				wp_send_json_error( [ 'message' => $msg_spam ] );
			}
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
			if ( ! empty( $_FILES ) && ! empty( $uploads_enabled ) ) { 
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
			// SMART DETECTION: Scan for email field if 'email' key doesn't exist directly
			$detected_email = '';
			
			// 1. Check direct 'email' or 'your-email' (standard CF7/PWP style)
			if ( ! empty( $submission_data['email'] ) ) {
				$detected_email = $submission_data['email'];
			} elseif ( ! empty( $submission_data['your-email'] ) ) {
				$detected_email = $submission_data['your-email'];
			} else {
				// 2. Scan keys for "email" string
				foreach ( $submission_data as $key => $val ) {
					if ( stripos( $key, 'email' ) !== false && is_email( $val ) ) {
						$detected_email = $val;
						break;
					}
				}
			}

			// 3. Validate
			$user_email = sanitize_email( $detected_email );
			if ( ! is_email( $user_email ) ) {
				// If email is mandatory for "Mail 2" (User Notification), this might be critical.
				// But valid submission might not ALWAYS require email? 
				// Current logic was: if ( ! is_email( $user_email ) ) error...
				// Let's keep strict check if simple 'email' field was expected, 
				// but since we now scan, if we found NOTHING, maybe we prompt?
				// For now, adhere to previous strictness: if we can't find a valid email, error.
				wp_send_json_error( [ 'message' => $msg_invalid_email ] );
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
					// Append error detail or just show generic?
					// Use custom upload fail message, maybe append detail
					wp_send_json_error( [ 'message' => $msg_upload_fail . ' (' . $upload_result->get_error_message() . ')' ] );
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
			PWP_Email_Manager::send_notifications( $submission_id, $form_id, $submission_data, $uploaded_files, $user_email );
		}

		// 8. Return Success
		$msg_success = get_post_meta( $form_id, '_pwp_msg_success', true ) ?: 'Thank you for your message. It has been sent.';
		wp_send_json_success( [ 'message' => $msg_success ] );
	}

	/**
	 * Check IP-based rate limiting
	 * Prevents spam by limiting submissions per IP address
	 * 
	 * @param string $ip IP address to check
	 * @return bool|WP_Error True if allowed, WP_Error if rate limit exceeded
	 */
	private function check_rate_limit( $ip ) {
		// Skip check for empty IP (shouldn't happen, but defensive)
		if ( empty( $ip ) ) {
			return true;
		}

		// Sanitize IP for use in transient key
		$ip_hash = md5( $ip );
		$transient_key = 'pwp_rate_limit_' . $ip_hash;
		
		// Get current submission count for this IP
		$submission_count = get_transient( $transient_key );
		
		// Get max submissions per hour (filterable for customization)
		$max_submissions = apply_filters( 'pwp_max_submissions_per_hour', 10 );
		
		if ( $submission_count === false ) {
			// First submission from this IP in the last hour
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
			return true;
		} else {
			// Check if limit exceeded
			if ( $submission_count >= $max_submissions ) {
				return new WP_Error( 
					'rate_limit_exceeded',
					sprintf( 
						'Too many submissions. Please wait before submitting again. (Limit: %d per hour)',
						$max_submissions
					)
				);
			}
			
			// Increment counter
			set_transient( $transient_key, $submission_count + 1, HOUR_IN_SECONDS );
			return true;
		}
	}
}
