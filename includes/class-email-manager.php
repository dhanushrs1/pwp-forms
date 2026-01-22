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
		// 1. Fetch Form Settings (Meta)
		$to          = get_post_meta( $form_id, '_pwp_mail_to', true ) ?: get_option( 'admin_email' );
		$from_header = get_post_meta( $form_id, '_pwp_mail_from', true ) ?: get_bloginfo( 'name' ) . ' <wordpress@' . $_SERVER['HTTP_HOST'] . '>';
		$subject_tmpl= get_post_meta( $form_id, '_pwp_mail_subject', true ) ?: 'New Submission: [your-subject]';
		$headers_tmpl= get_post_meta( $form_id, '_pwp_mail_headers', true ) ?: '';
		$body_tmpl   = get_post_meta( $form_id, '_pwp_mail_body', true ) ?: '[_all_fields]';
		$attachments_tmpl = get_post_meta( $form_id, '_pwp_mail_attachments', true ) ?: '';

		// 2. Parse Tags
		$subject = self::parse_mail_tags( $subject_tmpl, $data, $form_id );
		$body    = self::parse_mail_tags( $body_tmpl, $data, $form_id );
		$headers_parsed = self::parse_mail_tags( $headers_tmpl, $data, $form_id );
		$from_parsed    = self::parse_mail_tags( $from_header, $data, $form_id );
		
		// 3. Construct Headers
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$headers[] = 'From: ' . $from_parsed;

		if ( ! empty( $headers_parsed ) ) {
			$lines = explode( "\n", $headers_parsed );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( ! empty( $line ) ) {
					$headers[] = $line;
				}
			}
		}

		// 4. Attachments (Future: handle [file] tags mapping to actual paths)
		// For now simple pass-through of $files if user logic handled it, but here we just pass $files arg 
		// from submission if it was populated.
		
		// 5. Send Mail 1
		$recipients = explode( ',', self::parse_mail_tags( $to, $data, $form_id ) );
		foreach ( $recipients as $recipient ) {
			wp_mail( trim( $recipient ), $subject, nl2br( $body ), $headers, $files );
		}
		
		// Note: Mail 2 (User Confirmation) is typically a separate tab in CF7. 
		// For now, we will assume Mail 1 is the primary notification.
		// If the user wants an auto-responder, they would typically configure Mail 2. 
		// Since we didn't explicitly build "Mail 2" UI yet (just one Mail tab), we'll skip separate user email for now 
		// UNLESS we want to support the old "User Template" logic? 
		// The prompt said "move completely to that Individual A form". 
		// So we strictly follow the new "Mail" tab config. 
		// If the user sets "To: [your-email]", it acts as an autoresponder. 
		// Often people want BOTH admin notification AND user receipt. 
		// CF7 solves this with "Mail 2". 
		// For MVP of this refactor, we just send "Mail". 
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
