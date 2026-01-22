<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Form_Render {

	public function __construct() {
		add_shortcode( 'pwp_form', [ $this, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		
		// AJAX endpoints for cache-safe dynamic data
		add_action( 'wp_ajax_pwp_get_form_nonce', [ $this, 'ajax_get_nonce' ] );
		add_action( 'wp_ajax_nopriv_pwp_get_form_nonce', [ $this, 'ajax_get_nonce' ] );
		add_action( 'wp_ajax_pwp_get_user_data', [ $this, 'ajax_get_user_data' ] );
		add_action( 'wp_ajax_nopriv_pwp_get_user_data', [ $this, 'ajax_get_user_data' ] );
	}

	/**
	 * Enqueue Frontend Assets
	 */
	public function enqueue_assets() {
		// Only enqueue if shortcode is present? Or globally? 
		// For simplicity, enqueue globally or check post content.
		// Registering here, specific enqueue inside shortcode is tricky if header already sent.
		// So we enqueue globally but small file.
		wp_register_script( 'pwp-forms-js', PWP_FORMS_URL . 'public/js/pwp-forms.js', [ 'jquery' ], PWP_FORMS_VERSION, true );
		wp_register_style( 'pwp-forms-css', PWP_FORMS_URL . 'public/css/pwp-forms.css', [], PWP_FORMS_VERSION );
	}

	/**
	 * Render Shortcode [pwp_form id="123"]
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts );

		$form_id = intval( $atts['id'] );

		if ( ! $form_id || get_post_type( $form_id ) !== 'pwp_form' ) {
			return '';
		}

		// Enqueue assets when shortcode is used
		wp_enqueue_script( 'pwp-forms-js' );
		wp_enqueue_style( 'pwp-forms-css' );
		// SECURITY FIX: Nonce removed to prevent caching issues
		// JavaScript will fetch a fresh nonce via AJAX to prevent expiry on cached pages
		wp_localize_script( 'pwp-forms-js', 'pwp_forms_vars', [
			'ajax_url' => admin_url( 'admin-ajax.php' )
		]);

		return $this->generate_form_html( $form_id );
	}

	/**
	 * Generate Form HTML with Logic
	 */
	private function generate_form_html( $form_id ) {
		$access_mode = get_post_meta( $form_id, '_pwp_form_access_mode', true ) ?: 'guest_allowed';
		$form_type = get_post_meta( $form_id, '_pwp_form_type', true ) ?: 'general';
		$is_user_logged_in = is_user_logged_in();

		// 1. Check Access Restriction
		if ( ! $is_user_logged_in ) {
			// Strict Rules:
			// If Access Mode is Logged-in Only -> BLOCK
			// If Form Type is Support -> BLOCK
			if ( $access_mode === 'loggedin_only' || $form_type === 'support' ) {
				$login_url = wp_login_url( get_permalink() );
				return '<div class="pwp-notice pwp-restricted">
					<p>For security reasons, please <a href="' . esc_url( $login_url ) . '">log in</a> to submit this form.</p>
				</div>';
			}
		}

		// 2. Get Form Content
		$form_html = get_post_meta( $form_id, '_pwp_form_html', true );
		$uploads_enabled = get_post_meta( $form_id, '_pwp_form_uploads_enabled', true );

		// 2.5 Force Remove File Inputs if Disabled
		if ( empty( $uploads_enabled ) ) {
			$form_html = preg_replace( '/<input[^>]*type=[\"\\\']file[\"\\\'][^>]*>/i', '', $form_html );
		}

		// 3. Guest Upload Logic (Locking)
		if ( ! empty( $uploads_enabled ) && ! $is_user_logged_in ) {
			// Simple Regex Replace for file input
			$form_html = preg_replace_callback( '/<input[^>]*type=[\"\\\']file[\"\\\'][^>]*>/i', function( $matches ) {
				// Return a locked wrapper
				// Add disabled attribute
				$input_disabled = str_replace( '<input', '<input disabled ', $matches[0] );
				return '<div class="pwp-upload-locked-wrapper" title="Log in to upload files">
					' . $input_disabled . '
					<div class="pwp-lock-overlay"><span class="dashicons dashicons-lock"></span> Log in to Upload</div>
				</div>';
			}, $form_html );
		}

		// 3. SECURITY FIX: User identity handling moved to JavaScript
		// Pre-filling user data server-side causes PII leaks on cached pages
		// JavaScript will populate name/email fields dynamically after page load
		// No server-side modification of form HTML for logged-in users

		// 4. Wrap & Output
		// We add a honeypot field hidden
		$honeypot = '<div style="display:none;"><input type="text" name="pwp_hp_check" value=""></div>';
		
		// 4.5 Auto-Append Submit Button Logic
		// Always append unless configured not to (optional, but requested as mandatory)
		$submit_label = get_post_meta( $form_id, '_pwp_form_submit_label', true ) ?: 'Send Message';
		
		// Wrapper with class for easy styling
		$form_html .= '<div class="pwp-submit-wrapper" style="margin-top:20px;">
			<button type="submit" class="pwp-submit">' . esc_html( $submit_label ) . '</button>
		</div>';
		// Note: removed check for existing button to ENFORCE the new system.
		// If user had one, they might have 2 now. We should strip old ones or warn?
		// For now, simpler to append. User can remove old one from HTML editor.

		// Captcha
		$captcha_provider = get_option( 'pwp_captcha_provider', 'none' );
		$site_key = get_option( 'pwp_turnstile_site_key', '' );
		$captcha_html = '';

		if ( $captcha_provider === 'turnstile' && ! empty( $site_key ) ) {
			wp_enqueue_script( 'pwp-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true );
			$captcha_html = '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '" style="margin-top:10px; margin-bottom:10px;"></div>';
		} elseif ( $captcha_provider === 'recaptcha' ) {
			$recaptcha_site_key = get_option( 'pwp_recaptcha_site_key', '' );
			if ( ! empty( $recaptcha_site_key ) ) {
				wp_enqueue_script( 'pwp-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true );
				$captcha_html = '<div class="g-recaptcha" data-sitekey="' . esc_attr( $recaptcha_site_key ) . '" style="margin-top:10px; margin-bottom:10px;"></div>';
			}
		}
		
		ob_start();
		?>
		<form id="pwp-form-<?php echo esc_attr( $form_id ); ?>" class="pwp-form" data-id="<?php echo esc_attr( $form_id ); ?>" method="post" enctype="multipart/form-data">
			<?php echo $form_html; ?>
			<?php echo $captcha_html; ?>
			<?php echo $honeypot; ?>
			<div class="pwp-response-message" style="display:none; margin-top:10px;"></div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX: Get Fresh Nonce
	 * Prevents cached pages from having expired nonces
	 */
	public function ajax_get_nonce() {
		wp_send_json_success( [
			'nonce' => wp_create_nonce( 'pwp_form_submit' )
		] );
	}

	/**
	 * AJAX: Get User Data
	 * Prevents cached pages from exposing PII
	 */
	public function ajax_get_user_data() {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			wp_send_json_success( [
				'logged_in' => true,
				'name'      => $current_user->display_name,
				'email'     => $current_user->user_email
			] );
		} else {
			wp_send_json_success( [
				'logged_in' => false
			] );
		}
	}
}
