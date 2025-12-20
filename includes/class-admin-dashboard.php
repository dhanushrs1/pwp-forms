<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Admin_Dashboard {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3 );
	}

	/**
	 * Add Menu Page
	 */
	public function add_admin_menu() {
		$hook = add_submenu_page(
			'edit.php?post_type=pwp_form',
			'Submissions',
			'Submissions',
			'manage_options',
			'pwp-form-submissions', // Renamed slug
			[ $this, 'render_page' ]
		);

		add_action( "load-$hook", [ $this, 'add_screen_options' ] );
	}

	/**
	 * Add Screen Options
	 */
	public function add_screen_options() {
		add_screen_option( 'per_page', [
			'label'   => 'Submissions per page',
			'default' => 20,
			'option'  => 'pwp_submissions_per_page'
		] );
	}

	/**
	 * Save Screen Option
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'pwp_submissions_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Handle POST Actions (Reply, Delete)
	 */
	public function handle_actions() {
		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'pwp-form-submissions' ) {
			return;
		}

		// Delete Submission
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			check_admin_referer( 'delete_submission_' . $_GET['id'] );
			global $wpdb;
			$table = $wpdb->prefix . 'pwp_submissions';
			// Get files first to delete
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT uploaded_files FROM $table WHERE id = %d", $_GET['id'] ) );
			// Mock deleting files manually here or call helper if strict
			if ( $row && ! empty( $row->uploaded_files ) ) {
				$files = json_decode( $row->uploaded_files, true );
				if ( is_array( $files ) ) {
					foreach ( $files as $f ) {
						if ( file_exists( $f ) ) unlink( $f );
					}
				}
			}
			$wpdb->delete( $table, [ 'id' => $_GET['id'] ] );
			wp_redirect( admin_url( 'edit.php?post_type=pwp_form&page=pwp-form-submissions&msg=deleted' ) );
			exit;
		}

		// GDPR Delete By Email
		if ( isset( $_POST['pwp_gdpr_action'] ) && $_POST['pwp_gdpr_action'] === 'delete_email' ) {
			check_admin_referer( 'pwp_gdpr_action' );
			$email = sanitize_email( $_POST['gdpr_email'] );
			if ( is_email( $email ) ) {
				require_once PWP_FORMS_PATH . 'includes/class-database.php';
				PWP_Database::delete_email_data( $email );
				wp_redirect( admin_url( 'edit.php?post_type=pwp_form&page=pwp-form-submissions&msg=gdpr_deleted' ) );
				exit;
			}
		}

		// Send Reply
		if ( isset( $_POST['pwp_action'] ) && $_POST['pwp_action'] === 'reply' ) {
			check_admin_referer( 'reply_submission' );
			$id = intval( $_POST['submission_id'] );
			$message = wp_kses_post( $_POST['reply_message'] );
			$to = sanitize_email( $_POST['reply_to'] );

			// Send Email
			$subject = 'Re: Your Support Request'; // Could be dynamic
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			wp_mail( $to, $subject, $message, $headers );

			// Update Status & Note
			global $wpdb;
			$table = $wpdb->prefix . 'pwp_submissions';
			$wpdb->update( 
				$table, 
				[ 'status' => 'replied', 'admin_notes' => 'Replied on ' . current_time( 'mysql' ) ], 
				[ 'id' => $id ] 
			);

			wp_redirect( admin_url( 'edit.php?post_type=pwp_form&page=pwp-form-submissions&action=view&id=' . $id . '&msg=replied' ) );
			exit;
		}
	}

	/**
	 * Render Page
	 */
	public function render_page() {
		$action = $_GET['action'] ?? 'list';

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">Pro Submissions</h1>';

		if ( isset( $_GET['msg'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Action completed successfully.</p></div>';
		}

		if ( $action === 'view' && isset( $_GET['id'] ) ) {
			$this->render_detail_view( intval( $_GET['id'] ) );
		} else {
			$this->render_gdpr_tools(); // Move to top
			$this->render_list_view();
		}
		echo '</div>';
	}

	/**
	 * Render List View (Using WP_List_Table)
	 */
	private function render_list_view() {
		require_once PWP_FORMS_PATH . 'includes/class-submissions-list.php';
		
		$list_table = new PWP_Submissions_List_Table();
		$list_table->prepare_items();
		
		?>
		<form id="pwp-submissions-filter" method="get">
			<input type="hidden" name="post_type" value="pwp_form" />
			<input type="hidden" name="page" value="pwp-form-submissions" />
			<?php $list_table->search_box( 'Search Submissions', 'pwp-search' ); ?>
			<?php $list_table->display(); ?>
		</form>
		<?php
	}

	/**
	 * Render Detail View (Advanced Metabox Layout)
	 */
	private function render_detail_view( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pwp_submissions';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		if ( ! $row ) {
			echo '<div class="notice notice-error"><p>Submission not found.</p></div>';
			return;
		}

		$data = json_decode( $row->submission_data, true );
		$files = json_decode( $row->uploaded_files, true );
		
		// Handle Status Update Post
		if ( isset( $_POST['pwp_action'] ) && $_POST['pwp_action'] === 'update_status' ) {
			check_admin_referer( 'update_status_' . $id );
			$new_status = sanitize_text_field( $_POST['status'] );
			$wpdb->update( $table, [ 'status' => $new_status ], [ 'id' => $id ] );
			$row->status = $new_status; // Reflect immediate change
			echo '<div class="notice notice-success is-dismissible"><p>Status updated.</p></div>';
		}

		?>
		<p><a href="<?php echo admin_url( 'edit.php?post_type=pwp_form&page=pwp-form-submissions' ); ?>" class="button">&larr; Back to Submissions</a></p>
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				
				<!-- Main Column -->
				<div id="post-body-content">
					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle">Submission Data</h2></div>
						<div class="inside">
							<table class="widefat striped">
								<tbody>
									<?php foreach ( $data as $key => $val ) : ?>
									<tr>
										<th style="width:150px; color:#555;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></th>
										<td>
											<?php 
											if ( is_array( $val ) ) echo implode( ', ', array_map( 'esc_html', $val ) );
											else echo nl2br( esc_html( $val ) );
											?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<?php if ( ! empty( $files ) ) : ?>
								<h3>Values Attached</h3>
								<div style="background:#f9f9f9; padding:10px; border:1px solid #ddd;">
									<?php foreach ( $files as $file_path ) : 
										$upload_dir = wp_upload_dir();
										$file_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
									?>
										<p>
											<span class="dashicons dashicons-paperclip"></span>
											<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" style="font-weight:bold;">
												<?php echo basename( $file_path ); ?>
											</a>
											<small>(<?php echo size_format( filesize( $file_path ) ); ?>)</small>
										</p>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle">Reply to User</h2></div>
						<div class="inside">
							<form method="post" action="">
								<?php wp_nonce_field( 'reply_submission' ); ?>
								<input type="hidden" name="pwp_action" value="reply">
								<input type="hidden" name="submission_id" value="<?php echo $row->id; ?>">
								<input type="hidden" name="reply_to" value="<?php echo esc_attr( $row->user_email ); ?>">
								
								<p>
									<strong>To:</strong> <code><?php echo esc_html( $row->user_email ); ?></code>
								</p>
								<textarea name="reply_message" rows="6" class="large-text" placeholder="Write your reply..."></textarea>
								<p class="submit">
									<button type="submit" class="button button-primary">Send Email Reply</button>
								</p>
							</form>
						</div>
					</div>
				</div>

				<!-- Sidebar Column -->
				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle">Status & Actions</h2></div>
						<div class="inside">
							<form method="post" action="">
								<?php wp_nonce_field( 'update_status_' . $id ); ?>
								<input type="hidden" name="pwp_action" value="update_status">
								
								<p><strong>Created:</strong> <?php echo $row->created_at; ?></p>
								<p><strong>IP:</strong> <?php echo isset( $row->user_ip ) ? esc_html( $row->user_ip ) : 'N/A'; ?></p>
								
								<label for="status_select"><strong>Status:</strong></label>
								<br>
								<select name="status" id="status_select" style="width:100%; margin-top:5px;">
									<option value="new" <?php selected( $row->status, 'new' ); ?>>New</option>
									<option value="read" <?php selected( $row->status, 'read' ); ?>>Read</option>
									<option value="replied" <?php selected( $row->status, 'replied' ); ?>>Replied</option>
									<option value="closed" <?php selected( $row->status, 'closed' ); ?>>Closed</option>
								</select>

								<p style="text-align:right; margin-top:10px;">
									<button type="submit" class="button button-primary">Update Status</button>
								</p>
							</form>
							
							<div style="border-top:1px solid #eee; margin-top:10px; padding-top:10px;">
								<a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=pwp_form&page=pwp-form-submissions&action=delete&id=' . $row->id ), 'delete_submission_' . $row->id ); ?>" style="color:#a00; text-decoration:none;" onclick="return confirm('Delete permanently?');">Delete Submission</a>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * GDPR Tools (Accordion Style)
	 */
	private function render_gdpr_tools() {
		?>
		<details style="margin-bottom:20px; background:#fff; border:1px solid #ccd0d4; padding:10px; border-radius:4px;">
			<summary style="cursor:pointer; font-weight:600; outline:none;">Privacy Tools (GDPR)</summary>
			<div style="margin-top:10px; padding-top:10px; border-top:1px solid #eee;">
				<form method="post" action="" style="display:flex; align-items:center; gap:10px;">
					<?php wp_nonce_field( 'pwp_gdpr_action' ); ?>
					<input type="hidden" name="pwp_gdpr_action" value="delete_email">
					<label>Delete all data for Email: </label>
					<input type="email" name="gdpr_email" placeholder="user@example.com" required>
					<button type="submit" class="button button-secondary" onclick="return confirm('Permanently delete all submissions and files for this email?');">Delete Data</button>
				</form>
				<p class="description">Permanently remove all records associated with an email address to comply with "Right to be Forgotten".</p>
			</div>
		</details>
		<?php
	}
}
