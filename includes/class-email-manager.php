<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Email_Manager {

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
				$headers[] = trim( $line );
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

			// Headers for Mail 2
            // Use the "From" address defined in Mail 1 (usually the site admin)
			$headers_2 = [ 'Content-Type: text/html; charset=UTF-8' ];
			$headers_2[] = 'From: ' . $from_parsed; 

			// --- SEND MAIL 2 ---
			wp_mail( $to_2, $subject_2, $body_2_styled, $headers_2 );
		}
	}

    public static function parse_mail_tags( $content, $data, $form_id ) {
        $replacements = [
			'[_site_title]'       => get_bloginfo( 'name' ),
			'[_site_url]'         => home_url(),
			'[_site_admin_email]' => get_option( 'admin_email' ),
            '[_url]'              => get_permalink( $form_id )
		];
		
		foreach ( $data as $key => $val ) {
			if ( is_array( $val ) ) $val = implode( ', ', $val );
			$replacements["[$key]"] = $val;
		}

        if ( strpos( $content, '[_all_fields]' ) !== false ) {
            $table = "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse:collapse; width:100%;'>";
			foreach ( $data as $key => $val ) {
				// Clean label
				$label = ucfirst( str_replace( '-', ' ', str_replace( '_', ' ', $key ) ) );
				$v = is_array( $val ) ? implode( ', ', $val ) : esc_html( $val );
				$table .= "<tr><td style='background:#f9f9f9; width:30%;'><strong>$label</strong></td><td>$v</td></tr>";
			}
			$table .= "</table>";
			$replacements['[_all_fields]'] = $table;
        }

		return strtr( $content, $replacements );
    }

    public static function get_styled_email_html( $title, $content ) {
        $bg_color       = get_option( 'pwp_email_bg_color', '#f4f4f4' );
        $container_bg   = get_option( 'pwp_email_container_bg', '#ffffff' );
        $text_color     = get_option( 'pwp_email_text_color', '#333333' );
        $accent_color   = get_option( 'pwp_email_accent_color', '#0073aa' );
        $font_family    = get_option( 'pwp_email_font_family', 'Helvetica, Arial, sans-serif' );
        $font_size      = get_option( 'pwp_email_font_size', '16' );
        $footer_text    = get_option( 'pwp_email_footer', '' );
        $logo_url       = get_option( 'pwp_email_logo', '' );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; background-color: <?php echo esc_attr($bg_color); ?>; font-family: <?php echo esc_attr($font_family); ?>; color: <?php echo esc_attr($text_color); ?>; }
                .email-wrapper { max-width: 600px; margin: 40px auto; background: <?php echo esc_attr($container_bg); ?>; padding: 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
                p { font-size: <?php echo esc_attr($font_size); ?>px; line-height: 1.6; margin-bottom: 20px; }
                a { color: <?php echo esc_attr($accent_color); ?>; text-decoration: none; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
            </style>
        </head>
        <body style="background-color:<?php echo esc_attr($bg_color); ?>;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="padding: 40px 0;">
                        <?php if ( $logo_url ) : ?>
                            <div style="text-align:center; padding-bottom:20px;">
                                <img src="<?php echo esc_url($logo_url); ?>" style="max-width:200px;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="email-wrapper">
                            <!-- Optional Title if needed inside wrapper, but Subject is usually strictly subject. 
                                 Let's allow content to dictate, or add title if plain. 
                                 User's JS preview showed h1 "Thanks for contacting us" inside wrapper.
                                 Here we just output content. 
                            -->
                            <?php echo $content; ?>
                        </div>

                        <?php if ( $footer_text ) : ?>
                            <div class="footer"><?php echo wp_kses_post($footer_text); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
