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
		?>
		<div class="wrap">
			<h1>ProWPKit Forms Settings</h1>
			
			<?php $this->render_tabs( $active_tab ); ?>

			<form method="post" action="options.php">
				<?php 
				if ( $active_tab === 'email' ) {
					settings_fields( 'pwp_settings_email' );
					do_settings_sections( 'pwp_settings_email' );
				} else {
					settings_fields( 'pwp_settings_general' );
					do_settings_sections( 'pwp_settings_general' );
				}
				
				submit_button(); 
				?>
			</form>
		</div>
		<?php
	}
}
