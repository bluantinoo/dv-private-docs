<?php
/**
 * Admin columns customization for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom columns to the admin list
 *
 * @param array $columns Existing columns
 * @return array Modified columns
 */
function dv_add_admin_columns( $columns ) {
	// Insert new columns after checkbox
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;

		// After checkbox, add thumbnail
		if ( 'cb' === $key ) {
			$new_columns['dv_thumbnail'] = __( 'Thumbnail', 'dv-private-docs' );
		}

		// After title, add assigned user
		if ( 'title' === $key ) {
			$new_columns['dv_assigned_user'] = __( 'Assigned User', 'dv-private-docs' );
		}
	}

	// Add file status and downloads before date
	$new_columns['dv_file_status'] = __( 'File Status', 'dv-private-docs' );
	$new_columns['dv_downloads'] = __( 'Downloads', 'dv-private-docs' );

	return $new_columns;
}
add_filter( 'manage_dv_private_doc_posts_columns', 'dv_add_admin_columns' );

/**
 * Render custom column content
 *
 * @param string $column Column name
 * @param int    $post_id Post ID
 */
function dv_render_admin_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'dv_thumbnail':
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, array( 50, 50 ), array( 'style' => 'border-radius: 4px;' ) );
			} else {
				echo '<span style="color: #999;">—</span>';
			}
			break;

		case 'dv_assigned_user':
			$user_id = get_post_meta( $post_id, 'dv_assigned_user_id', true );

			if ( $user_id ) {
				$user = get_user_by( 'id', $user_id );

				if ( $user ) {
					$edit_link = get_edit_user_link( $user_id );
					echo '<strong><a href="' . esc_url( $edit_link ) . '">' . esc_html( $user->display_name ) . '</a></strong><br>';
					echo '<small style="color: #666;">' . esc_html( $user->user_email ) . '</small>';
				} else {
					echo '<span style="color: #d63638;">' . esc_html__( 'User not found', 'dv-private-docs' ) . '</span>';
				}
			} else {
				echo '<span style="color: #d63638;">' . esc_html__( 'Not assigned', 'dv-private-docs' ) . '</span>';
			}
			break;

		case 'dv_file_status':
			$private_relpath = get_post_meta( $post_id, 'dv_private_relpath', true );

			if ( ! $private_relpath ) {
				echo '<span style="color: #d63638;">✗ ' . esc_html__( 'No file', 'dv-private-docs' ) . '</span>';
			} else {
				// Check if private file exists
				$private_file = WP_CONTENT_DIR . '/' . $private_relpath;

				if ( file_exists( $private_file ) ) {
					$file_size = dv_get_file_size( $private_file );
					echo '<span style="color: #46b450;">✓ ' . esc_html__( 'Ready', 'dv-private-docs' ) . '</span><br>';
					echo '<small style="color: #666;">' . esc_html( $file_size ) . '</small>';
				} else {
					echo '<span style="color: #d63638;">✗ ' . esc_html__( 'File missing', 'dv-private-docs' ) . '</span>';
				}
			}
			break;

		case 'dv_downloads':
			$download_count = get_post_meta( $post_id, 'dv_download_count', true );
			$download_count = $download_count ? absint( $download_count ) : 0;

			if ( $download_count > 0 ) {
				echo '<span style="color: #2271b1;">📥 ' . esc_html( number_format_i18n( $download_count ) ) . '</span>';
			} else {
				echo '<span style="color: #999;">—</span>';
			}
			break;
	}
}
add_action( 'manage_dv_private_doc_posts_custom_column', 'dv_render_admin_columns', 10, 2 );

/**
 * Make custom columns sortable
 *
 * @param array $columns Sortable columns
 * @return array Modified sortable columns
 */
function dv_sortable_admin_columns( $columns ) {
	$columns['dv_assigned_user'] = 'dv_assigned_user';
	return $columns;
}
add_filter( 'manage_edit-dv_private_doc_sortable_columns', 'dv_sortable_admin_columns' );

/**
 * Handle sorting by assigned user
 *
 * @param WP_Query $query Current query
 */
function dv_admin_columns_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'dv_assigned_user' === $orderby ) {
		$query->set( 'meta_key', 'dv_assigned_user_id' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'dv_admin_columns_orderby' );
