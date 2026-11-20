<?php
/**
 * Helper functions for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central access control function
 * Determines if current user can access a specific document
 *
 * @param int $doc_id Post ID of the document
 * @return bool True if user has access, false otherwise
 */
function dv_user_can_access_doc( $doc_id ) {
	// Must be logged in
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Verify it's a valid document post
	$post = get_post( $doc_id );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return false;
	}

	// Admins can access everything
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	// Check if current user is the assigned user
	$assigned_user_id = (int) get_post_meta( $doc_id, 'dv_assigned_user_id', true );
	$current_user_id = get_current_user_id();

	return $assigned_user_id === $current_user_id;
}

/**
 * Generate a unique filename for private storage
 *
 * @param int $post_id Document post ID
 * @param int $attachment_id Attachment ID
 * @param string $original_filename Original file name
 * @return string Unique filename
 */
function dv_generate_private_filename( $post_id, $attachment_id, $original_filename ) {
	// Get file extension
	$pathinfo = pathinfo( $original_filename );
	$extension = isset( $pathinfo['extension'] ) ? $pathinfo['extension'] : 'bin';

	// Generate 12-character hash
	$hash = substr( md5( $post_id . $attachment_id . time() . wp_salt() ), 0, 12 );

	// Build filename: dvdoc-{post_id}-{attachment_id}-{hash}.{ext}
	return sprintf( 'dvdoc-%d-%d-%s.%s', $post_id, $attachment_id, $hash, $extension );
}

/**
 * Get the original filename from an attachment
 *
 * @param int $attachment_id Attachment ID
 * @return string Original filename or generic fallback
 */
function dv_get_original_filename( $attachment_id ) {
	$file_path = get_attached_file( $attachment_id );

	if ( $file_path ) {
		return basename( $file_path );
	}

	// Fallback
	return 'document.bin';
}

/**
 * Get the original filename for a document
 * Uses stored meta after file is moved to private storage
 *
 * @param int $doc_id Document post ID
 * @return string Original filename
 */
function dv_get_document_original_filename( $doc_id ) {
	// Try to get from meta (stored after file is moved to private storage)
	$filename = get_post_meta( $doc_id, 'dv_original_filename', true );

	if ( $filename ) {
		return $filename;
	}

	// Fallback: use post title with .pdf extension
	$post = get_post( $doc_id );
	if ( $post ) {
		return sanitize_file_name( $post->post_title ) . '.pdf';
	}

	return 'document.pdf';
}

/**
 * Get human-readable file size
 *
 * @param string $file_path Full path to file
 * @return string Formatted file size
 */
function dv_get_file_size( $file_path ) {
	if ( ! file_exists( $file_path ) ) {
		return __( 'Unknown', 'dv-private-docs' );
	}

	$bytes = filesize( $file_path );
	$units = array( 'B', 'KB', 'MB', 'GB' );

	for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
		$bytes /= 1024;
	}

	return round( $bytes, 2 ) . ' ' . $units[ $i ];
}

/**
 * Check if a document has a valid file in private storage
 *
 * @param int $doc_id Document post ID
 * @return bool True if file exists
 */
function dv_document_has_file( $doc_id ) {
	$relpath = get_post_meta( $doc_id, 'dv_private_relpath', true );

	if ( empty( $relpath ) ) {
		return false;
	}

	$full_path = WP_CONTENT_DIR . '/' . $relpath;
	return file_exists( $full_path );
}

/**
 * Get the absolute path to a document's private file
 *
 * @param int $doc_id Document post ID
 * @return string|false Full path or false if not found
 */
function dv_get_document_file_path( $doc_id ) {
	$relpath = get_post_meta( $doc_id, 'dv_private_relpath', true );

	if ( empty( $relpath ) ) {
		return false;
	}

	$full_path = WP_CONTENT_DIR . '/' . $relpath;

	if ( ! file_exists( $full_path ) ) {
		return false;
	}

	return $full_path;
}

/**
 * ============================================
 * WPML COMPATIBILITY HELPERS
 * ============================================
 */

/**
 * Check if WPML is active and multilingual support is available
 *
 * @return bool
 */
function dv_wpml_is_active() {
	return defined( 'ICL_SITEPRESS_VERSION' ) && function_exists( 'icl_object_id' );
}

/**
 * Get current WPML language code
 *
 * @return string|null Language code (e.g., 'en', 'it') or null if WPML not active
 */
function dv_get_current_lang() {
	if ( ! dv_wpml_is_active() ) {
		return null;
	}

	return apply_filters( 'wpml_current_language', null );
}

/**
 * Get default WPML language code
 *
 * @return string|null Language code or null if WPML not active
 */
