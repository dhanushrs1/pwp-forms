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
	/**
	 * Send Notifications
	 *
	 * @param int $submission_id
	 * @param int $form_id
	 * @param array $data
	 * @param array $files Paths
	 */
	public static function send_notifications( $submission_id, $form_id, $data, $files = [] ) {
		// --- 1. MAIL 1 (Admin) SETUP ---
		$to          = get_post_meta( $form_id, '_pwp_mail_to', true ) ?: get_option( 'admin_email' );
		$from_header = get_post_meta( $form_id, '_pwp_mail_from', true ) ?: get_bloginfo( 'name' ) . ' <wordpress@' . $_SERVER['HTTP_HOST'] . '>';
		$subject_tmpl= get_post_meta( $form_id, '_pwp_mail_subject', true ) ?: 'New Submission: [your-subject]';
		$headers_tmpl= get_post_meta( $form_id, '_pwp_mail_headers', true ) ?: '';
		$body_tmpl   = get_post_meta( $form_id, '_pwp_mail_body', true ) ?: '[_all_fields]';
		
		// Parse Admin Tags
		$subject = self::parse_mail_tags( $subject_tmpl, $data, $form_id );
		$body_raw = self::parse_mail_tags( $body_tmpl, $data, $form_id );
		
		// BRANDING: Apply Styled Template
		$body_styled = self::get_styled_email_html( $subject, nl2br( $body_raw ) );

		// Construct Headers
		$from_parsed = self::parse_mail_tags( $from_header, $data, $form_id );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$headers[] = 'From: ' . $from_parsed;

		if ( ! empty( $headers_tmpl ) ) {
			$headers_parsed = self::parse_mail_tags( $headers_tmpl, $data, $form_id );
			$lines = explode( "\n", $headers_parsed );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( ! empty( $line ) ) {
					$headers[] = $line;
				}
			}
		}

		// --- SEND MAIL 1 ---
		$recipients = explode( ',', self::parse_mail_tags( $to, $data, $form_id ) );
		foreach ( $recipients as $recipient ) {
			wp_mail( trim( $recipient ), $subject, $body_styled, $headers, $files );
		}
		
		// --- 2. MAIL 2 (User Confirmation) CHECK ---
		$mail_2_active = get_post_meta( $form_id, '_pwp_mail_2_active', true );
		
		if ( ! empty( $mail_2_active ) ) {
			$to_2_tmpl = get_post_meta( $form_id, '_pwp_mail_2_to', true );
			$subject_2_tmpl = get_post_meta( $form_id, '_pwp_mail_2_subject', true );
			$body_2_tmpl = get_post_meta( $form_id, '_pwp_mail_2_body', true );

			// Parse User Tags
			$to_2 = self::parse_mail_tags( $to_2_tmpl, $data, $form_id );
			$subject_2 = self::parse_mail_tags( $subject_2_tmpl, $data, $form_id );
			$body_2_raw = self::parse_mail_tags( $body_2_tmpl, $data, $form_id );

			// BRANDING: Apply Styled Template to User Email
			$body_2_styled = self::get_styled_email_html( $subject_2, nl2br( $body_2_raw ) );

			// Headers for Mail 2 (From Site Name)
			$headers_2 = [ 'Content-Type: text/html; charset=UTF-8' ];
			// Uses the Site Name <admin@site.com> format usually, or the "From" configured in Mail 1
			$headers_2[] = 'From: ' . $from_parsed; 

			// --- SEND MAIL 2 ---
			wp_mail( $to_2, $subject_2, $body_2_styled, $headers_2 );
		}
	}

	/**
	 * Parse Mail Tags
	 * Replaces [field] with data value.
	 */
	public static function parse_mail_tags( $content, $data, $form_id ) {
		// Special Tags
		$replacements = [
			'[_site_title]'       => get_bloginfo( 'name' ),
			'[_site_url]'         => home_url(),
			'[_site_admin_email]' => get_option( 'admin_email' ),
			'[_date]'             => date_i18n( get_option( 'date_format' ) ),
			'[_time]'             => date_i18n( get_option( 'time_format' ) ),
			'[_remote_ip]'        => $_SERVER['REMOTE_ADDR'],
			'[_url]'              => get_permalink( $form_id ), // or referer
		];
		
		// Field Tags
		foreach ( $data as $key => $val ) {
			if ( is_array( $val ) ) {
				$val = implode( ', ', $val );
			}
			$replacements["[$key]"] = $val;
		}

		// [_all_fields] Helper
		if ( strpos( $content, '[_all_fields]' ) !== false ) {
			$table = "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse:collapse; width:100%;'>";
			foreach ( $data as $key => $val ) {
				$label = ucfirst( str_replace( '_', ' ', $key ) );
				$v = is_array( $val ) ? implode( ', ', $val ) : esc_html( $val );
				$table .= "<tr><td style='background:#f9f9f9; width:30%;'><strong>$label</strong></td><td>$v</td></tr>";
			}
			$table .= "</table>";
			$replacements['[_all_fields]'] = $table;
		}

		return strtr( $content, $replacements );
	}

    /**
     * Generate Styled Email HTML Wrapper
     */
    public static function get_styled_email_html( $title, $content ) {
        // Get Settings
        $logo_url = get_option( 'pwp_email_logo', '' );
        $bg_color = get_option( 'pwp_email_bg_color', '#f4f4f4' );
        $container_bg = get_option( 'pwp_email_container_bg', '#ffffff' );
        $text_color = get_option( 'pwp_email_text_color', '#333333' );
        $accent_color = get_option( 'pwp_email_accent_color', '#0073aa' );
        $font_family = get_option( 'pwp_email_font_family', 'Helvetica, Arial, sans-serif' );
        $font_size = get_option( 'pwp_email_font_size', '16' );
        $footer_text = get_option( 'pwp_email_footer', 'Powered by <a href="https://prowpkit.com/">ProWPKit</a>' );

        // Replace Line Breaks in Footer
        $footer_html = nl2br( esc_html( $footer_text ) );
        
        // Logo Block
        $logo_html = '';
        if ( ! empty( $logo_url ) ) {
            $logo_html = '<div style="text-align:center; padding-bottom:20px;">
                <img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-width:200px; height:auto; border:0;">
            </div>';
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $title ); ?></title>
</head>
<body style="margin:0; padding:0; background-color:<?php echo esc_attr( $bg_color ); ?>; font-family:<?php echo esc_attr( $font_family ); ?>;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:<?php echo esc_attr( $bg_color ); ?>;">
        <tr>
            <td align="center" style="padding:40px 10px;">
                <?php echo $logo_html; ?>
                <!-- Main Card -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:<?php echo esc_attr( $container_bg ); ?>; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.05); max-width:600px; width:100%;">
                    <tr>
                        <td align="left" style="padding:40px; color:<?php echo esc_attr( $text_color ); ?>; font-size:<?php echo esc_attr( $font_size ); ?>px; line-height:1.6; font-family:<?php echo esc_attr( $font_family ); ?>;">
                            <?php echo $content; ?>
                        </td>
                    </tr>
                </table>
                <!-- Footer -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%;">
                    <tr>
                        <td align="center" style="padding:20px; color:#888888; font-size:12px;">
                            <?php echo $footer_html; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}
