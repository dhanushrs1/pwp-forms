<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Settings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add Settings Page
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=pwp_form',
			'Global Settings',
			'Settings',
			'manage_options',
			'pwp-settings',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register Settings
	 */
	public function register_settings() {
		// --- General Tab Sections ---
		// Section: Captcha
		add_settings_section(
			'pwp_captcha_section',
			'Captcha Configuration',
			null,
			'pwp_settings_general' // Page slug: General
		);

		register_setting( 'pwp_settings_general', 'pwp_captcha_provider' );
		register_setting( 'pwp_settings_general', 'pwp_turnstile_site_key' );
		register_setting( 'pwp_settings_general', 'pwp_turnstile_secret_key' );

		add_settings_field(
			'pwp_captcha_provider',
			'Captcha Provider',
			[ $this, 'render_provider_field' ],
			'pwp_settings_general',
			'pwp_captcha_section'
		);

		add_settings_field(
			'pwp_turnstile_keys',
			'Cloudflare Turnstile Keys',
			[ $this, 'render_keys_field' ],
			'pwp_settings_general',
			'pwp_captcha_section'
		);

		// Section: Uploads
		add_settings_section(
			'pwp_upload_section',
			'Upload Settings',
			null,
			'pwp_settings_general'
		);

		register_setting( 'pwp_settings_general', 'pwp_max_upload_size' );

		add_settings_field(
			'pwp_max_upload_size',
			'Max Upload Size (MB)',
			[ $this, 'render_upload_size_field' ],
			'pwp_settings_general',
			'pwp_upload_section'
		);

		// --- Email Tab Sections ---
		// Section: Visual Formatting
		add_settings_section(
			'pwp_visual_section',
			'Visual Formatting',
			null,
			'pwp_settings_email'
		);

		register_setting( 'pwp_settings_email', 'pwp_email_logo' );
		register_setting( 'pwp_settings_email', 'pwp_email_bg_color' );
		register_setting( 'pwp_settings_email', 'pwp_email_container_bg' );
		register_setting( 'pwp_settings_email', 'pwp_email_text_color' );
		register_setting( 'pwp_settings_email', 'pwp_email_accent_color' );
		register_setting( 'pwp_settings_email', 'pwp_email_font_family' );
		register_setting( 'pwp_settings_email', 'pwp_email_font_size' );
		register_setting( 'pwp_settings_email', 'pwp_email_footer' );

		add_settings_field(
			'pwp_email_logo',
			'Logo URL',
			[ $this, 'render_logo_field' ],
			'pwp_settings_email',
			'pwp_visual_section'
		);

		add_settings_field(
			'pwp_email_styling',
			'Email Styling',
			[ $this, 'render_styling_fields' ],
			'pwp_settings_email',
			'pwp_visual_section'
		);

		add_settings_field(
			'pwp_email_footer',
			'Footer Text',
			[ $this, 'render_footer_field' ],
			'pwp_settings_email',
			'pwp_visual_section'
		);

		// Section: Email Templates
		add_settings_section(
			'pwp_email_section',
			'Email Notification Settings',
			null,
			'pwp_settings_email' // Page slug: Email
		);

		register_setting( 'pwp_settings_email', 'pwp_email_template_admin' );
		register_setting( 'pwp_settings_email', 'pwp_email_template_user' );
		register_setting( 'pwp_settings_email', 'pwp_from_name' );
		register_setting( 'pwp_settings_email', 'pwp_from_email' );

		add_settings_field(
			'pwp_sender_settings',
			'Sender Settings',
			[ $this, 'render_sender_settings_field' ],
			'pwp_settings_email',
			'pwp_email_section'
		);

		add_settings_field(
			'pwp_email_template_admin',
			'Admin Notification Template',
			[ $this, 'render_admin_template_field' ],
			'pwp_settings_email',
			'pwp_email_section'
		);

		add_settings_field(
			'pwp_email_template_user',
			'User Confirmation Template',
			[ $this, 'render_user_template_field' ],
			'pwp_settings_email',
			'pwp_email_section'
		);
	}

	/**
	 * Field Renderers (Unchanged)
	 */
	public function render_provider_field() {
		$provider = get_option( 'pwp_captcha_provider', 'none' );
		?>
		<select name="pwp_captcha_provider">
			<option value="none" <?php selected( $provider, 'none' ); ?>>None</option>
			<option value="turnstile" <?php selected( $provider, 'turnstile' ); ?>>Cloudflare Turnstile</option>
		</select>
		<p class="description">Select the Captcha provider to use on all forms.</p>
		<?php
	}

	public function render_keys_field() {
		$site_key = get_option( 'pwp_turnstile_site_key', '' );
		$secret_key = get_option( 'pwp_turnstile_secret_key', '' );
		?>
		<p>
			<strong>Site Key:</strong><br>
			<input type="text" name="pwp_turnstile_site_key" value="<?php echo esc_attr( $site_key ); ?>" style="width:400px;">
		</p>
		<p>
			<strong>Secret Key:</strong><br>
			<input type="password" name="pwp_turnstile_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" style="width:400px;">
		</p>
		<?php
	}

	public function render_upload_size_field() {
		$size = get_option( 'pwp_max_upload_size', '5' );
		?>
		<input type="number" name="pwp_max_upload_size" value="<?php echo esc_attr( $size ); ?>" min="1" step="1"> MB
		<p class="description">Set the maximum file size allowed for uploads.</p>
		<?php
	}

	public function render_sender_settings_field() {
		$from_name = get_option( 'pwp_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'pwp_from_email', get_option( 'admin_email' ) );
		?>
		<p>
			<strong>From Name:</strong><br>
			<input type="text" name="pwp_from_name" value="<?php echo esc_attr( $from_name ); ?>" style="width:400px;">
		</p>
		<p>
			<strong>From Email:</strong><br>
			<input type="email" name="pwp_from_email" value="<?php echo esc_attr( $from_email ); ?>" style="width:400px;">
		</p>
		<p class="description">Emails will appear to come from this Name and Email address. IMPORTANT: Use an email on your domain (e.g., support@<?php echo $_SERVER['HTTP_HOST']; ?>) to prevent Spam.</p>
		<?php
	}

	public function render_logo_field() {
		$logo = get_option( 'pwp_email_logo', '' );
		?>
		<input type="url" name="pwp_email_logo" value="<?php echo esc_attr( $logo ); ?>" style="width:400px;" placeholder="https://example.com/logo.png">
		<p class="description">Enter the full URL of your logo image. Ensure it is hosted on SSL (https).</p>
		<?php
	}

	public function render_styling_fields() {
		$bg_color = get_option( 'pwp_email_bg_color', '#f4f4f4' );
		$container_bg = get_option( 'pwp_email_container_bg', '#ffffff' );
		$text_color = get_option( 'pwp_email_text_color', '#333333' );
		$accent_color = get_option( 'pwp_email_accent_color', '#0073aa' );
		$font_family = get_option( 'pwp_email_font_family', 'Helvetica, Arial, sans-serif' );
		$font_size = get_option( 'pwp_email_font_size', '16' );
		?>
		<div style="display:flex; flex-wrap:wrap; gap:20px;">
			<div>
				<label>Background Color</label><br>
				<input type="color" name="pwp_email_bg_color" value="<?php echo esc_attr( $bg_color ); ?>">
			</div>
			<div>
				<label>Container BG</label><br>
				<input type="color" name="pwp_email_container_bg" value="<?php echo esc_attr( $container_bg ); ?>">
			</div>
			<div>
				<label>Text Color</label><br>
				<input type="color" name="pwp_email_text_color" value="<?php echo esc_attr( $text_color ); ?>">
			</div>
			<div>
				<label>Accent Color</label><br>
				<input type="color" name="pwp_email_accent_color" value="<?php echo esc_attr( $accent_color ); ?>">
			</div>
		</div>
		<div style="margin-top:10px; display:flex; gap:20px;">
			<div style="flex:1;">
				<label>Font Family Stack</label><br>
				<input type="text" name="pwp_email_font_family" value="<?php echo esc_attr( $font_family ); ?>" style="width:100%;" placeholder="e.g. Inter, Helvetica, sans-serif">
				<p class="description">Enter font names separated by commas. Defaults to system fonts if not found.</p>
			</div>
			<div style="width:100px;">
				<label>Font Size (px)</label><br>
				<input type="number" name="pwp_email_font_size" value="<?php echo esc_attr( $font_size ); ?>" style="width:100%;">
			</div>
		</div>
		<?php
	}

	public function render_footer_field() {
		$footer = get_option( 'pwp_email_footer', 'Powered by ProWPKit' );
		?>
		<textarea name="pwp_email_footer" rows="2" style="width:100%;"><?php echo esc_textarea( $footer ); ?></textarea>
		<p class="description">Text to verify at the bottom of the email.</p>
		<?php
	}

	public function render_admin_template_field() {
		$content = get_option( 'pwp_email_template_admin', "<h1>New Submission</h1>\n<p>You have received a new form submission:</p>\n{body}\n<p><small>{site_name}</small></p>" );
		?>
		<textarea name="pwp_email_template_admin" rows="10" style="width:100%; font-family:monospace;"><?php echo esc_textarea( $content ); ?></textarea>
		<p class="description">Allowed placeholders: <code>{body}</code> (The form data table), <code>{site_name}</code>, <code>{form_title}</code>, <code>{logo}</code> (Add your own img tag).</p>
		<?php
	}

	public function render_user_template_field() {
		$content = get_option( 'pwp_email_template_user', "<h1>Thank you!</h1>\n<p>We have received your submission.</p>\n{body}\n<p>Best Regards,<br>{site_name}</p>" );
		?>
		<textarea name="pwp_email_template_user" rows="10" style="width:100%; font-family:monospace;"><?php echo esc_textarea( $content ); ?></textarea>
		<p class="description">Allowed placeholders: <code>{body}</code>, <code>{site_name}</code>, <code>{form_title}</code>, <code>{logo}</code>.</p>
		<?php
	}

	/**
	 * Render Tab Nav
	 */
	private function render_tabs( $active_tab ) {
		$tabs = [
			'general' => 'General Settings',
			'email'   => 'Email Templates'
		];
		
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_id => $tab_name ) {
			$class = ( $active_tab == $tab_id ) ? ' nav-tab-active' : '';
			$url = admin_url( 'edit.php?post_type=pwp_form&page=pwp-settings&tab=' . $tab_id );
			echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $class . '">' . esc_html( $tab_name ) . '</a>';
		}
		echo '</h2>';
	}

	/**
	 * Render Page
	 */
	public function render_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		
		// Enqueue Local Assets for Settings Page
		wp_enqueue_style( 'pwp-admin-settings', plugins_url( '../public/css/pwp-admin-settings.css', __FILE__ ), [], '1.0.0' );
		wp_enqueue_script( 'pwp-admin-settings', plugins_url( '../public/js/pwp-admin-settings.js', __FILE__ ), [], '1.0.0', true );

		?>
		<div class="wrap">
			<h1>ProWPKit Forms Settings</h1>
			
			<?php $this->render_tabs( $active_tab ); ?>

			<form method="post" action="options.php">
				<?php 
				if ( $active_tab === 'email' ) {
					// 2-Column Layout for Email
					settings_fields( 'pwp_settings_email' );
					?>
					<div class="pwp-settings-wrapper">
						<div class="pwp-settings-panel">
							<?php do_settings_sections( 'pwp_settings_email' ); ?>
							<?php submit_button(); ?>
						</div>
						
						<div class="pwp-preview-panel">
							<div class="pwp-preview-header">
								<h3>Instant Preview</h3>
								<div class="pwp-preview-controls">
									<button id="pwp-preview-mode-admin" class="button active">Admin View</button>
									<button id="pwp-preview-mode-user" class="button">User View</button>
								</div>
							</div>
							<iframe id="pwp-email-preview-frame"></iframe>
						</div>
					</div>
					<?php
				} else {
					// Standard Layout for General
					settings_fields( 'pwp_settings_general' );
					do_settings_sections( 'pwp_settings_general' );
					submit_button(); 
				}
				?>
			</form>
		</div>
		<?php
	}
}
