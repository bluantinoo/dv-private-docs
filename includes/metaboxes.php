<?php
/**
 * Admin metaboxes for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register metaboxes for private documents
 */
function dv_register_metaboxes() {
	add_meta_box(
		'dv_assign_user_metabox',
		__( 'Assign to User', 'dv-private-docs' ),
		'dv_render_assign_user_metabox',
		'dv_private_doc',
		'side',
		'high'
	);

	add_meta_box(
		'dv_document_file_metabox',
		__( 'Document File', 'dv-private-docs' ),
		'dv_render_document_file_metabox',
		'dv_private_doc',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'dv_register_metaboxes' );

/**
 * Render the "Assign to User" metabox
 *
 * @param WP_Post $post Current post object
 */
function dv_render_assign_user_metabox( $post ) {
	// Nonce field for security
	wp_nonce_field( 'dv_save_document_meta', 'dv_document_meta_nonce' );

	// Get current assigned user
	$assigned_user_id = get_post_meta( $post->ID, 'dv_assigned_user_id', true );

	// Get all users
	$users = get_users( array(
		'orderby' => 'display_name',
		'order'   => 'ASC',
	) );

	?>
	<p>
		<label for="dv_assigned_user_id">
			<?php esc_html_e( 'Select user:', 'dv-private-docs' ); ?>
		</label>
	</p>
	<select name="dv_assigned_user_id" id="dv_assigned_user_id" class="widefat">
		<option value=""><?php esc_html_e( '— Select a user —', 'dv-private-docs' ); ?></option>
		<?php foreach ( $users as $user ) : ?>
			<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $assigned_user_id, $user->ID ); ?>>
				<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( 'This document will be visible only to the selected user.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Render the "Document File" metabox
 *
 * @param WP_Post $post Current post object
 */
function dv_render_document_file_metabox( $post ) {
	$private_relpath = get_post_meta( $post->ID, 'dv_private_relpath', true );
	$original_filename = get_post_meta( $post->ID, 'dv_original_filename', true );

	// Check if private file exists
	$private_file_exists = false;
	$private_file_size = '';
	if ( $private_relpath ) {
		$private_full_path = WP_CONTENT_DIR . '/' . $private_relpath;
		if ( file_exists( $private_full_path ) ) {
			$private_file_exists = true;
			$private_file_size = dv_get_file_size( $private_full_path );
		}
	}

	?>
	<div id="dv-file-uploader" style="margin-bottom: 15px;">
		<input type="hidden" name="dv_attachment_id" id="dv_attachment_id" value="" />

		<?php if ( $private_file_exists ) : ?>
			<?php
			// Generate download URL for admin preview
			$download_url = add_query_arg(
				array(
					'action'   => 'dv_private_doc_download',
					'doc'      => $post->ID,
					'_wpnonce' => wp_create_nonce( 'dv_download_' . $post->ID ),
				),
				admin_url( 'admin-post.php' )
			);
			?>
			<div id="dv-file-preview" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
				<p style="margin: 0 0 10px 0;">
					<strong><?php esc_html_e( 'Document file:', 'dv-private-docs' ); ?></strong><br>
					<span style="font-family: monospace; color: #0073aa;"><?php echo esc_html( $original_filename ); ?></span>
				</p>
				<p style="margin: 0 0 10px 0; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #2c3338;">
					<strong><?php esc_html_e( '✓ Private storage:', 'dv-private-docs' ); ?></strong>
					<?php echo esc_html( $private_file_size ); ?>
				</p>
				<p style="margin: 0 0 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; font-size: 12px; color: #155724;">
					<?php esc_html_e( 'This file is stored securely and is not accessible from the Media Library.', 'dv-private-docs' ); ?>
				</p>

				<p style="margin: 15px 0 0 0;">
					<a href="<?php echo esc_url( $download_url ); ?>" class="button button-secondary" target="_blank">
						<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span>
						<?php esc_html_e( 'Download / Preview', 'dv-private-docs' ); ?>
					</a>
					<button type="button" class="button" id="dv_change_file_btn">
						<?php esc_html_e( 'Change File', 'dv-private-docs' ); ?>
					</button>
					<button type="button" class="button" id="dv_remove_file_btn" style="color: #d63638;">
						<?php esc_html_e( 'Remove File', 'dv-private-docs' ); ?>
					</button>
				</p>
			</div>
		<?php else : ?>
			<p>
				<button type="button" class="button button-primary button-large" id="dv_upload_file_btn">
					<?php esc_html_e( 'Upload Document File', 'dv-private-docs' ); ?>
				</button>
			</p>
			<p class="description">
				<?php esc_html_e( 'Select the file that users will download. Supported formats: PDF, DOC, DOCX, XLS, XLSX, etc.', 'dv-private-docs' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var dvMediaFrame;
		var dvHasFile = <?php echo dv_document_has_file( $post->ID ) ? 'true' : 'false'; ?>;

		// Upload/Change file
		$('#dv_upload_file_btn, #dv_change_file_btn').on('click', function(e) {
			e.preventDefault();

			if (dvMediaFrame) {
				dvMediaFrame.open();
				return;
			}

			dvMediaFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select Document File', 'dv-private-docs' ) ); ?>',
				button: {
					text: '<?php echo esc_js( __( 'Use this file', 'dv-private-docs' ) ); ?>'
				},
				multiple: false,
				library: {
					uploadedTo: <?php echo absint( $post->ID ); ?>
				}
			});

			dvMediaFrame.on('select', function() {
				var attachment = dvMediaFrame.state().get('selection').first().toJSON();

				// Show loading state immediately
				var loadingHtml = '<div id="dv-file-preview" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">' +
					'<p style="margin: 0;">' +
					'<span class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></span>' +
					'<strong><?php echo esc_js( __( 'Securing file...', 'dv-private-docs' ) ); ?></strong><br>' +
					'<small style="color: #666;"><?php echo esc_js( __( 'Moving file to private storage and removing from media library...', 'dv-private-docs' ) ); ?></small>' +
					'</p>' +
					'</div>';
				$('#dv-file-uploader').html('<input type="hidden" name="dv_attachment_id" id="dv_attachment_id" value="" />' + loadingHtml);

				// IMMEDIATELY move file to private storage via AJAX
				// This ensures file is secured regardless of whether user saves or not
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dv_secure_file_immediately',
						post_id: <?php echo absint( $post->ID ); ?>,
						attachment_id: attachment.id,
						nonce: '<?php echo esc_js( wp_create_nonce( 'dv_secure_file_' . $post->ID ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							// File secured successfully - update state
							dvHasFile = true;

							var successHtml = '<div id="dv-file-preview" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">' +
								'<p style="margin: 0 0 10px 0;">' +
								'<strong><?php echo esc_js( __( 'Document file:', 'dv-private-docs' ) ); ?></strong><br>' +
								'<span style="font-family: monospace; color: #0073aa;">' + response.data.filename + '</span>' +
								'</p>' +
								'<p style="margin: 0 0 10px 0; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #2c3338;">' +
								'<strong><?php echo esc_js( __( '✓ Private storage:', 'dv-private-docs' ) ); ?></strong> ' + response.data.size +
								'</p>' +
								'<p style="margin: 0 0 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; font-size: 12px; color: #155724;">' +
								'<?php echo esc_js( __( '✓ File secured! Removed from Media Library and stored in private directory.', 'dv-private-docs' ) ); ?>' +
								'</p>' +
								'<p style="margin: 15px 0 0 0;">' +
								'<button type="button" class="button" id="dv_change_file_btn"><?php echo esc_js( __( 'Change File', 'dv-private-docs' ) ); ?></button> ' +
								'<button type="button" class="button" id="dv_remove_file_btn" style="color: #d63638;"><?php echo esc_js( __( 'Remove File', 'dv-private-docs' ) ); ?></button>' +
								'</p>' +
								'</div>';
							$('#dv-file-uploader').html('<input type="hidden" name="dv_attachment_id" id="dv_attachment_id" value="" />' + successHtml);
						} else {
							// Error
							var errorHtml = '<div id="dv-file-preview" style="background: #fcf8e3; border: 1px solid #d63638; padding: 15px; border-radius: 4px;">' +
								'<p style="margin: 0; color: #d63638;">' +
								'<strong>✗ <?php echo esc_js( __( 'Error:', 'dv-private-docs' ) ); ?></strong> ' + response.data +
								'</p>' +
								'<p style="margin: 10px 0 0 0;">' +
								'<button type="button" class="button" id="dv_upload_file_btn"><?php echo esc_js( __( 'Try Again', 'dv-private-docs' ) ); ?></button>' +
								'</p>' +
								'</div>';
							$('#dv-file-uploader').html('<input type="hidden" name="dv_attachment_id" id="dv_attachment_id" value="" />' + errorHtml);
						}
					},
					error: function() {
						var errorHtml = '<div id="dv-file-preview" style="background: #fcf8e3; border: 1px solid #d63638; padding: 15px; border-radius: 4px;">' +
							'<p style="margin: 0; color: #d63638;">' +
							'<strong>✗ <?php echo esc_js( __( 'Connection error. Please try again.', 'dv-private-docs' ) ); ?></strong>' +
							'</p>' +
							'<p style="margin: 10px 0 0 0;">' +
							'<button type="button" class="button" id="dv_upload_file_btn"><?php echo esc_js( __( 'Try Again', 'dv-private-docs' ) ); ?></button>' +
							'</p>' +
							'</div>';
						$('#dv-file-uploader').html('<input type="hidden" name="dv_attachment_id" id="dv_attachment_id" value="" />' + errorHtml);
					}
				});
			});

			dvMediaFrame.open();
		});

		// Remove file
		$(document).on('click', '#dv_remove_file_btn', function(e) {
			e.preventDefault();
			if (confirm('<?php echo esc_js( __( 'Are you sure you want to remove this file?', 'dv-private-docs' ) ); ?>')) {
				// Update state
				dvHasFile = false;

				var emptyHtml = '<input type="hidden" name="dv_attachment_id" id="dv_attachment_id" value="" />' +
					'<p><button type="button" class="button button-primary button-large" id="dv_upload_file_btn">' +
					'<?php echo esc_js( __( 'Upload Document File', 'dv-private-docs' ) ); ?></button></p>' +
					'<p class="description"><?php echo esc_js( __( 'Select the file that users will download. Supported formats: PDF, DOC, DOCX, XLS, XLSX, etc.', 'dv-private-docs' ) ); ?></p>';

				$('#dv-file-uploader').html(emptyHtml);
			}
		});

		// Validate before publish - show immediate feedback
		$('#publish').on('click', function(e) {
			// Check if we're trying to publish (not save draft)
			var originalStatus = $('#original_post_status').val();
			var postStatus = $('#post_status').val();

			// Only validate when publishing (not when saving draft)
			if (originalStatus === 'draft' || postStatus === 'publish') {
				var hasUser = $('#dv_assigned_user_id').val() && $('#dv_assigned_user_id').val() !== '';
				var hasFile = dvHasFile;

				var errors = [];

				if (!hasUser) {
					errors.push('<?php echo esc_js( __( 'You must assign this document to a user before publishing.', 'dv-private-docs' ) ); ?>');
				}

				if (!hasFile) {
					errors.push('<?php echo esc_js( __( 'You must upload a document file before publishing.', 'dv-private-docs' ) ); ?>');
				}

				if (errors.length > 0) {
					e.preventDefault();

					// Show warning notice at top of page
					var errorHtml = '<div class="notice notice-warning is-dismissible" style="margin: 15px 0;">' +
						'<p><strong><?php echo esc_js( __( 'Cannot publish document:', 'dv-private-docs' ) ); ?></strong></p>' +
						'<ul style="margin: 10px 0; padding-left: 25px;">';

					$.each(errors, function(i, error) {
						errorHtml += '<li>' + error + '</li>';
					});

					errorHtml += '</ul>' +
						'<p><?php echo esc_js( __( 'The document will be saved as draft. Complete all requirements to publish.', 'dv-private-docs' ) ); ?></p>' +
						'</div>';

					// Remove previous notices
					$('.wrap h1').first().after(errorHtml);

					// Scroll to top to see notice
					$('html, body').animate({ scrollTop: 0 }, 300);

					// Re-enable publish button but change to "Save Draft"
					$('#publish').val('<?php echo esc_js( __( 'Save Draft', 'dv-private-docs' ) ); ?>');
					$('#post_status').val('draft');

					// Auto-dismiss after 10 seconds
					setTimeout(function() {
						$('.notice-warning.is-dismissible').fadeOut();
					}, 10000);
				}
			}
		});
	});
	</script>
	<?php
}

