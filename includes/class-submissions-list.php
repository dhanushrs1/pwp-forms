<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class PWP_Submissions_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => 'submission',
			'plural'   => 'submissions',
			'ajax'     => false
		] );
	}

	/**
	 * Message to show if no items found
	 */
	public function no_items() {
		echo 'No submissions found.';
	}

	/**
	 * Default Column Render
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'created_at':
			case 'user_email':
			case 'submission_type':
			case 'status':
				return esc_html( $item[ $column_name ] );
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Checkbox Column
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * ID Column with Actions
	 */
	public function column_id( $item ) {
		$page      = $_REQUEST['page'];
		$post_type = 'pwp_form';
		$view_url  = "edit.php?post_type=$post_type&page=$page&action=view&id={$item['id']}";
		$del_url   = wp_nonce_url( "edit.php?post_type=$post_type&page=$page&action=delete&id={$item['id']}", 'delete_submission_' . $item['id'] );

		$actions = [
			'view'   => sprintf( '<a href="%s">View</a>', esc_url( $view_url ) ),
			'delete' => sprintf( '<a href="%s" onclick="return confirm(\'Delete?\')">Delete</a>', esc_url( $del_url ) ),
		];

		return sprintf( '%1$s %2$s', $item['id'], $this->row_actions( $actions ) );
	}

	/**
	 * Status Column
	 */
	public function column_status( $item ) {
		$status = strtolower( $item['status'] );
		$color = '#555'; // Default/Open/Closed
		if ( $status === 'new' ) $color = '#46b450';
		if ( $status === 'read' ) $color = '#0073aa';
		if ( $status === 'replied' ) $color = '#e27730';
		if ( $status === 'closed' ) $color = '#333';
		
		return sprintf( '<span style="color:white; background:%s; padding:3px 8px; border-radius:3px; font-weight:bold; font-size:11px;">%s</span>', $color, ucfirst( $status ) );
	}

	/**
	 * Columns
	 */
	public function get_columns() {
		$columns = [
			'cb'              => '<input type="checkbox" />',
			'id'              => 'ID',
			'created_at'      => 'Date',
			'user_email'      => 'Email',
			'submission_type' => 'For Form',
			'status'          => 'Status'
		];
		return $columns;
	}

	/**
	 * Sortable Columns
	 */
	public function get_sortable_columns() {
		$sortable = [
			'id'         => [ 'id', true ],
			'created_at' => [ 'created_at', false ],
			'user_email' => [ 'user_email', false ],
			'status'     => [ 'status', false ]
		];
		return $sortable;
	}

	/**
	 * Prepare Items (Query)
	 */
	public function prepare_items() {
		global $wpdb;
		$table = $wpdb->prefix . 'pwp_submissions';

		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );

		$per_page = 20; // fallback
		if ( ! empty( $option ) ) {
			$per_page = get_user_meta( $user, $option, true );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = $screen->get_option( 'per_page', 'default' );
			}
		}
		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Query building
		$query = "SELECT * FROM $table WHERE 1=1";
		
		// Search
		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = esc_sql( $_REQUEST['s'] );
			// Enhanced Search: Email OR Data OR Form ID
			$query .= " AND ( user_email LIKE '%$search%' OR submission_data LIKE '%$search%' OR form_id = '$search' )";
		}

		// Status Filter
		if ( ! empty( $_REQUEST['pwp_status_filter'] ) ) {
			$status = esc_sql( $_REQUEST['pwp_status_filter'] );
			$query .= " AND status = '$status'";
		}

		// Date Filter (YYYY-MM)
		if ( ! empty( $_REQUEST['pwp_date_filter'] ) ) {
			$date_filter = sanitize_text_field( $_REQUEST['pwp_date_filter'] );
			// Expect YYYY-MM
			$parts = explode( '-', $date_filter );
			if ( count( $parts ) === 2 ) {
				$year = esc_sql( $parts[0] );
				$month = esc_sql( $parts[1] );
				$query .= " AND MONTH(created_at) = '$month' AND YEAR(created_at) = '$year'";
			}
		}

		// Order
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? esc_sql( $_REQUEST['orderby'] ) : 'created_at';
		$order = ( ! empty( $_REQUEST['order'] ) ) ? esc_sql( $_REQUEST['order'] ) : 'DESC';
		$query .= " ORDER BY $orderby $order";

		// Pagination logic
		$total_items = $wpdb->get_var( str_replace( '*', 'COUNT(*)', $query ) );
		
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;
		
		$query .= " LIMIT $offset, $per_page";

		// Fetch
		$this->items = $wpdb->get_results( $query, ARRAY_A );

		// Register Pagination
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		] );
	}

	/**
	 * Extra Table Navigation (Filters)
	 */
	public function extra_tablenav( $which ) {
		if ( $which === 'top' ) {
			?>
			<div class="alignleft actions">
				<?php $this->render_status_dropdown(); ?>
				<?php $this->render_date_picker(); ?>
				<?php submit_button( 'Filter', 'secondary', 'filter_action', false ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Render Status Dropdown
	 */
	private function render_status_dropdown() {
		$selected = isset( $_REQUEST['pwp_status_filter'] ) ? $_REQUEST['pwp_status_filter'] : '';
		$statuses = [
			'new'     => 'New',
			'read'    => 'Read',
			'replied' => 'Replied',
			'closed'  => 'Closed'
		];
		?>
		<select name="pwp_status_filter">
			<option value="">All Statuses</option>
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render Date Picker (Input Type Month)
	 */
	private function render_date_picker() {
		$selected = isset( $_REQUEST['pwp_date_filter'] ) ? $_REQUEST['pwp_date_filter'] : '';
		?>
		<!-- Visual Calendar Month Picker -->
		<input type="month" name="pwp_date_filter" value="<?php echo esc_attr( $selected ); ?>" />
		<?php
	}

	/**
	 * Bulk Actions
	 */
	public function get_bulk_actions() {
		return [
			'bulk-delete' => 'Delete'
		];
	}
}
