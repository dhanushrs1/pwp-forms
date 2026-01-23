<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Database {

	/**
	 * Create custom database tables
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pwp_submissions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			user_id bigint(20) DEFAULT NULL,
			user_email varchar(100) NOT NULL,
			submission_type varchar(50) NOT NULL DEFAULT 'general',
			submission_data longtext NOT NULL,
			uploaded_files text DEFAULT NULL,
			user_ip varchar(45) DEFAULT NULL,
			status varchar(50) DEFAULT 'open',
			admin_notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY user_id (user_id),
			KEY user_email (user_email),
			KEY status (status)
		) $charset_collate;";

		// NEW: Replies Table
		$table_replies = $wpdb->prefix . 'pwp_submission_replies';
		$sql_replies = "CREATE TABLE $table_replies (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) NOT NULL,
			message longtext NOT NULL,
			sender enum('admin','user') NOT NULL DEFAULT 'admin',
			created_by bigint(20) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY submission_id (submission_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		dbDelta( $sql_replies );
	}

	/**
	 * Delete all data for a specific user ID (GDPR)
	 */
	public static function delete_user_data( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pwp_submissions';
		
		// 1. Get files to delete
		$submissions = $wpdb->get_results( $wpdb->prepare( "SELECT uploaded_files FROM $table_name WHERE user_id = %d", $user_id ) );
		self::delete_files_from_submissions( $submissions );

		// 2. Delete rows
		return $wpdb->delete( $table_name, [ 'user_id' => $user_id ] );
	}

	/**
	 * Delete all data for a specific email (GDPR)
	 */
	public static function delete_email_data( $email ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pwp_submissions';

		// 1. Get files to delete
		$submissions = $wpdb->get_results( $wpdb->prepare( "SELECT uploaded_files FROM $table_name WHERE user_email = %s", $email ) );
		self::delete_files_from_submissions( $submissions );

		// 2. Delete rows
		return $wpdb->delete( $table_name, [ 'user_email' => $email ] );
	}

	/**
	 * Helper to delete physical files
	 */
	private static function delete_files_from_submissions( $submissions ) {
		if ( empty( $submissions ) ) {
			return;
		}

		foreach ( $submissions as $submission ) {
			if ( ! empty( $submission->uploaded_files ) ) {
				$files = json_decode( $submission->uploaded_files, true );
				if ( is_array( $files ) ) {
					foreach ( $files as $file_path ) {
						// Assuming file_path is full path or relative to uploads
						// We will need to ensure how we store paths. 
						// For now, assume absolute path or handle later.
						// Security: Ensure file is within WP Uploads directory
						$upload_dir = wp_upload_dir();
						$base_dir = $upload_dir['basedir'];
						
						// Normalize paths for comparison (Windows/Unix)
						$real_path = realpath( $file_path );
						$real_base = realpath( $base_dir );

						if ( $real_path && $real_base && strpos( $real_path, $real_base ) === 0 ) {
							if ( file_exists( $file_path ) ) {
								// SAFETY CHECK: Ensure file isn't used by another submission (race condition prevention)
								if ( ! self::is_file_used_elsewhere( $file_path ) ) {
									unlink( $file_path );
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Check if a file is used by other submissions
	 * Prevents race condition where deleting one submission breaks another
	 * 
	 * @param string $file_path File path to check
	 * @return bool True if file is used elsewhere, false if safe to delete
	 */
	private static function is_file_used_elsewhere( $file_path ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pwp_submissions';
		
		// Search all submissions for this file path
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE uploaded_files LIKE %s",
			'%' . $wpdb->esc_like( $file_path ) . '%'
		) );
		
		// If more than 1 submission references this file, it's in use
		return ( $count > 1 );
	}
}