/**
 * Save document metadata
 * Note: File handling is done via AJAX (dv_ajax_secure_file_immediately)
 *
 * @param int $post_id Post ID
 */
function dv_save_document_meta( $post_id ) {
	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Get post to verify type
	$post = get_post( $post_id );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Verify nonce if present
	// If $_POST is empty (REST API save), we skip this - WordPress already validated
	if ( ! empty( $_POST ) ) {
		$nonce_valid = false;

		// Check our custom nonce
		if ( isset( $_POST['dv_document_meta_nonce'] ) ) {
			$nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dv_document_meta_nonce'] ) ), 'dv_save_document_meta' );
		}

		// Check WordPress update post nonce
		if ( ! $nonce_valid && isset( $_POST['_wpnonce'] ) ) {
			$nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-post_' . $post_id );
		}

		if ( ! $nonce_valid ) {
			return;
		}
	}

	// Save assigned user ID (only if present in POST)
	if ( isset( $_POST['dv_assigned_user_id'] ) ) {
		$assigned_user_id = absint( $_POST['dv_assigned_user_id'] );
		update_post_meta( $post_id, 'dv_assigned_user_id', $assigned_user_id );
	}

	// Note: File is handled immediately via AJAX when selected
	// No file handling needed here
}
add_action( 'save_post_dv_private_doc', 'dv_save_document_meta', 5 );

