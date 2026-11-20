<?php
/**
 * Making sure no file of a private document is left in the media library
 *
 * The plugin's whole guarantee is that a private document exists in exactly one
 * place, wp-content/private-docs/, behind a login. That guarantee held for the
 * attachment the plugin knew about and for nothing else.
 *
 * WPML duplicates every upload into the other active languages on the
 * add_attachment hook (sitepress-multilingual-cms, class-wpml-media-attachments
 * -duplication.php), which fires while the file is being uploaded, before this
 * plugin ever sees it. So by the time the document is saved there are already
 * two attachments and two physical files; the plugin secured one and left the
 * other sitting in wp-content/uploads/, publicly downloadable.
 *
 * WPML offers no filter to opt out per attachment, and the upload goes through
 * WordPress's own media handler, so it cannot be wrapped from here. The fix is
 * therefore after the fact, in two layers:
 *
 * 1. When deleting a secured attachment, delete its translations as well.
 * 2. A sweeper that periodically looks for any attachment still attached to a
 *    private document and removes it, whatever created it. This is the layer
 *    that matters: it does not depend on knowing how the stray got there, so it
 *    also covers a future WPML change, a different translation plugin, or a
 *    file attached by hand.
 *
 * Nothing is ever deleted outright: a stray whose content is not already in
 * private-docs is moved there first.
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IDs of an attachment's translations in other languages
 *
 * Uses WPML's public filters, which simply return null when WPML is not
 * installed, so this is safe on a monolingual site.
 *
 * @param int $attachment_id Attachment ID.
 * @return int[] Translation IDs, excluding the attachment itself.
 */