function dv_get_default_lang() {
	if ( ! dv_wpml_is_active() ) {
		return null;
	}

	return apply_filters( 'wpml_default_language', null );
}

/**
 * Get translated post/page ID for current or specified language
 *
 * @param int    $post_id Post ID to translate
 * @param string $post_type Post type (e.g., 'page', 'post', 'dv_private_doc')
 * @param string|null $lang Target language code (null = current language)
 * @return int Translated post ID, or original if no translation found
 */
function dv_get_translated_post_id( $post_id, $post_type = 'page', $lang = null ) {
	if ( ! dv_wpml_is_active() || ! $post_id ) {
		return $post_id;
	}

	if ( null === $lang ) {
		$lang = dv_get_current_lang();
	}

	$translated_id = apply_filters( 'wpml_object_id', $post_id, $post_type, true, $lang );

	return $translated_id ? $translated_id : $post_id;
}

/**
 * Check if a post type is set as translatable in WPML
 *
 * @param string $post_type Post type to check
 * @return bool
 */
function dv_is_cpt_translatable( $post_type ) {
	if ( ! dv_wpml_is_active() ) {
		return false;
	}

	// Get WPML post type translation settings
	$wpml_settings = apply_filters( 'wpml_setting', false, 'custom_posts_sync_option' );

	if ( ! is_array( $wpml_settings ) ) {
		return false;
	}

	// Check if post type is set to translate (1) or not (0 or 2=display-as-translated)
	// 0 = do not translate, 1 = translate, 2 = display as translated
	return isset( $wpml_settings[ $post_type ] ) && 1 === (int) $wpml_settings[ $post_type ];
}

/**
 * Get page URL from language-aware option
 *
 * @param string $base_option_key Base option key (e.g., 'dv_list_page', 'dv_view_page')
 * @param int    $fallback_post_id Optional fallback post ID if option not set
 * @return string Page URL or empty string
 */
function dv_get_page_url_for_lang_option( $base_option_key, $fallback_post_id = 0 ) {
	$page_id = 0;

	if ( dv_wpml_is_active() ) {
		$current_lang = dv_get_current_lang();
		$default_lang = dv_get_default_lang();

		// Try current language option first
		if ( $current_lang ) {
			$page_id = absint( get_option( $base_option_key . '_' . $current_lang, 0 ) );
		}

		// Fallback to default language option
		if ( ! $page_id && $default_lang && $default_lang !== $current_lang ) {
			$page_id = absint( get_option( $base_option_key . '_' . $default_lang, 0 ) );
		}

		// Fallback to base option (backwards compatibility)
		if ( ! $page_id ) {
			$page_id = absint( get_option( $base_option_key, 0 ) );
		}

		// Last resort: try to find ANY option with language suffix (for WPML deactivation scenarios)
		if ( ! $page_id ) {
			global $wpdb;
			$pattern = $wpdb->esc_like( $base_option_key . '_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback query for WPML activation/deactivation scenarios; searches for options with dynamic language suffixes that cannot be retrieved with standard WP functions; rare execution path so caching not beneficial
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value != '0' LIMIT 1",
					$pattern
				)
			);
			if ( $result ) {
				$page_id = absint( $result );
			}
		}

		// If we have a page ID, translate it to current language
		if ( $page_id ) {
			$page_id = dv_get_translated_post_id( $page_id, 'page', $current_lang );
		}
	} else {
		// No WPML active: try base option first
		$page_id = absint( get_option( $base_option_key, 0 ) );

		// If base option doesn't exist, try to match WordPress default language
		// This handles the case where WPML was active before and user disabled it
		if ( ! $page_id ) {
			// Get WordPress locale (e.g., 'it_IT', 'en_US') and extract language code
			$wp_locale = get_locale();
			$wp_lang = substr( $wp_locale, 0, 2 ); // Extract 'it' from 'it_IT', 'en' from 'en_US'

			// Try option matching WordPress language
			if ( $wp_lang ) {
				$page_id = absint( get_option( $base_option_key . '_' . $wp_lang, 0 ) );
			}

			// Last resort: find ANY saved option with language suffix
			if ( ! $page_id ) {
				global $wpdb;
				$pattern = $wpdb->esc_like( $base_option_key . '_' ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback query for WPML deactivation scenarios; searches for options with dynamic language suffixes that cannot be retrieved with standard WP functions; rare execution path so caching not beneficial
				$result = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value != '0' ORDER BY option_name ASC LIMIT 1",
						$pattern
					)
				);
				if ( $result ) {
					$page_id = absint( $result );
				}
			}
		}
	}

	// Use fallback if still no page ID
	if ( ! $page_id && $fallback_post_id ) {
		$page_id = absint( $fallback_post_id );
	}

	// Return permalink or empty string
	return $page_id ? get_permalink( $page_id ) : '';
}

