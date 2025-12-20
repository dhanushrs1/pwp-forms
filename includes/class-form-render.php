<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Form_Render {

	public function __construct() {
		add_shortcode( 'pwp_form', [ $this, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
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
		wp_localize_script( 'pwp-forms-js', 'pwp_forms_vars', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'pwp_form_submit' )
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

		// 3. User Identity Handling (Logged-in)
		if ( $is_user_logged_in ) {
			$current_user = wp_get_current_user();
			$user_name = $current_user->display_name;
			$user_email = $current_user->user_email;

			// Auto-fill and Lock Name Field
			// Regex looks for: name="name" (case insensitive) and adds/replaces value/readonly
			// NOTE: This is a simple regex replacement. For complex HTML parsing, DOMDocument is better.
			// But for developer-controlled HTML within this plugin, regex is faster/sane enough usually.
			
			// Replace Name Input
			$form_html = preg_replace_callback( '/<input[^>]*name=["\']name["\'][^>]*>/i', function( $matches ) use ( $user_name ) {
				$input = $matches[0];
				// Remove existing value/readonly if any
				$input = preg_replace( '/value=["\'][^"\']*["\']/', '', $input );
				$input = preg_replace( '/readonly/', '', $input );
				// Add new value and readonly
				return str_replace( '<input', '<input value="' . esc_attr( $user_name ) . '" readonly ', $input );
			}, $form_html );

			// Replace Email Input
			$form_html = preg_replace_callback( '/<input[^>]*name=["\']email["\'][^>]*>/i', function( $matches ) use ( $user_email ) {
				$input = $matches[0];
				$input = preg_replace( '/value=["\'][^"\']*["\']/', '', $input );
				$input = preg_replace( '/readonly/', '', $input );
				return str_replace( '<input', '<input value="' . esc_attr( $user_email ) . '" readonly ', $input );
			}, $form_html );
		}

		// 4. Wrap & Output
		// We add a honeypot field hidden
		$honeypot = '<div style="display:none;"><input type="text" name="pwp_hp_check" value=""></div>';
		
		// Captcha
		$captcha_provider = get_option( 'pwp_captcha_provider', 'none' );
		$site_key = get_option( 'pwp_turnstile_site_key', '' );
		$captcha_html = '';

		if ( $captcha_provider === 'turnstile' && ! empty( $site_key ) ) {
			wp_enqueue_script( 'pwp-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true );
			$captcha_html = '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '" style="margin-top:10px; margin-bottom:10px;"></div>';
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
}