/**
 * Copy attachment file to private storage and delete from media library
 * SECURITY CRITICAL: Always deletes attachment from media library
 *
 * @param int $post_id Document post ID
 * @param int $attachment_id Attachment ID
 */
function dv_copy_file_to_private_storage( $post_id, $attachment_id ) {
	$source_file = get_attached_file( $attachment_id );

	if ( ! $source_file || ! file_exists( $source_file ) ) {
		// Source file doesn't exist, delete attachment anyway for safety
		dv_delete_attachment_with_translations( $attachment_id );
		delete_post_meta( $post_id, 'dv_attachment_id' );
		return;
	}

	// Store original filename before deletion
	$original_filename = dv_get_original_filename( $attachment_id );

	// Generate unique filename
	$unique_filename = dv_generate_private_filename( $post_id, $attachment_id, $original_filename );

	// Build destination path
	$private_dir = WP_CONTENT_DIR . '/' . DV_PRIVATE_DOCS_DIR;
	$destination_file = $private_dir . '/' . $unique_filename;

	// Ensure the directory exists AND is protected before writing into it.
	// If protection cannot be established we refuse to store the file: the
	// attachment is deleted below in any case, so nothing stays reachable.
	$copy_success = dv_ensure_private_dir_protection()
		? copy( $source_file, $destination_file )
		: false;

	if ( $copy_success ) {
		// Save relative path
		$relpath = DV_PRIVATE_DOCS_DIR . '/' . $unique_filename;
		update_post_meta( $post_id, 'dv_private_relpath', $relpath );

		// Store original filename for downloads
		update_post_meta( $post_id, 'dv_original_filename', $original_filename );

		// Delete old private file if exists
		$old_relpath = get_post_meta( $post_id, 'dv_private_relpath', true );
		if ( $old_relpath && $old_relpath !== $relpath ) {
			$old_full_path = WP_CONTENT_DIR . '/' . $old_relpath;
			if ( file_exists( $old_full_path ) ) {
				wp_delete_file( $old_full_path );
			}
		}
	} else {
	}

	// CRITICAL SECURITY: ALWAYS delete the attachment from media library
	// Even if copy failed, we must remove public access
	// Better to lose the file than leave it publicly accessible
	dv_delete_attachment_with_translations( $attachment_id );

	// Clear the attachment_id meta since file is now deleted
	delete_post_meta( $post_id, 'dv_attachment_id' );
}

