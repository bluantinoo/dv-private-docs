<?php
/**
 * Protected download handler for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle download requests for logged-in users
 */
function dv_handle_private_doc_download() {
	// Get document ID
	$doc_id = isset( $_GET['doc'] ) ? absint( $_GET['doc'] ) : 0;

	if ( ! $doc_id ) {
		wp_die( esc_html__( 'Invalid document request.', 'dv-private-docs' ), 400 );
	}

	// Verify nonce
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'dv_download_' . $doc_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'dv-private-docs' ), 403 );
	}

	// Check if user has access to this document
	if ( ! dv_user_can_access_doc( $doc_id ) ) {
		wp_die( esc_html__( 'You do not have permission to download this document.', 'dv-private-docs' ), 403 );
	}

	// Get file path
	$file_path = dv_get_document_file_path( $doc_id );

	if ( ! $file_path || ! file_exists( $file_path ) ) {
		wp_die( esc_html__( 'Document file not found.', 'dv-private-docs' ), 404 );
	}

	// Get original filename for download
	$download_filename = dv_get_document_original_filename( $doc_id );

	// Increment download counter
	$download_count = get_post_meta( $doc_id, 'dv_download_count', true );
	$download_count = $download_count ? absint( $download_count ) : 0;
	update_post_meta( $doc_id, 'dv_download_count', $download_count + 1 );

	// Determine MIME type
	$mime_type = mime_content_type( $file_path );
	if ( ! $mime_type ) {
		$mime_type = 'application/octet-stream';
	}

	// Clear any output buffers
	if ( ob_get_level() ) {
		ob_end_clean();
	}

	// Send headers
	header( 'Content-Type: ' . $mime_type );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $download_filename ) . '"' );
	header( 'Content-Length: ' . filesize( $file_path ) );
	header( 'Cache-Control: private, must-revalidate' );
	header( 'Pragma: private' );
	header( 'Expires: 0' );

	// Disable compression for this download to prevent file corruption
	if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv( 'no-gzip', '1' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv -- Required to disable Apache compression for binary file downloads
	}
	@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Required to disable output compression for binary file downloads, prevents file corruption

	// Stream file in chunks
	// Note: Using direct filesystem functions instead of WP_Filesystem because:
	// - WP_Filesystem doesn't support chunked streaming
	// - Chunked streaming is essential for large files (PDFs, documents)
	// - Prevents memory exhaustion on shared hosting
	$chunk_size = 8192; // 8KB chunks
	$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for chunked file streaming

	if ( false === $handle ) {
		wp_die( esc_html__( 'Cannot read file.', 'dv-private-docs' ), 500 );
	}

	while ( ! feof( $handle ) ) {
		echo fread( $handle, $chunk_size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Binary file streaming, escaping would corrupt data

		// Flush output buffer
		if ( ob_get_level() > 0 ) {
			ob_flush();
		}
		flush();
	}

	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing file handle from fopen

	// Exit to prevent any additional output
	exit;
}
add_action( 'admin_post_dv_private_doc_download', 'dv_handle_private_doc_download' );

/**
 * Handle download requests for non-logged-in users
 * Always deny access
 */
function dv_handle_private_doc_download_nopriv() {
	wp_die(
		esc_html__( 'You must be logged in to download documents.', 'dv-private-docs' ),
		esc_html__( 'Access Denied', 'dv-private-docs' ),
		array( 'response' => 403 )
	);
}
add_action( 'admin_post_nopriv_dv_private_doc_download', 'dv_handle_private_doc_download_nopriv' );
