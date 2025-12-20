<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Form_Manager {

	public function __construct() {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ] );
		add_filter( 'manage_pwp_form_posts_columns', [ $this, 'add_shortcode_column' ] );
		add_action( 'manage_pwp_form_posts_custom_column', [ $this, 'render_shortcode_column' ], 10, 2 );
	}

	/**
	 * Register Custom Post Type
	 */
	public function register_cpt() {
		$labels = [
			'name'               => 'ProWPKit Forms',
			'singular_name'      => 'Form',
			'menu_name'          => 'Pro Forms',
			'add_new'            => 'Add New Form',
			'add_new_item'       => 'Add New Form',
			'edit_item'          => 'Edit Form',
			'new_item'           => 'New Form',
			'view_item'          => 'View Form',
			'search_items'       => 'Search Forms',
			'not_found'          => 'No forms found',
			'not_found_in_trash' => 'No forms found in Trash',
		];

		$args = [
			'labels'              => $labels,
			'public'              => false, // Not public on frontend directly
			'show_ui'             => true,
			'show_in_menu'        => true,
			'supports'            => [ 'title' ],
			'menu_icon'           => 'dashicons-feedback',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
		];

		register_post_type( 'pwp_form', $args );
	}

	/**
	 * Add Shortcode Column
	 */
	public function add_shortcode_column( $columns ) {
		$new_columns = [];
		foreach( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( $key === 'title' ) {
				$new_columns['shortcode'] = 'Shortcode';
			}
		}
		return $new_columns;
	}

	/**
	 * Render Shortcode Column
	 */
	public function render_shortcode_column( $column, $post_id ) {
		if ( $column === 'shortcode' ) {
			echo '<code style="user-select:all;">[pwp_form id="' . $post_id . '"]</code>';
		}
	}

	/**
	 * Add Meta Boxes
	 */
	public function add_meta_boxes() {
		// Shortcode Info
		add_meta_box(
			'pwp_form_shortcode_box',
			'Quick Shortcode',
			[ $this, 'render_shortcode_box' ],
			'pwp_form',
			'side',
			'high'
		);

		// HTML Content Editor
		add_meta_box(
			'pwp_form_html_box',
			'Form HTML (Standard HTML5)',
			[ $this, 'render_html_box' ],
			'pwp_form',
			'normal',
			'high'
		);

		// Configuration
		add_meta_box(
			'pwp_form_config_box',
			'Form Configuration & Security',
			[ $this, 'render_config_box' ],
			'pwp_form',
			'side',
			'default'
		);
		
		// Email Settings
		add_meta_box(
			'pwp_form_email_box',
			'Email Notification Settings',
			[ $this, 'render_email_box' ],
			'pwp_form',
			'normal',
			'default'
		);
	}

	/**
	 * Render Shortcode Box
	 */
	public function render_shortcode_box( $post ) {
		echo '<p>Copy this shortcode to display the form:</p>';
		echo '<code style="display:block; padding:10px; background:#f0f0f1; user-select:all; text-align:center;">[pwp_form id="' . $post->ID . '"]</code>';
	}

	/**
	 * Render HTML Box
	 */
	public function render_html_box( $post ) {
		$html = get_post_meta( $post->ID, '_pwp_form_html', true );
		?>
		<style>
			#pwp-toolbar-container {
				background: #fcfcfc;
				border: 1px solid #dcdcde;
				border-bottom: 0;
				padding: 15px;
			}
			.pwp-toolbar-section {
				margin-bottom: 15px;
			}
			.pwp-toolbar-section:last-child {
				margin-bottom: 0;
			}
			.pwp-section-title {
				font-size: 11px;
				text-transform: uppercase;
				color: #646970;
				font-weight: 600;
				margin-bottom: 8px;
				letter-spacing: 0.5px;
			}
			.pwp-btn-grid {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}
			.pwp-btn-insert {
				display: inline-flex;
				align-items: center;
				background: #fff;
				border: 1px solid #c3c4c7;
				color: #2c3338;
				padding: 6px 12px;
				font-size: 13px;
				border-radius: 4px;
				cursor: pointer;
				transition: all 0.2s ease;
				text-decoration: none;
			}
			.pwp-btn-insert:hover {
				background: #f6f7f7;
				border-color: #2271b1;
				color: #2271b1;
				transform: translateY(-1px);
				box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			}
			.pwp-btn-insert .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				margin-right: 6px;
				color: #50575e;
			}
			.pwp-btn-insert:hover .dashicons {
				color: #2271b1;
			}
			.pwp-btn-insert.primary {
				background: #f0f6fc;
				border-color: #2271b1;
				color: #2271b1;
				font-weight: 500;
			}
			.pwp-btn-insert.primary:hover {
				background: #2271b1;
				color: white;
			}
			.pwp-btn-insert.primary:hover .dashicons {
				color: white;
			}
		</style>

		<div id="pwp-toolbar-container">
			
			<!-- Essentials -->
			<div class="pwp-toolbar-section">
				<div class="pwp-section-title">Essentials</div>
				<div class="pwp-btn-grid">
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Name</label>\n<input type=\'text\' name=\'name\' class=\'pwp-input\' required>\n')">
						<span class="dashicons dashicons-admin-users"></span> Name
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Email Address</label>\n<input type=\'email\' name=\'email\' class=\'pwp-input\' required>\n')">
						<span class="dashicons dashicons-email"></span> Email
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Phone Number</label>\n<input type=\'tel\' name=\'phone\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-phone"></span> Phone
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Website URL</label>\n<input type=\'url\' name=\'website\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-admin-links"></span> URL
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Password</label>\n<input type=\'password\' name=\'password\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-lock"></span> Password
					</button>
				</div>
			</div>

			<!-- Input Types -->
			<div class="pwp-toolbar-section">
				<div class="pwp-section-title">Data Inputs</div>
				<div class="pwp-btn-grid">
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Number</label>\n<input type=\'number\' name=\'number\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-calculator"></span> Number
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Date</label>\n<input type=\'date\' name=\'date\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-calendar-alt"></span> Date
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Time</label>\n<input type=\'time\' name=\'time\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-clock"></span> Time
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Color Picker</label>\n<input type=\'color\' name=\'color\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-art"></span> Color
					</button>
				</div>
			</div>

			<!-- Choices -->
			<div class="pwp-toolbar-section">
				<div class="pwp-section-title">Choices & Options</div>
				<div class="pwp-btn-grid">
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Dropdown</label>\n<select name=\'choice\' class=\'pwp-input\'>\n  <option value=\'1\'>Option 1</option>\n  <option value=\'2\'>Option 2</option>\n</select>\n')">
						<span class="dashicons dashicons-arrow-down-alt2"></span> Select
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Multi Select</label>\n<select name=\'multi_choice[]\' class=\'pwp-input\' multiple>\n  <option value=\'1\'>Option 1</option>\n  <option value=\'2\'>Option 2</option>\n</select>\n')">
						<span class="dashicons dashicons-list-view"></span> Multi
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label><input type=\'radio\' name=\'radio_group\' value=\'1\'> Option 1</label>\n<label><input type=\'radio\' name=\'radio_group\' value=\'2\'> Option 2</label>\n')">
						<span class="dashicons dashicons-marker"></span> Radio
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label><input type=\'checkbox\' name=\'checkbox_1\' value=\'1\'> Check me</label>\n')">
						<span class="dashicons dashicons-yes"></span> Checkbox
					</button>
				</div>
			</div>

			<!-- Big Content -->
			<div class="pwp-toolbar-section">
				<div class="pwp-section-title">Large Content</div>
				<div class="pwp-btn-grid">
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Message</label>\n<textarea name=\'message\' class=\'pwp-textarea\' rows=\'4\'></textarea>\n')">
						<span class="dashicons dashicons-text-page"></span> Text Area
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<label>Upload File</label>\n<input type=\'file\' name=\'file\' class=\'pwp-input\'>\n')">
						<span class="dashicons dashicons-paperclip"></span> File Upload
					</button>
				</div>
			</div>

			<!-- Submit -->
			<div class="pwp-toolbar-section" style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
				<div class="pwp-btn-grid">
					<button type="button" class="pwp-btn-insert primary" onclick="pwpInsert('<button type=\'submit\' class=\'pwp-btn\'>Submit Form</button>\n')">
						<span class="dashicons dashicons-saved"></span> <strong>Insert Submit Button</strong>
					</button>
					<button type="button" class="pwp-btn-insert" onclick="pwpInsert('<input type=\'hidden\' name=\'hidden_field\' value=\'my_value\'>\n')">
						<span class="dashicons dashicons-hidden"></span> Hidden Field
					</button>
				</div>
			</div>

		</div>

		<textarea id="pwp_form_html" name="pwp_form_html" rows="20" style="width:100%; font-family:monospace; background:#fafafa; color:#333; border-top:0;"><?php echo esc_textarea( $html ); ?></textarea>

		<script>
		function pwpInsert(myValue) {
			var myField = document.getElementById('pwp_form_html');
			if (document.selection) {
				myField.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
			} else if (myField.selectionStart || myField.selectionStart == '0') {
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				myField.value = myField.value.substring(0, startPos)
					+ myValue
					+ myField.value.substring(endPos, myField.value.length);
			} else {
				myField.value += myValue;
			}
			myField.focus(); // Re-focus
		}
		</script>
		<?php
	}

	/**
	 * Render Config Box
	 */
	public function render_config_box( $post ) {
		$access_mode = get_post_meta( $post->ID, '_pwp_form_access_mode', true ) ?: 'guest_allowed';
		$form_type = get_post_meta( $post->ID, '_pwp_form_type', true ) ?: 'general';
		$uploads = get_post_meta( $post->ID, '_pwp_form_uploads_enabled', true );
		$success_msg = get_post_meta( $post->ID, '_pwp_form_success_message', true ) ?: 'Thank you for your submission.';
		$error_msg = get_post_meta( $post->ID, '_pwp_form_error_message', true ) ?: 'There was an error sending your message. Please try again.';

		wp_nonce_field( 'pwp_save_form', 'pwp_form_nonce' );
		?>
		<p><strong>Access Mode</strong></p>
		<select name="pwp_form_access_mode" style="width:100%;">
			<option value="guest_allowed" <?php selected( $access_mode, 'guest_allowed' ); ?>>Guest + Logged-in</option>
			<option value="loggedin_only" <?php selected( $access_mode, 'loggedin_only' ); ?>>Logged-in Users Only</option>
		</select>
		<p class="description">Use 'Logged-in Only' for sensitive actions.</p>

		<p><strong>Form Type</strong></p>
		<select name="pwp_form_type" style="width:100%;">
			<option value="general" <?php selected( $form_type, 'general' ); ?>>General Inquiry</option>
			<option value="support" <?php selected( $form_type, 'support' ); ?>>Account / Support Ticket</option>
		</select>
		<p class="description">Support tickets require Logged-in access.</p>

		<p><strong>File Uploads</strong></p>
		<label>
			<input type="checkbox" name="pwp_form_uploads_enabled" value="1" <?php checked( $uploads, '1' ); ?>> Enable File Uploads
		</label>
		<p class="description">Only allowed for Logged-in users (unless extended).</p>

		<hr>

		<p><strong>Success Message</strong></p>
		<textarea name="pwp_form_success_message" rows="3" style="width:100%;"><?php echo esc_textarea( $success_msg ); ?></textarea>

		<p><strong>Error Message</strong></p>
		<textarea name="pwp_form_error_message" rows="3" style="width:100%;"><?php echo esc_textarea( $error_msg ); ?></textarea>
		<?php
	}

	/**
	 * Render Email Box
	 */
	public function render_email_box( $post ) {
		$email_to = get_post_meta( $post->ID, '_pwp_email_to', true ) ?: get_option('admin_email');
		$subject = get_post_meta( $post->ID, '_pwp_email_subject', true ) ?: 'New Form Submission';
		?>
		<p><strong>Admin Email To</strong></p>
		<input type="text" name="pwp_email_to" value="<?php echo esc_attr( $email_to ); ?>" style="width:100%;">
		<p class="description">Comma separated for multiple.</p>

		<p><strong>Email Subject</strong></p>
		<input type="text" name="pwp_email_subject" value="<?php echo esc_attr( $subject ); ?>" style="width:100%;">
		<?php
	}

	/**
	 * Save Meta Boxes
	 */
	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['pwp_form_nonce'] ) || ! wp_verify_nonce( $_POST['pwp_form_nonce'], 'pwp_save_form' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Fields to save
		$fields = [
			'pwp_form_html',
			'pwp_form_access_mode',
			'pwp_form_type',
			'pwp_form_uploads_enabled',
			'pwp_form_success_message',
			'pwp_form_error_message',
			'pwp_email_to',
			'pwp_email_subject'
		];

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				// Special sanitization for HTML
				if ( $field === 'pwp_form_html' ) {
					// Allow raw HTML for admins (unfiltered_html) to prevent stripping complex tags/attributes
					if ( current_user_can( 'unfiltered_html' ) ) {
						update_post_meta( $post_id, '_' . $field, $_POST[ $field ] );
					} else {
						update_post_meta( $post_id, '_' . $field, wp_kses_post( $_POST[ $field ] ) );
					}
				} else {
					update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
				}
			} else {
				// Handle checkboxes/empty
				if ( $field === 'pwp_form_uploads_enabled' ) {
					delete_post_meta( $post_id, '_' . $field );
				}
			}
		}
	}
}