/**
 * Validate document before publishing
 * Force draft if user or file is missing
 *
 * @param array $data Post data
 * @param array $postarr Raw post data
 * @return array Modified post data
 */
function dv_validate_document_publish( $data, $postarr ) {
	// Only for our CPT
	if ( 'dv_private_doc' !== $data['post_type'] ) {
		return $data;
	}

	// Only when trying to publish
	if ( 'publish' !== $data['post_status'] ) {
		return $data;
	}

	// Skip for autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $data;
	}

	$post_id = isset( $postarr['ID'] ) ? $postarr['ID'] : 0;

	// Check assigned user
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress in edit_post() before wp_insert_post_data filter
	$assigned_user_id = isset( $_POST['dv_assigned_user_id'] ) ? absint( $_POST['dv_assigned_user_id'] ) : get_post_meta( $post_id, 'dv_assigned_user_id', true );

	// Check file - either new attachment OR existing private file
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress in edit_post() before wp_insert_post_data filter
	$new_attachment_id = isset( $_POST['dv_attachment_id'] ) ? absint( $_POST['dv_attachment_id'] ) : 0;
	$existing_private_file = get_post_meta( $post_id, 'dv_private_relpath', true );
	$has_file = $new_attachment_id || $existing_private_file;

	$errors = array();

	if ( empty( $assigned_user_id ) ) {
		$errors[] = __( 'You must assign this document to a user before publishing.', 'dv-private-docs' );
	}

	if ( ! $has_file ) {
		$errors[] = __( 'You must upload a document file before publishing.', 'dv-private-docs' );
	}

	// If validation fails, force draft status
	if ( ! empty( $errors ) ) {
		$data['post_status'] = 'draft';

		// Store errors for display
		set_transient( 'dv_publish_errors_' . get_current_user_id(), $errors, 45 );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'dv_validate_document_publish', 10, 2 );

