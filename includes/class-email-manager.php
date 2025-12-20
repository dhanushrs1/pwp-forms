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
            $admin_content = "<h1>New Submission</h1><p>You have received a new form submission:</p>{body}";
        } else {
            $admin_content = $admin_template;
        }

        // Replace Placeholders in Admin Body
        $admin_content = str_replace( '{body}', $table_html, $admin_content );
        $admin_content = str_replace( '{site_name}', $site_name, $admin_content );
        $admin_content = str_replace( '{form_title}', $form_title, $admin_content );
        
        // WRAP with Visual Template
        $admin_final_html = self::get_styled_email_html( "New Submission", $admin_content );

        // Headers
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        
        // Custom From Header
        $from_name = get_option( 'pwp_from_name', get_bloginfo( 'name' ) );
        $from_email = get_option( 'pwp_from_email', get_option( 'admin_email' ) );
        $headers[] = "From: $from_name <$from_email>";

        // Admin Headers (Reply-to: User)
        $admin_headers = $headers;
        if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
            $admin_headers[] = 'Reply-To: ' . sanitize_email( $data['email'] );
        }

        // Send Admin Email
        $admin_emails = explode( ',', $admin_emails_str );
        foreach ( $admin_emails as $to ) {
            wp_mail( trim( $to ), $subject, $admin_final_html, $admin_headers, $files );
        }

        // 4. Send User Confirmation
        if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
            $user_template = get_option( 'pwp_email_template_user', '' );
            if ( empty( $user_template ) ) {
                $user_content = "<h1>Thank you!</h1><p>We have received your submission.</p>{body}<p>Best Regards,<br>$site_name</p>";
            } else {
                $user_content = $user_template;
            }

            // Replace Placeholders
            $user_content = str_replace( '{body}', $table_html, $user_content );
            $user_content = str_replace( '{site_name}', $site_name, $user_content );
            $user_content = str_replace( '{form_title}', $form_title, $user_content );

            // WRAP with Visual Template
            $user_final_html = self::get_styled_email_html( "Submission Received", $user_content );

            $user_subject = "Confirmation: $subject";
            
            // User Headers (Reply-to: Support)
            $user_headers = $headers;
            $user_headers[] = "Reply-To: $from_email";

            wp_mail( $data['email'], $user_subject, $user_final_html, $user_headers );
        }
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
        $footer_text = get_option( 'pwp_email_footer', 'Powered by ProWPKit' );

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