function dv_get_attachment_translation_ids( $attachment_id ) {
	$trid = apply_filters( 'wpml_element_trid', null, $attachment_id, 'post_attachment' );
	if ( ! $trid ) {
		return array();
	}

	$translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_attachment' );
	if ( ! is_array( $translations ) ) {
		return array();
	}

	$ids = array();
	foreach ( $translations as $translation ) {
		$id = isset( $translation->element_id ) ? (int) $translation->element_id : 0;
		if ( $id && $id !== (int) $attachment_id ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Delete an attachment together with every translation of it
 *
 * Drop-in replacement for wp_delete_attachment() everywhere this plugin removes
 * a file it has taken into private storage.
 *
 * @param int $attachment_id Attachment ID.
 * @return int Number of attachments actually deleted.
 */
function dv_delete_attachment_with_translations( $attachment_id ) {
	$deleted = 0;

	foreach ( dv_get_attachment_translation_ids( $attachment_id ) as $translation_id ) {
		if ( wp_delete_attachment( $translation_id, true ) ) {
			$deleted++;
		}
	}

	if ( wp_delete_attachment( $attachment_id, true ) ) {
		$deleted++;
	}

	return $deleted;
}

/**
 * Whether a file with this exact content is already in private storage
 *
 * Compared by content, not by name: the copies WPML makes are renamed with a
 * numeric suffix, so matching on filename would miss them.
 *
 * @param string $path Absolute path of the file to look for.
 * @return bool
 */
function dv_content_already_in_private_storage( $path ) {
	if ( ! is_readable( $path ) ) {
		return false;
	}

	$hash = md5_file( $path );
	if ( ! $hash ) {
		return false;
	}

	foreach ( glob( dv_private_docs_dir() . '/*' ) as $candidate ) {
		if ( ! is_file( $candidate ) ) {
			continue;
		}
		$name = basename( $candidate );
		if ( '.htaccess' === $name || 'index.php' === $name ) {
			continue;
		}
		if ( md5_file( $candidate ) === $hash ) {
			return true;
		}
	}

	return false;
}

/**
 * Move a stray file into private storage before its attachment is deleted
 *
 * Used when the content is not already secured, so that removing the stray can
 * never destroy the only copy. The file keeps a recognisable prefix because it
 * belongs to no document record and a human has to decide what to do with it.
 *
 * @param string $path Absolute path of the file.
 * @param int    $doc_id Private document the attachment was attached to.
 * @return bool
 */
function dv_rescue_stray_file( $path, $doc_id ) {
	if ( ! dv_ensure_private_dir_protection() || ! is_readable( $path ) ) {
		return false;
	}

	$target = sprintf(
		'%s/dvstray-%d-%s-%s',
		dv_private_docs_dir(),
		(int) $doc_id,
		substr( md5( $path . filemtime( $path ) ), 0, 12 ),
		sanitize_file_name( basename( $path ) )
	);

	return copy( $path, $target );
}

/**
 * Remove any attachment still attached to a private document
 *
 * This is the invariant the plugin has to hold: a file belonging to a private
 * document is never in the media library, because everything in the media
 * library is served publicly by the web server.
 *
 * @return array{removed:int,rescued:int,failed:int}
 */
function dv_sweep_stray_attachments() {
	global $wpdb;

	$result = array(
		'removed'  => 0,
		'rescued'  => 0,
		'failed'   => 0,
	);

	$strays = $wpdb->get_results(
		"SELECT a.ID AS attachment_id, d.ID AS doc_id
		 FROM {$wpdb->posts} a
		 INNER JOIN {$wpdb->posts} d
		         ON d.ID = a.post_parent AND d.post_type = 'dv_private_doc'
		 WHERE a.post_type = 'attachment'"
	);

	foreach ( $strays as $stray ) {
		$path = get_attached_file( $stray->attachment_id );

		if ( $path && file_exists( $path ) && ! dv_content_already_in_private_storage( $path ) ) {
			if ( dv_rescue_stray_file( $path, $stray->doc_id ) ) {
				$result['rescued']++;
			} else {
				// Could not secure the only copy, so leave it alone rather than
				// destroy it. The notice below tells someone to look.
				$result['failed']++;
				continue;
			}
		}

		if ( dv_delete_attachment_with_translations( $stray->attachment_id ) ) {
			$result['removed']++;
		} else {
			$result['failed']++;
		}
	}

	return $result;
}

/**
 * Run the sweeper periodically in the admin
 */
function dv_maybe_sweep_stray_attachments() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( get_transient( 'dv_private_docs_sweep_done' ) ) {
		return;
	}
	set_transient( 'dv_private_docs_sweep_done', 1, HOUR_IN_SECONDS );

	$result = dv_sweep_stray_attachments();

	if ( $result['removed'] || $result['failed'] ) {
		update_option( 'dv_private_docs_last_sweep', $result, false );
	}
}
add_action( 'admin_init', 'dv_maybe_sweep_stray_attachments', 20 );

/**
 * Tell the administrator when the sweeper had to intervene
 *
 * Silence would defeat the purpose: the point of the sweep is to surface a
 * failure of the upload path, not to paper over it.
 */
function dv_stray_attachments_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result = get_option( 'dv_private_docs_last_sweep' );
	if ( ! is_array( $result ) || ( empty( $result['removed'] ) && empty( $result['failed'] ) ) ) {
		return;
	}

	delete_option( 'dv_private_docs_last_sweep' );

	if ( ! empty( $result['failed'] ) ) {
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'DV Private Documents: a private document file is still in the media library.', 'dv-private-docs' ),
			esc_html(
				sprintf(
					/* translators: %d: number of files. */
					_n(
						'%d file could not be removed from the media library, which means it is publicly downloadable. Check the permissions on wp-content/private-docs/.',
						'%d files could not be removed from the media library, which means they are publicly downloadable. Check the permissions on wp-content/private-docs/.',
						$result['failed'],
						'dv-private-docs'
					),
					$result['failed']
				)
			)
		);
	}

	if ( ! empty( $result['removed'] ) ) {
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'DV Private Documents: copies of private documents were removed from the media library.', 'dv-private-docs' ),
			esc_html(
				sprintf(
					/* translators: 1: number removed, 2: number moved into private storage. */
					__( '%1$d publicly accessible copies were deleted, %2$d of which were moved into private storage first because they existed nowhere else. This normally means a translation plugin duplicated the upload.', 'dv-private-docs' ),
					$result['removed'],
					isset( $result['rescued'] ) ? $result['rescued'] : 0
				)
			)
		);
	}
}
add_action( 'admin_notices', 'dv_stray_attachments_notice' );
