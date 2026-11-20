<?php
/**
 * Uninstall DV Private Documents
 *
 * Runs only when the plugin is deleted, never on deactivation. By default it
 * removes only the update channel state, so documents, files and settings
 * survive a reinstall. Everything is removed only when "Delete Data on
 * Uninstall" is checked in the settings.
 *
 * @package DV_Private_Docs
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove the update channel state (always).
 */
function dv_private_docs_uninstall_update_channel() {
	delete_site_option( 'external_updates-dv-private-docs' );
	wp_clear_scheduled_hook( 'puc_cron_check_updates-dv-private-docs' );
}

/**
 * Delete a directory and everything in it.
 *
 * @param string $dir Absolute path.
 */
function dv_private_docs_uninstall_rmdir( $dir ) {
	$items = scandir( $dir );
	if ( false === $items ) {
		return;
	}

	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}

		$path = $dir . DIRECTORY_SEPARATOR . $item;

		if ( is_dir( $path ) && ! is_link( $path ) ) {
			dv_private_docs_uninstall_rmdir( $path );
		} else {
			wp_delete_file( $path );
		}
	}

	@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- best effort, a leftover empty folder is harmless
}

/**
 * The private storage folder, if it exists where the plugin puts it.
 *
 * @return string|false Absolute path.
 */
function dv_private_docs_uninstall_storage() {
	$content_dir = realpath( WP_CONTENT_DIR );
	$storage     = $content_dir ? realpath( $content_dir . DIRECTORY_SEPARATOR . 'private-docs' ) : false;

	if ( $storage && is_dir( $storage ) && dirname( $storage ) === $content_dir ) {
		return $storage;
	}

	return false;
}

/**
 * Remove every document, file, user field and setting of the current site.
 *
 * @return bool Whether this site asked for its data to be deleted.
 */
function dv_private_docs_uninstall_site() {
	global $wpdb;

	if ( ! get_option( 'dv_delete_data_on_uninstall', false ) ) {
		return false;
	}

	$storage = dv_private_docs_uninstall_storage();

	// Documents with their files, meta and comments.
	$doc_ids = get_posts( array(
		'post_type'        => 'dv_private_doc',
		'post_status'      => array_keys( get_post_stati() ), // 'any' leaves out trash and auto-draft
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'suppress_filters' => true,
	) );

	foreach ( $doc_ids as $doc_id ) {
		$relpath = (string) get_post_meta( $doc_id, 'dv_private_relpath', true );
		$file    = '' !== $relpath ? realpath( WP_CONTENT_DIR . '/' . $relpath ) : false;

		// Only files inside the storage folder, whatever the meta says.
		if ( $storage && $file && is_file( $file ) && 0 === strpos( $file, $storage . DIRECTORY_SEPARATOR ) ) {
			wp_delete_file( $file );
		}

		wp_delete_post( $doc_id, true );
	}

	// Settings and state.
	$options = array(
		'dv_list_page',
		'dv_view_page',
		'dv_profile_page',
		'dv_subscriber_redirect_page',
		'dv_template_assignment',
		'dv_template_comment',
		'dv_login_form_before_content',
		'dv_login_form_after_content',
		'dv_color_scheme',
		'dv_enable_embedded_login',
		'dv_enable_recaptcha',
		'dv_enable_unstyled_mode',
		'dv_hide_admin_bar_subscribers',
		'dv_login_captcha',
		'dv_notification_email',
		'dv_notify_admin_on_comment',
		'dv_notify_on_assignment',
		'dv_notify_user_on_comment',
		'dv_recaptcha_secret_key',
		'dv_recaptcha_site_key',
		'dv_show_login_message',
		'dv_turnstile_secret_key',
		'dv_turnstile_site_key',
		'dv_turnstile_theme',
		'dv_private_docs_last_sweep',
		'dv_private_docs_protection_fs',
		'dv_private_docs_protection_http',
		'dv_delete_data_on_uninstall',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Per-language copies of the language-aware options (e.g. dv_list_page_en).
	$per_language = array(
		'dv_list_page',
		'dv_view_page',
		'dv_profile_page',
		'dv_subscriber_redirect_page',
		'dv_template_assignment',
		'dv_template_comment',
		'dv_login_form_before_content',
		'dv_login_form_after_content',
	);

	foreach ( $per_language as $base ) {
		$names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off cleanup
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $base . '_' ) . '%'
			)
		);

		foreach ( $names as $name ) {
			// Only a language code may follow the base name, never another option.
			if ( preg_match( '/^' . preg_quote( $base, '/' ) . '_[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/i', $name ) ) {
				delete_option( $name );
			}
		}
	}

	foreach ( array( 'dv_private_docs_protection_checked', 'dv_private_docs_probe_checked', 'dv_private_docs_sweep_done' ) as $transient ) {
		delete_transient( $transient );
	}
	return true;
}

dv_private_docs_uninstall_update_channel();

// The storage folder and the users are shared by all the sites of a network:
// the folder (with whatever is left in it: recovered strays, protection files)
// and the profile fields go only when every site asked for its data to be deleted.
$dv_private_docs_delete_storage = true;

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $dv_private_docs_site_id ) {
		switch_to_blog( $dv_private_docs_site_id );
		if ( ! dv_private_docs_uninstall_site() ) {
			$dv_private_docs_delete_storage = false;
		}
		restore_current_blog();
	}
} else {
	$dv_private_docs_delete_storage = dv_private_docs_uninstall_site();
}

// Users are shared by the network too: their profile fields go on the same terms.
if ( $dv_private_docs_delete_storage ) {
	foreach ( array( 'dv_organization', 'dv_phone', 'dv_pending_email' ) as $dv_private_docs_meta_key ) {
		delete_metadata( 'user', 0, $dv_private_docs_meta_key, '', true );
	}

	if ( dv_private_docs_uninstall_storage() ) {
		dv_private_docs_uninstall_rmdir( dv_private_docs_uninstall_storage() );
	}
}
