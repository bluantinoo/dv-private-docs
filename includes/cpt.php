<?php
/**
 * Custom Post Type registration for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Private Document custom post type
 */
function dv_register_private_doc_cpt() {
	$labels = array(
		'name'                  => _x( 'Private Documents', 'Post Type General Name', 'dv-private-docs' ),
		'singular_name'         => _x( 'Private Document', 'Post Type Singular Name', 'dv-private-docs' ),
		'menu_name'             => __( 'Private Documents', 'dv-private-docs' ),
		'name_admin_bar'        => __( 'Private Document', 'dv-private-docs' ),
		'archives'              => __( 'Document Archives', 'dv-private-docs' ),
		'attributes'            => __( 'Document Attributes', 'dv-private-docs' ),
		'parent_item_colon'     => __( 'Parent Document:', 'dv-private-docs' ),
		'all_items'             => __( 'All Documents', 'dv-private-docs' ),
		'add_new_item'          => __( 'Add New Document', 'dv-private-docs' ),
		'add_new'               => __( 'Add New', 'dv-private-docs' ),
		'new_item'              => __( 'New Document', 'dv-private-docs' ),
		'edit_item'             => __( 'Edit Document', 'dv-private-docs' ),
		'update_item'           => __( 'Update Document', 'dv-private-docs' ),
		'view_item'             => __( 'View Document', 'dv-private-docs' ),
		'view_items'            => __( 'View Documents', 'dv-private-docs' ),
		'search_items'          => __( 'Search Document', 'dv-private-docs' ),
		'not_found'             => __( 'Not found', 'dv-private-docs' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'dv-private-docs' ),
		'featured_image'        => __( 'Document Thumbnail', 'dv-private-docs' ),
		'set_featured_image'    => __( 'Set document thumbnail', 'dv-private-docs' ),
		'remove_featured_image' => __( 'Remove document thumbnail', 'dv-private-docs' ),
		'use_featured_image'    => __( 'Use as document thumbnail', 'dv-private-docs' ),
		'insert_into_item'      => __( 'Insert into document', 'dv-private-docs' ),
		'uploaded_to_this_item' => __( 'Uploaded to this document', 'dv-private-docs' ),
		'items_list'            => __( 'Documents list', 'dv-private-docs' ),
		'items_list_navigation' => __( 'Documents list navigation', 'dv-private-docs' ),
		'filter_items_list'     => __( 'Filter documents list', 'dv-private-docs' ),
	);

	$args = array(
		'label'               => __( 'Private Document', 'dv-private-docs' ),
		'description'         => __( 'Private documents assigned to individual users', 'dv-private-docs' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'comments', 'thumbnail' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 1,
		'menu_icon'           => 'dashicons-media-document',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'rewrite'             => false,
		'capability_type'     => 'post',
		'show_in_rest'        => false,
	);

	register_post_type( 'dv_private_doc', $args );
}
add_action( 'init', 'dv_register_private_doc_cpt' );

/**
 * Custom admin messages for private documents
 *
 * @param array $messages Existing post messages
 * @return array Modified messages
 */
function dv_private_doc_updated_messages( $messages ) {
	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	$messages['dv_private_doc'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Document updated.', 'dv-private-docs' ),
		2  => __( 'Custom field updated.', 'dv-private-docs' ),
		3  => __( 'Custom field deleted.', 'dv-private-docs' ),
		4  => __( 'Document updated.', 'dv-private-docs' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Document restored to revision from %s', 'dv-private-docs' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress admin message context, nonce verified by core before this filter
		6  => __( 'Document published.', 'dv-private-docs' ),
		7  => __( 'Document saved.', 'dv-private-docs' ),
		8  => __( 'Document submitted.', 'dv-private-docs' ),
		9  => sprintf(
			/* translators: %s: scheduled date */
			__( 'Document scheduled for: <strong>%s</strong>.', 'dv-private-docs' ),
			date_i18n( __( 'M j, Y @ G:i', 'dv-private-docs' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Document draft updated.', 'dv-private-docs' )
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'dv_private_doc_updated_messages' );

/**
 * Custom bulk messages for private documents
 *
 * @param array $bulk_messages Arrays of messages, each keyed by the corresponding post type
 * @param array $bulk_counts Array of item counts for each message
 * @return array Modified bulk messages
 */
function dv_private_doc_bulk_updated_messages( $bulk_messages, $bulk_counts ) {
	$bulk_messages['dv_private_doc'] = array(
		/* translators: %s: Number of documents */
		'updated'   => _n( '%s document updated.', '%s documents updated.', $bulk_counts['updated'], 'dv-private-docs' ),
		/* translators: %s: Number of documents */
		'locked'    => _n( '%s document not updated, somebody is editing it.', '%s documents not updated, somebody is editing them.', $bulk_counts['locked'], 'dv-private-docs' ),
		/* translators: %s: Number of documents */
		'deleted'   => _n( '%s document permanently deleted.', '%s documents permanently deleted.', $bulk_counts['deleted'], 'dv-private-docs' ),
		/* translators: %s: Number of documents */
		'trashed'   => _n( '%s document moved to the Trash.', '%s documents moved to the Trash.', $bulk_counts['trashed'], 'dv-private-docs' ),
		/* translators: %s: Number of documents */
		'untrashed' => _n( '%s document restored from the Trash.', '%s documents restored from the Trash.', $bulk_counts['untrashed'], 'dv-private-docs' ),
	);

	return $bulk_messages;
}
add_filter( 'bulk_post_updated_messages', 'dv_private_doc_bulk_updated_messages', 10, 2 );

/**
 * Disable trackbacks and pingbacks by default for private documents
 *
 * @param array $data Post data
 * @param array $postarr Raw post data
 * @return array Modified post data
 */
function dv_disable_trackbacks( $data, $postarr ) {
	if ( 'dv_private_doc' === $data['post_type'] ) {
		$data['ping_status'] = 'closed';
		$data['comment_status'] = isset( $data['comment_status'] ) ? $data['comment_status'] : 'open';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'dv_disable_trackbacks', 5, 2 );