/**
 * Get user's preferred language for notifications
 *
 * @param int $user_id User ID
 * @return string|null Language code or null/default
 */
function dv_get_user_language( $user_id ) {
	if ( ! dv_wpml_is_active() ) {
		return null;
	}

	// Try WPML user language meta (admin language preference)
	$user_lang = get_user_meta( $user_id, 'icl_admin_language', true );

	if ( $user_lang && $user_lang !== '_default_' ) {
		return $user_lang;
	}

	// Try alternative WPML user meta key
	$user_lang = get_user_meta( $user_id, 'wpml_language', true );

	if ( $user_lang ) {
		return $user_lang;
	}

	// Fallback to default site language (always return a valid language when WPML is active)
	$default_lang = dv_get_default_lang();

	return $default_lang ? $default_lang : null;
}

/**
 * Get email template with WPML support and fallback hierarchy
 *
 * @param string $base_option_key Base option key (e.g., 'dv_template_assignment')
 * @param string $lang            Language code or null
 * @param string $default_text    Fallback text if no template found
 * @return string Template text
 */
function dv_get_email_template( $base_option_key, $lang, $default_text ) {
	$template = '';

	if ( dv_wpml_is_active() ) {
		$current_lang = $lang ? $lang : dv_get_current_lang();
		$default_lang = dv_get_default_lang();

		// Try current language option first
		if ( $current_lang ) {
			$template = get_option( $base_option_key . '_' . $current_lang, '' );
		}

		// Fallback to default language option
		if ( empty( $template ) && $default_lang && $default_lang !== $current_lang ) {
			$template = get_option( $base_option_key . '_' . $default_lang, '' );
		}

		// Fallback to base option (backwards compatibility)
		if ( empty( $template ) ) {
			$template = get_option( $base_option_key, '' );
		}

		// Last resort: database search for any language option
		if ( empty( $template ) ) {
			global $wpdb;
			$pattern = $wpdb->esc_like( $base_option_key . '_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback query for WPML activation/deactivation scenarios; searches for template options with dynamic language suffixes that cannot be retrieved with standard WP functions; rare execution path so caching not beneficial
			$result  = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value != '' LIMIT 1",
					$pattern
				)
			);
			if ( $result ) {
				$template = $result;
			}
		}
	} else {
		// No WPML: try base option, then locale match, then database search
		$template = get_option( $base_option_key, '' );

		if ( empty( $template ) ) {
			$wp_locale = get_locale();
			$wp_lang   = substr( $wp_locale, 0, 2 );

			if ( $wp_lang ) {
				$template = get_option( $base_option_key . '_' . $wp_lang, '' );
			}

			if ( empty( $template ) ) {
				global $wpdb;
				$pattern = $wpdb->esc_like( $base_option_key . '_' ) . '%';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback query for WPML deactivation scenarios; searches for template options with dynamic language suffixes that cannot be retrieved with standard WP functions; rare execution path so caching not beneficial
				$result  = $wpdb->get_var(
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback query for WPML deactivation scenarios; searches for template options with dynamic language suffixes that cannot be retrieved with standard WP functions; rare execution path so caching not beneficial
					$wpdb->prepare(
						"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value != '' LIMIT 1",
						$pattern
					)
				);
				if ( $result ) {
					$template = $result;
				}
			}
		}
	}

	// Return template or default
	return ! empty( $template ) ? $template : $default_text;
}

/**
 * Replace placeholders in email templates
 *
 * @param string $template Template text with placeholders
 * @param array  $data     Associative array of placeholder => value pairs
 * @return string Template with placeholders replaced
 */
function dv_replace_email_placeholders( $template, $data ) {
	foreach ( $data as $key => $value ) {
		$placeholder = '{' . $key . '}';
		$template    = str_replace( $placeholder, $value, $template );
	}
	return $template;
}

/**
 * Get default assignment email template
 *
 * @return string Default template with placeholders
 */
function dv_get_default_assignment_template() {
	return __( "Hello {user_name},\n\nA new document has been assigned to you:\n\n{document_title}\n\nYou can view and download it here:\n{document_url}\n\nBest regards", 'dv-private-docs' );
}

/**
 * Get default comment email template
 *
 * @return string Default template with placeholders
 */
function dv_get_default_comment_template() {
	return __( "Hello {user_name},\n\nNew comment from {comment_author} on document \"{document_title}\":\n\n{comment_text}\n\nView and reply here:\n{document_url}\n\nBest regards", 'dv-private-docs' );
}

