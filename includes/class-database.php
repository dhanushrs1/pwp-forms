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

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
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
						if ( file_exists( $file_path ) ) {
							unlink( $file_path );
						}
					}
				}
			}
		}
	}
}
