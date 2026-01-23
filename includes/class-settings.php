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
		register_setting( 'pwp_settings_general', 'pwp_turnstile_site_key' );
		register_setting( 'pwp_settings_general', 'pwp_turnstile_secret_key' );
		register_setting( 'pwp_settings_general', 'pwp_recaptcha_site_key' );
		register_setting( 'pwp_settings_general', 'pwp_recaptcha_secret_key' );

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

		// Section: Email Templates (MOVED TO FORM EDITOR)
		// Keeping 'Visual Formatting' global for now as "Default Style"
		// But removing 'Email Notification Settings' (Templates) content.
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
				<input type="text" class="pwp-color-field" name="pwp_email_bg_color" value="<?php echo esc_attr( $bg_color ); ?>" data-default-color="#f4f4f4">
			</div>
			<div>
				<label>Container BG</label><br>
				<input type="text" class="pwp-color-field" name="pwp_email_container_bg" value="<?php echo esc_attr( $container_bg ); ?>" data-default-color="#ffffff">
			</div>
			<div>
				<label>Text Color</label><br>
				<input type="text" class="pwp-color-field" name="pwp_email_text_color" value="<?php echo esc_attr( $text_color ); ?>" data-default-color="#333333">
			</div>
			<div>
				<label>Accent Color</label><br>
				<input type="text" class="pwp-color-field" name="pwp_email_accent_color" value="<?php echo esc_attr( $accent_color ); ?>" data-default-color="#0073aa">
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
		$footer = get_option( 'pwp_email_footer', 'Powered by <a href="https://prowpkit.com/">ProWPKit</a>' );
		?>
		<textarea name="pwp_email_footer" rows="2" style="width:100%;"><?php echo esc_textarea( $footer ); ?></textarea>
		<p class="description">Text to verify at the bottom of the email.</p>
		<?php
	}

	public function render_admin_template_field() {
		$content = get_option( 'pwp_email_template_admin', "<h1>New Submission</h1>\n<p>You have received a new form submission:</p>\n{body}\n<p><small>{site_name}</small></p>" );
		$this->render_template_editor( 'Admin Notification', 'pwp_email_template_admin', $content, [ '{body}', '{site_name}', '{form_title}', '{logo}' ] );
	}

	public function render_user_template_field() {
		$content = get_option( 'pwp_email_template_user', "<h1>Thank you!</h1>\n<p>We have received your submission.</p>\n{body}\n<p>Best Regards,<br>{site_name}</p>" );
		$this->render_template_editor( 'User Confirmation', 'pwp_email_template_user', $content, [ '{body}', '{site_name}', '{form_title}', '{logo}' ] );
	}

	/**
	 * Render Template Editor Helper
	 */
	private function render_template_editor( $title, $field_name, $content, $placeholders ) {
		?>
		<div class="pwp-template-card">
			<div class="pwp-card-header">
				<h4><?php echo esc_html( $title ); ?></h4>
				<span class="pwp-badge">HTML Editor</span>
			</div>
			<div class="pwp-placeholder-toolbar">
				<span class="toolbar-label">Insert:</span>
				<?php foreach ( $placeholders as $placeholder ) : ?>
					<button type="button" class="button pwp-insert-var" data-target="<?php echo esc_attr( $field_name ); ?>" data-value="<?php echo esc_attr( $placeholder ); ?>">
						<?php echo esc_html( $placeholder ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="pwp-editor-wrapper">
				<textarea id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" rows="15" class="large-text code"><?php echo esc_textarea( $content ); ?></textarea>
			</div>
			<div class="pwp-card-footer">
				<p class="description">Use HTML to design your email.</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Custom Card Renderer for Templates
	 */
	private function render_custom_template_card( $title, $field_name, $content ) {
		$placeholders = [ '{body}', '{site_name}', '{form_title}', '{logo}' ];
		?>
		<div class="pwp-card pwp-template-card-wrapper">
			<div class="pwp-card-header">
				<h3><?php echo esc_html( $title ); ?></h3>
				<div class="pwp-header-actions">
					<button type="button" class="button pwp-preview-btn" data-target="<?php echo esc_attr( $field_name ); ?>">
						<span class="dashicons dashicons-visibility"></span> Preview
					</button>
				</div>
			</div>
			
			<div class="pwp-toolbar">
				<span class="toolbar-label">Insert:</span>
				<?php foreach ( $placeholders as $placeholder ) : ?>
					<button type="button" class="pwp-pill-btn pwp-insert-var" data-target="<?php echo esc_attr( $field_name ); ?>" data-value="<?php echo esc_attr( $placeholder ); ?>">
						<?php echo esc_html( $placeholder ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="pwp-editor-area">
				<textarea id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" rows="15" class="large-text code"><?php echo esc_textarea( $content ); ?></textarea>
			</div>
		</div>
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
		
		// --- ADDED: Enqueue WP Color Picker ---
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Enqueue Local Assets for Settings Page
		wp_enqueue_style( 'pwp-admin-settings', plugins_url( '../public/css/pwp-admin-settings.css', __FILE__ ), [], '1.0.1' );
		wp_enqueue_script( 'pwp-admin-settings', plugins_url( '../public/js/pwp-admin-settings.js', __FILE__ ), [ 'jquery', 'wp-color-picker' ], '1.0.1', true );

		?>
		<div class="wrap">
			<h1>PWP Forms Settings</h1>
			
			<?php $this->render_tabs( $active_tab ); ?>

			<form method="post" action="options.php">
				<?php 
				if ( $active_tab === 'email' ) {
					settings_fields( 'pwp_settings_email' );
					
					// Fetch Options
					$logo = get_option( 'pwp_email_logo', '' );
					$font_family = get_option( 'pwp_email_font_family', 'Helvetica, Arial, sans-serif' );
					$font_size = get_option( 'pwp_email_font_size', '16' );
					$bg_color = get_option( 'pwp_email_bg_color', '#f4f4f4' );
					$container_bg = get_option( 'pwp_email_container_bg', '#ffffff' );
					$text_color = get_option( 'pwp_email_text_color', '#333333' );
					$accent_color = get_option( 'pwp_email_accent_color', '#0073aa' );
					$footer = get_option( 'pwp_email_footer', 'Powered by <a href="https://prowpkit.com/">ProWPKit</a>' );
					
					$from_name = get_option( 'pwp_from_name', get_bloginfo( 'name' ) );
					$from_email = get_option( 'pwp_from_email', get_option( 'admin_email' ) );

					$admin_template = get_option( 'pwp_email_template_admin', "<h1>New Submission</h1>\n<p>You have received a new form submission:</p>\n{body}\n<p><small>{site_name}</small></p>" );
					$user_template = get_option( 'pwp_email_template_user', "<h1>Thank you!</h1>\n<p>We have received your submission.</p>\n{body}\n<p>Best Regards,<br>{site_name}</p>" );
					?>

					<div class="pwp-email-settings-container">
						
						<!-- Branding & Visuals -->
						<div class="pwp-card">
							<div class="pwp-card-header">
								<h3>Visual Styling</h3>
								<p class="description">Customize the look and feel of your emails.</p>
							</div>
							<div class="pwp-card-body">
								<div class="pwp-grid-col-2">
									<div class="pwp-form-group">
										<label>Logo URL</label>
										<input type="url" name="pwp_email_logo" value="<?php echo esc_attr( $logo ); ?>" placeholder="https://..." class="regular-text">
									</div>
									<div class="pwp-grid-col-2-inner">
										<div class="pwp-form-group">
											<label>Font Family</label>
											<input type="text" name="pwp_email_font_family" value="<?php echo esc_attr( $font_family ); ?>" class="regular-text">
										</div>
										<div class="pwp-form-group" style="max-width: 100px;">
											<label>Size (px)</label>
											<input type="number" name="pwp_email_font_size" value="<?php echo esc_attr( $font_size ); ?>" class="small-text">
										</div>
									</div>
								</div>

								<div class="pwp-color-palette-row">
									<div class="pwp-color-group">
										<label>Background</label>
										<input type="color" name="pwp_email_bg_color" value="<?php echo esc_attr( $bg_color ); ?>">
									</div>
									<div class="pwp-color-group">
										<label>Container</label>
										<input type="color" name="pwp_email_container_bg" value="<?php echo esc_attr( $container_bg ); ?>">
									</div>
									<div class="pwp-color-group">
										<label>Text</label>
										<input type="color" name="pwp_email_text_color" value="<?php echo esc_attr( $text_color ); ?>">
									</div>
									<div class="pwp-color-group">
										<label>Accent</label>
										<input type="color" name="pwp_email_accent_color" value="<?php echo esc_attr( $accent_color ); ?>">
									</div>
								</div>

								<div class="pwp-form-group" style="margin-top: 20px;">
									<label>Footer Text</label>
									<textarea name="pwp_email_footer" rows="2" class="large-text"><?php echo esc_textarea( $footer ); ?></textarea>
								</div>
							</div>
						</div>

						<!-- Global defaults note -->
						<div class="pwp-card">
							<div class="pwp-card-body">
								<p><em>Note: Individual Email Templates and Message settings are now configured directly within each Form's editor.</em></p>
							</div>
						</div>

						<!-- Save Bar -->
						<div class="pwp-save-bar">
							<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
						</div>

					</div>

					<!-- Preview Modal -->
					<div id="pwp-preview-modal" class="pwp-modal">
						<div class="pwp-modal-content">
							<div class="pwp-modal-header">
								<h3>Email Preview</h3>
								<button type="button" class="pwp-modal-close">&times;</button>
							</div>
							<div class="pwp-modal-body">
								<iframe id="pwp-email-preview-frame"></iframe>
							</div>
						</div>
					</div>

					<?php
				} else {
					// Standard Layout for General
					settings_fields( 'pwp_settings_general' );
					
					// Fetch Options
					$captcha_provider = get_option( 'pwp_captcha_provider', 'none' );
					$site_key = get_option( 'pwp_turnstile_site_key', '' );
					$secret_key = get_option( 'pwp_turnstile_secret_key', '' );
					$recaptcha_site_key = get_option( 'pwp_recaptcha_site_key', '' );
					$recaptcha_secret_key = get_option( 'pwp_recaptcha_secret_key', '' );
					$max_upload = get_option( 'pwp_max_upload_size', '5' );
					?>

					<div class="pwp-email-settings-container">
						
						<!-- Security Card -->
						<div class="pwp-card">
							<div class="pwp-card-header">
								<h3>Spam Protection</h3>
								<p class="description">Configure security measures to prevent spam submissions.</p>
							</div>
							<div class="pwp-card-body">
								<div class="pwp-form-group">
									<label>Captcha Provider</label>
									<select name="pwp_captcha_provider" id="pwp_captcha_provider" style="width: 100%; max-width: 300px;">
										<option value="none" <?php selected( $captcha_provider, 'none' ); ?>>None</option>
										<option value="turnstile" <?php selected( $captcha_provider, 'turnstile' ); ?>>Cloudflare Turnstile</option>
										<option value="recaptcha" <?php selected( $captcha_provider, 'recaptcha' ); ?>>Google reCAPTCHA v2</option>
									</select>
								</div>

								<div id="pwp-turnstile-settings" class="pwp-captcha-settings" style="<?php echo ( $captcha_provider === 'turnstile' ) ? '' : 'display:none;'; ?> margin-top: 20px; padding-top: 20px; border-top: 1px dashed #ddd;">
									<h4>Turnstile Settings</h4>
									<div class="pwp-form-group">
										<label>Site Key</label>
										<input type="text" name="pwp_turnstile_site_key" value="<?php echo esc_attr( $site_key ); ?>" class="regular-text" style="width: 100%;">
									</div>
									<div class="pwp-form-group">
										<label>Secret Key</label>
										<input type="password" name="pwp_turnstile_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" style="width: 100%;">
									</div>
								</div>

								<div id="pwp-recaptcha-settings" class="pwp-captcha-settings" style="<?php echo ( $captcha_provider === 'recaptcha' ) ? '' : 'display:none;'; ?> margin-top: 20px; padding-top: 20px; border-top: 1px dashed #ddd;">
									<h4>reCAPTCHA Settings</h4>
									<div class="pwp-form-group">
										<label>Site Key</label>
										<input type="text" name="pwp_recaptcha_site_key" value="<?php echo esc_attr( $recaptcha_site_key ); ?>" class="regular-text" style="width: 100%;">
									</div>
									<div class="pwp-form-group">
										<label>Secret Key</label>
										<input type="password" name="pwp_recaptcha_secret_key" value="<?php echo esc_attr( $recaptcha_secret_key ); ?>" class="regular-text" style="width: 100%;">
									</div>
								</div>
							</div>
						</div>

						<!-- Uploads Card -->
						<div class="pwp-card">
							<div class="pwp-card-header">
								<h3>File Uploads</h3>
								<p class="description">Manage restrictions for file attachments.</p>
							</div>
							<div class="pwp-card-body">
								<div class="pwp-form-group">
									<label>Max Upload Size (MB)</label>
									<input type="number" name="pwp_max_upload_size" value="<?php echo esc_attr( $max_upload ); ?>" min="1" step="1" class="small-text">
									<p class="description" style="margin-top: 5px;">Server limit: <?php echo esc_html( ini_get('upload_max_filesize') ); ?></p>
								</div>
							</div>
						</div>

						<!-- Save Bar -->
						<div class="pwp-save-bar">
							<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
						</div>

					</div>
					
					<script>
					jQuery(document).ready(function($){
						$('#pwp_captcha_provider').change(function(){
							$('.pwp-captcha-settings').slideUp();
							if($(this).val() === 'turnstile'){
								$('#pwp-turnstile-settings').slideDown();
							} else if($(this).val() === 'recaptcha'){
								$('#pwp-recaptcha-settings').slideDown();
							}
						});
					});
					</script>
					<?php
				}
				?>
			</form>
		</div>
		<?php
	}
}
