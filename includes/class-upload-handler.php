<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PWP_Upload_Handler {

	/**
	 * Handle File Uploads
	 * 
	 * @param array $files $_FILES array
	 * @param int $form_id Form ID
	 * @return array|WP_Error Array of file URLs/Paths or Error
	 */
	public static function handle_uploads( $files, $form_id ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$uploaded_files = [];
		$upload_overrides = [ 'test_form' => false ];
		
		// Allowed Mime Types (Whitelist)
		$allowed_mimes = [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'txt'          => 'text/plain'
		];

		// Normalize $_FILES structure to a flat array of files
		$normalized_files = self::normalize_files( $files );

		if ( empty( $normalized_files ) ) {
			return $uploaded_files;
		}

		$max_size_mb = get_option( 'pwp_max_upload_size', '5' );
		$max_size_bytes = intval( $max_size_mb ) * 1024 * 1024;

		foreach ( $normalized_files as $file_array ) {
			if ( $file_array['error'] !== UPLOAD_ERR_OK ) {
				continue; 
			}

			// 1. Size Limit Check
			if ( $file_array['size'] > $max_size_bytes ) {
				return new WP_Error( 'file_too_large', "File {$file_array['name']} is too large. Max {$max_size_mb}MB." );
			}

			// 2. MIME Type Check
			$file_info = wp_check_filetype( $file_array['name'] );
			$ext = $file_info['ext'];
			$type = $file_info['type'];
			
			$is_allowed = false;
			foreach ( $allowed_mimes as $mime_ext => $mime_type ) {
				if ( stripos( $mime_ext, $ext ) !== false && $type === $mime_type ) {
					$is_allowed = true;
					break;
				}
			}

			if ( ! $is_allowed ) {
				return new WP_Error( 'invalid_type', "File type $ext is not allowed for file {$file_array['name']}." );
			}

			// 3. Handle Upload
			$movefile = wp_handle_upload( $file_array, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$uploaded_files[] = $movefile['file'];
			} else {
				return new WP_Error( 'upload_error', $movefile['error'] );
			}
		}

		return $uploaded_files;
	}

	/**
	 * Helper to normalize $_FILES array
	 * Handles both single (name="file") and array (name="files[]") inputs
	 */
	private static function normalize_files( $files ) {
		$normalized = [];

		foreach ( $files as $key => $file ) {
			if ( is_array( $file['name'] ) ) {
				// Reshape "files[]" structure
				$count = count( $file['name'] );
				$keys = array_keys( $file );

				for ( $i = 0; $i < $count; $i++ ) {
					$new_file = [];
					foreach ( $keys as $k ) {
						$new_file[$k] = $file[$k][$i];
					}
					// Only add if not empty (check error code 4 = UPLOAD_ERR_NO_FILE)
					if ( $new_file['error'] != 4 ) {
						$normalized[] = $new_file;
					}
				}
			} else {
				// Single file
				if ( $file['error'] != 4 ) {
					$normalized[] = $file;
				}
			}
		}

		return $normalized;
	}
}
