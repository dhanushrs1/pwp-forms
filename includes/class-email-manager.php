<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Email_Manager {

	/**
	 * Send Notifications
	 *
	 * @param int $submission_id
	 * @param int $form_id
	 * @param array $data
	 * @param array $files Paths
	 */
	public static function send_notifications( $submission_id, $form_id, $data, $files = [] ) {
		$admin_emails_str = get_post_meta( $form_id, '_pwp_email_to', true ) ?: get_option( 'admin_email' );
		$subject_tmpl = get_post_meta( $form_id, '_pwp_email_subject', true ) ?: 'New Form Submission';

		// Prepare Dynamic Data
		$site_name = get_bloginfo( 'name' );
		$form_title = get_the_title( $form_id );
		
		// 1. Prepare Data Replacements (for subject)
		$replacements = [
			'{site_name}' => $site_name,
			'{form_title}' => $form_title
		];
		foreach ( $data as $key => $val ) {
			if ( is_string( $val ) ) {
				$replacements["{{$key}}"] = $val;
			}
		}

		// Subject Replacement
		$subject = strtr( $subject_tmpl, $replacements );

		// 2. Build Data Table (The {body})
		$table_html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; width:100%;">';
		foreach ( $data as $key => $val ) {
			$label = ucfirst( str_replace( '_', ' ', $key ) );
			$value = is_array( $val ) ? implode( ', ', $val ) : nl2br( esc_html( $val ) );
			$table_html .= "<tr><td style='background:#f9f9f9; width:30%;'><strong>$label</strong></td><td>$value</td></tr>";
		}
		$table_html .= '</table>';
		$table_html .= '<p><small>Submission ID: ' . $submission_id . '</small></p>';

		// 3. Admin Email Construction
		$admin_template = get_option( 'pwp_email_template_admin', '' );
		if ( empty( $admin_template ) ) {
			// Default Fallback
			$admin_body = "<h1>New Submission</h1><p>You have received a new form submission:</p>{body}<p><small>$site_name</small></p>";
		} else {
			$admin_body = $admin_template;
		}

		// Replace Placeholders in Admin Body
		$admin_body = str_replace( '{body}', $table_html, $admin_body );
		$admin_body = str_replace( '{site_name}', $site_name, $admin_body );
		$admin_body = str_replace( '{form_title}', $form_title, $admin_body );
		// Simple logo placeholder if they typed {logo}, they should probably use <img> tag, but let's handle text replacement if needed.
		// For now, assuming user puts <img> in HTML directly.

		// Headers
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		
		// Custom From Header (MUST be from the site domain to avoid Spam)
		$from_name = get_option( 'pwp_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'pwp_from_email', get_option( 'admin_email' ) );
		
		// Force From header
		$headers[] = "From: $from_name <$from_email>";

		// A. Admin Email Logic
		// Reply-To should be the USER (Submitter) so admin can just hit reply
		$admin_headers = $headers;
		if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
			$admin_headers[] = 'Reply-To: ' . sanitize_email( $data['email'] );
		}

		// Send Admin Email
		$admin_emails = explode( ',', $admin_emails_str );
		foreach ( $admin_emails as $to ) {
			wp_mail( trim( $to ), $subject, $admin_body, $admin_headers, $files );
		}

		// B. User Confirmation Logic
		if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
			$user_template = get_option( 'pwp_email_template_user', '' );
			if ( empty( $user_template ) ) {
				$user_body_html = "<h1>Thank you!</h1><p>We have received your submission.</p>{body}<p>Best Regards,<br>$site_name</p>";
			} else {
				$user_body_html = $user_template;
			}

			// Replace Placeholders in User Body
			$user_body_html = str_replace( '{body}', $table_html, $user_body_html );
			$user_body_html = str_replace( '{site_name}', $site_name, $user_body_html );
			$user_body_html = str_replace( '{form_title}', $form_title, $user_body_html );

			$user_subject = "Confirmation: $subject";
			
			// User email headers:
			// From: Site Name <support@site.com> (Already in $headers)
			// Reply-To: Support Email (NOT the user themselves)
			$user_headers = $headers;
			$user_headers[] = "Reply-To: $from_email"; // If they reply, it goes to support

			wp_mail( $data['email'], $user_subject, $user_body_html, $user_headers );
		}
	}
}
