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
		// Main Editor Box (Tabs)
		add_meta_box(
			'pwp_form_editor_box',
			'Form Editor',
			[ $this, 'render_editor_box' ],
			'pwp_form',
			'normal',
			'high'
		);

		// Shortcode Info (Side)
		add_meta_box(
			'pwp_form_shortcode_box',
			'Shortcode',
			[ $this, 'render_shortcode_box' ],
			'pwp_form',
			'side',
			'high'
		);

		// Status / Save (Side) is handled by default Publish box
	}

	/**
	 * Render Shortcode Box
	 */
	public function render_shortcode_box( $post ) {
		echo '<p>Copy this shortcode to display the form:</p>';
		echo '<code style="display:block; padding:10px; background:#f0f0f1; user-select:all; text-align:center;">[pwp_form id="' . $post->ID . '"]</code>';
	}

	/**
	 * Render Main Editor Box with Tabs
	 */
	public function render_editor_box( $post ) {
		wp_nonce_field( 'pwp_save_form', 'pwp_form_nonce' );
		
		// Active Tab
		$active_tab = 'form'; // Default
		?>
		<div class="pwp-editor-tabs">
			<ul class="pwp-tab-nav">
				<li class="pwp-tab-item active" data-tab="form">Form</li>
				<li class="pwp-tab-item" data-tab="mail">Mail</li>
				<li class="pwp-tab-item" data-tab="messages">Messages</li>
				<li class="pwp-tab-item" data-tab="settings">Additional Settings</li>
			</ul>
			
			<!-- TAB: FORM -->
			<div class="pwp-tab-content active" id="pwp-tab-form">
				<?php $this->render_tab_form( $post ); ?>
			</div>

			<!-- TAB: MAIL -->
			<div class="pwp-tab-content" id="pwp-tab-mail">
				<?php $this->render_tab_mail( $post ); ?>
			</div>

			<!-- TAB: MESSAGES -->
			<div class="pwp-tab-content" id="pwp-tab-messages">
				<?php $this->render_tab_messages( $post ); ?>
			</div>

			<!-- TAB: SETTINGS -->
			<div class="pwp-tab-content" id="pwp-tab-settings">
				<?php $this->render_tab_settings( $post ); ?>
			</div>
		</div>

		<style>
			.pwp-editor-tabs { border: 1px solid #c3c4c7; background: #fff; }
			.pwp-tab-nav { list-style: none; margin: 0; padding: 0; display: flex; background: #f0f0f1; border-bottom: 1px solid #c3c4c7; }
			.pwp-tab-item { padding: 12px 20px; font-weight: 600; cursor: pointer; border-right: 1px solid #c3c4c7; color: #50575e; }
			.pwp-tab-item:hover { background: #f6f7f7; color: #1d2327; }
			.pwp-tab-item.active { background: #fff; border-bottom: 1px solid #fff; margin-bottom: -1px; color: #1d2327; }
			.pwp-tab-content { display: none; padding: 20px; }
			.pwp-tab-content.active { display: block; }
			.pwp-field-row { margin-bottom: 15px; }
			.pwp-field-row label { display: block; font-weight: 600; margin-bottom: 5px; }
			.pwp-field-row .description { margin-top: 5px; color: #646970; font-style: italic; }
			/* Toolbar Tweaks */
			#pwp-toolbar-container { border: 1px solid #dcdcde; border-bottom: 0; }
		</style>
		<script>
		jQuery(document).ready(function($){
			$('.pwp-tab-item').click(function(){
				var tab = $(this).data('tab');
				$('.pwp-tab-item').removeClass('active');
				$(this).addClass('active');
				$('.pwp-tab-content').removeClass('active');
				$('#pwp-tab-' + tab).addClass('active');
			});
		});
		</script>
		<?php
	}

	/**
	 * Render Tab: Form (Toolbar + Editor)
	 */
	public function render_tab_form( $post ) {
		$html = get_post_meta( $post->ID, '_pwp_form_html', true );
		$submit_label = get_post_meta( $post->ID, '_pwp_form_submit_label', true ) ?: 'Send Message';
		?>
		
		<!-- Submit Button Config -->
		<div class="pwp-config-bar" style="background:#fff; border:1px solid #c3c4c7; border-bottom:0; padding:10px 15px; display:flex; align-items:center; justify-content:space-between; background:#f6f7f7;">
			<div class="pwp-config-item">
				<label style="font-weight:600; margin-right:10px;">Submit Button Label:</label>
				<input type="text" name="pwp_form_submit_label" value="<?php echo esc_attr( $submit_label ); ?>" class="regular-text" placeholder="e.g. Send Message">
			</div>
			<div class="pwp-config-item">
				<span class="description">Button is automatically added to the bottom of the form.</span>
			</div>
		</div>

		<!-- Toolbar -->
		<div id="pwp-toolbar-wrap">
			<div id="pwp-toolbar-container">
				
				<!-- Group: Text Fields -->
				<div class="pwp-toolbar-group">
					<div class="pwp-group-title">Text Inputs</div>
					<div class="pwp-tags-list">
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Name</label>\n<input type=\'text\' name=\'your-name\' class=\'pwp-input\' required>\n</div>\n')"><span class="dashicons dashicons-admin-users"></span> Text</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Email</label>\n<input type=\'email\' name=\'your-email\' class=\'pwp-input\' required>\n</div>\n')"><span class="dashicons dashicons-email"></span> Email</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Phone</label>\n<input type=\'tel\' name=\'your-phone\' class=\'pwp-input\'>\n</div>\n')"><span class="dashicons dashicons-phone"></span> Tel</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>URL</label>\n<input type=\'url\' name=\'your-url\' class=\'pwp-input\'>\n</div>\n')"><span class="dashicons dashicons-admin-links"></span> URL</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Number</label>\n<input type=\'number\' name=\'your-number\' class=\'pwp-input\'>\n</div>\n')"><span class="dashicons dashicons-calculator"></span> Num</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Date</label>\n<input type=\'date\' name=\'your-date\' class=\'pwp-input\'>\n</div>\n')"><span class="dashicons dashicons-calendar-alt"></span> Date</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Message</label>\n<textarea name=\'your-message\' class=\'pwp-textarea\' rows=\'4\'></textarea>\n</div>\n')"><span class="dashicons dashicons-text-page"></span> TextArea</button>
					</div>
				</div>

				<!-- Group: Selection -->
				<div class="pwp-toolbar-group">
					<div class="pwp-group-title">Selection</div>
					<div class="pwp-tags-list">
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>Select</label>\n<select name=\'your-menu\' class=\'pwp-input\'>\n<option value=\'Option 1\'>Option 1</option>\n</select>\n</div>\n')"><span class="dashicons dashicons-arrow-down-alt2"></span> Dropdown</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label class=\'pwp-checkbox\'><input type=\'checkbox\' name=\'your-cb\' value=\'1\'> Option 1</label>\n</div>\n')"><span class="dashicons dashicons-yes"></span> Checkbox</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label class=\'pwp-radio\'><input type=\'radio\' name=\'your-radio\' value=\'1\'> Option 1</label>\n</div>\n')"><span class="dashicons dashicons-marker"></span> Radio</button>
					</div>
				</div>

				<!-- Group: Advanced -->
				<div class="pwp-toolbar-group">
					<div class="pwp-group-title">Advanced</div>
					<div class="pwp-tags-list">
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label>File</label>\n<input type=\'file\' name=\'your-file\' class=\'pwp-input\'>\n</div>\n')"><span class="dashicons dashicons-paperclip"></span> File</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<div class=\'pwp-field\'>\n<label class=\'pwp-acceptance\'><input type=\'checkbox\' name=\'your-consent\' value=\'1\'> I agree</label>\n</div>\n')"><span class="dashicons dashicons-thumbs-up"></span> Accept</button>
						<button type="button" class="button pwp-tag-btn" onclick="pwpInsert('<input type=\'hidden\' name=\'your-hidden\' value=\'value\'>\n')"><span class="dashicons dashicons-hidden"></span> Hidden</button>
					</div>
				</div>

			</div>
		</div>

		<textarea id="pwp_form_html" name="pwp_form_html" rows="20" style="width:100%; font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5; background:#282c34; color:#abb2bf; border:0; padding:15px; border-radius:0 0 4px 4px;" placeholder="<!-- Enter your HTML form fields here -->"><?php echo esc_textarea( $html ); ?></textarea>
		
		<style>
			#pwp-toolbar-wrap {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-bottom: 0; 
				border-top: 1px solid #e5e5e5;
				position: relative;
			}
			#pwp-toolbar-container {
				padding: 12px 15px;
				display: flex;
				flex-wrap: nowrap; /* Prevent wrapping for horizontal scroll */
				gap: 25px;
				overflow-x: auto; /* Enable Horizontal Scroll */
				white-space: nowrap;
				scrollbar-width: thin; /* Firefox */
				align-items: center;
			}
			/* Chrome/Safari Scrollbar styling */
			#pwp-toolbar-container::-webkit-scrollbar { height: 6px; }
			#pwp-toolbar-container::-webkit-scrollbar-track { background: #f0f0f1; border-radius: 3px; }
			#pwp-toolbar-container::-webkit-scrollbar-thumb { background: #c3c4c7; border-radius: 3px; }
			#pwp-toolbar-container::-webkit-scrollbar-thumb:hover { background: #a7aaad; }

			/* Enable mouse wheel horizontal scrolling via JS if needed, but flex-nowrap + overflow-x usually works natively with trackpad/shift+scroll */

			.pwp-toolbar-group {
				display: flex;
				flex-direction: column; /* Stack Title on top of buttons? Or side by side? User said "clean". Let's do Side-by-side inside group, but groups side-by-side */
				align-items: flex-start;
				gap: 5px;
				flex-shrink: 0; /* Don't shrink groups */
				border-right: 1px solid #eee;
				padding-right: 20px;
			}
			.pwp-toolbar-group:last-child { border-right: none; padding-right: 0; }

			.pwp-group-title {
				font-size: 10px;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				color: #646970;
				font-weight: 700;
				margin-bottom: 4px;
				padding-left: 2px;
			}
			.pwp-tags-list {
				display: flex;
				gap: 6px;
				align-items: center;
			}
			.pwp-tag-btn {
				display: inline-flex !important;
				align-items: center !important;
				gap: 6px;
				padding: 4px 10px !important;
				height: 32px !important;
				font-size: 13px !important;
				background: #fff !important;
				border: 1px solid #dcdcde !important;
				border-radius: 4px !important;
				cursor: pointer;
				transition: all 0.2s ease;
				color: #3c434a !important;
			}
			.pwp-tag-btn:hover {
				background: #f6f7f7 !important;
				border-color: #2271b1 !important;
				color: #2271b1 !important;
				transform: translateY(-1px);
				box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			}
			.pwp-tag-btn .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				color: #50575e;
			}
			.pwp-tag-btn:hover .dashicons {
				color: #2271b1;
			}
			
			/* Dark Theme Editor Tweaks */
			#pwp_form_html::selection { background: #3e4451; }
		</style>
		<script>
		// Horizontal Mouse Wheel Scroll Support
		jQuery(document).ready(function($){
			const container = document.getElementById('pwp-toolbar-container');
			if(container) {
				container.addEventListener('wheel', (evt) => {
					if (evt.deltaY !== 0) { // If vertical scroll attempted inside this narrow strip
						evt.preventDefault();
						container.scrollLeft += evt.deltaY; // Scroll horizontally instead
					}
				});
			}
		});

		function pwpInsert(myValue) {
			var myField = document.getElementById('pwp_form_html');
			if (document.selection) {
				myField.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
			} else if (myField.selectionStart || myField.selectionStart == '0') {
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
			} else {
				myField.value += myValue;
			}
			myField.focus();
		}
		</script>
		<?php	}

	/**
	 * Render Tab: Mail
	 */
	public function render_tab_mail( $post ) {
		// Defaults mimicking CF7
		$to = get_post_meta( $post->ID, '_pwp_mail_to', true ) ?: '[_site_admin_email]';
		$from = get_post_meta( $post->ID, '_pwp_mail_from', true ) ?: '[_site_title] <wordpress@' . $_SERVER['HTTP_HOST'] . '>';
		$subject = get_post_meta( $post->ID, '_pwp_mail_subject', true ) ?: '[_site_title] "[your-subject]"';
		$headers = get_post_meta( $post->ID, '_pwp_mail_headers', true ) ?: 'Reply-To: [your-email]';
		$body = get_post_meta( $post->ID, '_pwp_mail_body', true ) ?: "From: [your-name] <[your-email]>\nSubject: [your-subject]\n\nMessage Body:\n[your-message]\n\n-- \nThis is a notification that a contact form was submitted on your website ([_site_title] [_site_url]).";
		$attachments = get_post_meta( $post->ID, '_pwp_mail_attachments', true ) ?: '[your-file]';

		?>
		<div class="pwp-field-row">
			<label>To</label>
			<input type="text" name="pwp_mail_to" value="<?php echo esc_attr( $to ); ?>" class="large-text">
		</div>
		<div class="pwp-field-row">
			<label>From</label>
			<input type="text" name="pwp_mail_from" value="<?php echo esc_attr( $from ); ?>" class="large-text">
		</div>
		<div class="pwp-field-row">
			<label>Subject</label>
			<input type="text" name="pwp_mail_subject" value="<?php echo esc_attr( $subject ); ?>" class="large-text">
		</div>
		<div class="pwp-field-row">
			<label>Additional Headers</label>
			<textarea name="pwp_mail_headers" rows="2" class="large-text"><?php echo esc_textarea( $headers ); ?></textarea>
		</div>
		<div class="pwp-field-row">
			<label>Message Body</label>
			<textarea name="pwp_mail_body" rows="12" class="large-text"><?php echo esc_textarea( $body ); ?></textarea>
			<p class="description">Use [your-name], [your-email] tags corresponding to form field names.</p>
		</div>
		<div class="pwp-field-row">
			<label>File Attachments</label>
			<textarea name="pwp_mail_attachments" rows="2" class="large-text"><?php echo esc_textarea( $attachments ); ?></textarea>
			<p class="description">Enter file tags (e.g., [file-123]) one per line.</p>
		</div>
		<?php
	}

	/**
	 * Render Tab: Messages
	 */
	public function render_tab_messages( $post ) {
		// Define all messages
		$messages = [
			'msg_success' => [ 'label' => 'Sender\'s message was sent successfully', 'default' => 'Thank you for your message. It has been sent.' ],
			'msg_fail' => [ 'label' => 'Sender\'s message failed to send', 'default' => 'There was an error trying to send your message. Please try again later.' ],
			'msg_validation_error' => [ 'label' => 'Validation errors occurred', 'default' => 'One or more fields have an error. Please check and try again.' ],
			'msg_spam' => [ 'label' => 'Submission was referred to as spam', 'default' => 'Spam detected. Access denied.' ],
			'msg_required' => [ 'label' => 'There is a field that the sender must fill in', 'default' => 'Please fill out this field.' ],
			'msg_invalid_email' => [ 'label' => 'Email address that the sender entered is invalid', 'default' => 'Please enter a valid email address.' ],
			'msg_upload_failed' => [ 'label' => 'Uploading a file fails for any reason', 'default' => 'There was an unknown error uploading the file.' ],
		];

		foreach ( $messages as $key => $config ) {
			$val = get_post_meta( $post->ID, '_pwp_' . $key, true ) ?: $config['default'];
			?>
			<div class="pwp-field-row">
				<label><?php echo esc_html( $config['label'] ); ?></label>
				<input type="text" name="pwp_<?php echo $key; ?>" value="<?php echo esc_attr( $val ); ?>" class="large-text">
			</div>
			<?php
		}
	}

	/**
	 * Render Tab: Additional Settings
	 */
	public function render_tab_settings( $post ) {
		$snippets = get_post_meta( $post->ID, '_pwp_additional_settings', true );
		?>
		<div class="pwp-field-row">
			<label>Additional Settings</label>
			<textarea name="pwp_additional_settings" rows="10" class="large-text code" placeholder="demo_mode: on"><?php echo esc_textarea( $snippets ); ?></textarea>
			<p class="description">Add customization code snippets here (e.g., <code>demo_mode: on</code>).</p>
		</div>
		<?php
	}

	/**
	 * Deprecated Renderers (Stubs to prevent errors if called externally, though private so unlikely)
	 */
	public function render_html_box($post) {} 
	public function render_config_box($post) {}
	public function render_email_box($post) {}

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
			'pwp_form_submit_label', // Added
			'pwp_mail_to',
			'pwp_mail_from',
			'pwp_mail_subject',
			'pwp_mail_headers',
			'pwp_mail_body',
			'pwp_mail_attachments',
			'pwp_additional_settings',
			// Messages
			'pwp_msg_success',
			'pwp_msg_fail',
			'pwp_msg_validation_error',
			'pwp_msg_spam',
			'pwp_msg_required',
			'pwp_msg_invalid_email',
			'pwp_msg_upload_failed'
		];

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				// Special sanitization for HTML
				if ( $field === 'pwp_form_html' || $field === 'pwp_mail_body' ) {
					// Allow raw HTML for admins (unfiltered_html) to prevent stripping complex tags/attributes
					if ( current_user_can( 'unfiltered_html' ) ) {
						update_post_meta( $post_id, '_' . $field, $_POST[ $field ] );
					} else {
						update_post_meta( $post_id, '_' . $field, wp_kses_post( $_POST[ $field ] ) );
					}
				} else {
					update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( $_POST[ $field ] ) );
				}
			}
		}
	}
}