/**
 * Display validation errors and warnings as admin notices
 */
function dv_display_validation_errors() {
	$screen = get_current_screen();

	if ( ! $screen || 'dv_private_doc' !== $screen->post_type ) {
		return;
	}

	// Show publish errors
	$errors = get_transient( 'dv_publish_errors_' . get_current_user_id() );

	if ( $errors ) {
		delete_transient( 'dv_publish_errors_' . get_current_user_id() );

		echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Document could not be published:', 'dv-private-docs' ) . '</strong></p><ul>';
		foreach ( $errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
	}

	// Show draft warnings
	$warnings = get_transient( 'dv_draft_warnings_' . get_current_user_id() );

	if ( $warnings ) {
		delete_transient( 'dv_draft_warnings_' . get_current_user_id() );

		echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Document saved as draft:', 'dv-private-docs' ) . '</strong></p><ul>';
		foreach ( $warnings as $warning ) {
			echo '<li>' . esc_html( $warning ) . '</li>';
		}
		echo '</ul><p>' . esc_html__( 'This document cannot be published until all requirements are met.', 'dv-private-docs' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'dv_display_validation_errors' );

/**
 * Check draft documents and show warnings for missing data
 * Only shows warnings on NEW draft creation, not on every save
 *
 * @param int $post_id Post ID
 */
function dv_check_draft_warnings( $post_id ) {
	// Only for our CPT
	$post = get_post( $post_id );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Only for draft status
	if ( 'draft' !== $post->post_status ) {
		return;
	}

	// Only show warnings if this is a NEW draft or if publish attempt failed
	// Check if there are already publish errors set (means publish attempt failed)
	$has_publish_errors = get_transient( 'dv_publish_errors_' . get_current_user_id() );

	// Check if this is a brand new post (transition from auto-draft)
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified in dv_save_document_meta() at priority 5
	$is_new_draft = isset( $_POST['original_post_status'] ) && 'auto-draft' === $_POST['original_post_status'];

	// Don't show warnings on existing draft updates (only on new creation)
	if ( ! $has_publish_errors && ! $is_new_draft ) {
		return;
	}

	// Check for missing data
	$assigned_user_id = get_post_meta( $post_id, 'dv_assigned_user_id', true );
	$has_file = dv_document_has_file( $post_id );

	$warnings = array();

	if ( empty( $assigned_user_id ) ) {
		$warnings[] = __( 'No user assigned to this document.', 'dv-private-docs' );
	}

	if ( ! $has_file ) {
		$warnings[] = __( 'No document file uploaded.', 'dv-private-docs' );
	}

	// Store warnings for display
	if ( ! empty( $warnings ) ) {
		set_transient( 'dv_draft_warnings_' . get_current_user_id(), $warnings, 45 );
	}
}
add_action( 'save_post_dv_private_doc', 'dv_check_draft_warnings', 20 );

/**
 * Delete private file when document is permanently deleted
 * This runs when emptying trash or directly deleting
 *
 * @param int $post_id Post ID being deleted
 */
function dv_delete_private_file_on_post_delete( $post_id ) {
	// Verify it's our post type
	$post = get_post( $post_id );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Get private file path
	$private_relpath = get_post_meta( $post_id, 'dv_private_relpath', true );

	if ( ! $private_relpath ) {
		return; // No file to delete
	}

	// Build full path
	$private_file = WP_CONTENT_DIR . '/' . $private_relpath;

	// Delete file if exists
	if ( file_exists( $private_file ) ) {
		wp_delete_file( $private_file );
	}
}
add_action( 'before_delete_post', 'dv_delete_private_file_on_post_delete' );

/**
 * AJAX handler: Immediately secure file when selected from media library
 * This executes BEFORE the post is saved, ensuring file is always secured
 */
function dv_ajax_secure_file_immediately() {
	// Get parameters
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	// Verify nonce
	if ( ! wp_verify_nonce( $nonce, 'dv_secure_file_' . $post_id ) ) {
		wp_send_json_error( __( 'Security check failed.', 'dv-private-docs' ) );
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( __( 'You do not have permission to edit this document.', 'dv-private-docs' ) );
	}

	// Verify post exists and is correct type
	$post = get_post( $post_id );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		wp_send_json_error( __( 'Invalid document.', 'dv-private-docs' ) );
	}

	// Verify attachment exists
	if ( ! $attachment_id || ! get_post( $attachment_id ) ) {
		wp_send_json_error( __( 'Invalid file.', 'dv-private-docs' ) );
	}

	// Get source file
	$source_file = get_attached_file( $attachment_id );

	if ( ! $source_file || ! file_exists( $source_file ) ) {
		// Source file doesn't exist, delete attachment anyway for safety
		dv_delete_attachment_with_translations( $attachment_id );
		wp_send_json_error( __( 'Source file not found.', 'dv-private-docs' ) );
	}

	// Store original filename before deletion
	$original_filename = dv_get_original_filename( $attachment_id );

	// Generate unique filename
	$unique_filename = dv_generate_private_filename( $post_id, $attachment_id, $original_filename );

	// Build destination path
	$private_dir = WP_CONTENT_DIR . '/' . DV_PRIVATE_DOCS_DIR;
	$destination_file = $private_dir . '/' . $unique_filename;

	// Ensure the directory exists AND is protected before writing into it
	if ( ! dv_ensure_private_dir_protection() ) {
		dv_delete_attachment_with_translations( $attachment_id );
		wp_send_json_error( __( 'The secure storage directory is not protected. The file was not stored, to avoid leaving it publicly downloadable.', 'dv-private-docs' ) );
	}

	// Copy file to private storage
	$copy_success = copy( $source_file, $destination_file );

	if ( ! $copy_success ) {
		// Copy failed - delete attachment and return error
		dv_delete_attachment_with_translations( $attachment_id );
		wp_send_json_error( __( 'Failed to copy file to secure storage.', 'dv-private-docs' ) );
	}

	// Save metadata
	$relpath = DV_PRIVATE_DOCS_DIR . '/' . $unique_filename;
	update_post_meta( $post_id, 'dv_private_relpath', $relpath );
	update_post_meta( $post_id, 'dv_original_filename', $original_filename );

	// Delete old private file if exists
	$old_relpath = get_post_meta( $post_id, 'dv_private_relpath', true );
	if ( $old_relpath && $old_relpath !== $relpath ) {
		$old_full_path = WP_CONTENT_DIR . '/' . $old_relpath;
		if ( file_exists( $old_full_path ) ) {
			wp_delete_file( $old_full_path );
		}
	}

	// CRITICAL SECURITY: Delete attachment from media library
	// This ensures the file is NEVER publicly accessible
	dv_delete_attachment_with_translations( $attachment_id );

	// Return success with file info
	$file_size = dv_get_file_size( $destination_file );

	wp_send_json_success(
		array(
			'filename' => $original_filename,
			'size'     => $file_size,
			'message'  => __( 'File secured successfully!', 'dv-private-docs' ),
		)
	);
}
add_action( 'wp_ajax_dv_secure_file_immediately', 'dv_ajax_secure_file_immediately' );
