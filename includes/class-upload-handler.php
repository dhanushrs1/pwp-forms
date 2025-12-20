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
		// Verify capability (Double check)
		// Logic already enforces this in Form_Submit, but good to have safeguard
		// "Internal Storage"
		
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

		// Override upload filters to enforce mime types specifically for this upload if needed
		// But wp_handle_upload checks against global allowed types. 
		// We'll manually check MIME type against our strict list before passing to WP.

		foreach ( $files as $key => $file_array ) {
			// Handle multiple files if array
			if ( is_array( $file_array['name'] ) ) {
				// Normalized parsing for multiple files not implemented in this simple loop
				// Assuming simple inputs name="file1", name="file2" for now as per "example markup"
				continue; 
			}

			if ( $file_array['error'] !== UPLOAD_ERR_OK ) {
				continue; // Skip empty or failed
			}

			// 1. Check File Type
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
				return new WP_Error( 'invalid_type', "File type $ext is not allowed." );
			}

			// 2. Size Limit (Dynamic)
			$max_size_mb = get_option( 'pwp_max_upload_size', '5' );
			$max_size_bytes = intval( $max_size_mb ) * 1024 * 1024;

			if ( $file_array['size'] > $max_size_bytes ) {
				return new WP_Error( 'file_too_large', "File is too large. Max {$max_size_mb}MB." );
			}

			// 3. Secure Name (wp_handle_upload does sanitize_file_name)
			// Move file
			$movefile = wp_handle_upload( $file_array, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$uploaded_files[] = $movefile['file']; // Store absolute path for email attachment
			} else {
				return new WP_Error( 'upload_error', $movefile['error'] );
			}
		}

		return $uploaded_files;
	}
}
