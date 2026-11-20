<?php
/**
 * Comment protection for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require users to be logged in to comment on private documents
 *
 * @param bool $open Whether comments are open
 * @param int  $post_id Post ID
 * @return bool Modified comments open status
 */
function dv_require_login_for_comments( $open, $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return $open;
	}

	// Comments only allowed for logged-in users
	if ( ! is_user_logged_in() ) {
		return false;
	}

	return $open;
}
add_filter( 'comments_open', 'dv_require_login_for_comments', 10, 2 );

/**
 * Validate comment submission
 * Block comments from users who don't have access to the document
 *
 * @param int $comment_post_ID Post ID being commented on
 */
function dv_validate_comment_access( $comment_post_ID ) {
	$post = get_post( $comment_post_ID );

	// Only for our CPT
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Must be logged in
	if ( ! is_user_logged_in() ) {
		wp_die(
			esc_html__( 'You must be logged in to comment on this document.', 'dv-private-docs' ),
			esc_html__( 'Access Denied', 'dv-private-docs' ),
			array( 'response' => 403 )
		);
	}

	// Check document access
	if ( ! dv_user_can_access_doc( $comment_post_ID ) ) {
		wp_die(
			esc_html__( 'You do not have permission to comment on this document.', 'dv-private-docs' ),
			esc_html__( 'Access Denied', 'dv-private-docs' ),
			array( 'response' => 403 )
		);
	}
}
add_action( 'pre_comment_on_post', 'dv_validate_comment_access' );

/**
 * Filter comments to hide them from unauthorized users
 *
 * @param array $comments Array of comments
 * @param int   $post_id Post ID
 * @return array Filtered comments
 */
function dv_filter_comments_by_access( $comments, $post_id ) {
	$post = get_post( $post_id );

	// Only for our CPT
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return $comments;
	}

	// If user doesn't have access to the document, hide all comments
	if ( ! dv_user_can_access_doc( $post_id ) ) {
		return array();
	}

	return $comments;
}
add_filter( 'comments_array', 'dv_filter_comments_by_access', 10, 2 );

/**
 * Modify comment form defaults for private documents
 *
 * @param array $defaults Default comment form arguments
 * @return array Modified defaults
 */
function dv_modify_comment_form_defaults( $defaults ) {
	global $post;

	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return $defaults;
	}

	// Customize messages
	$defaults['must_log_in'] = sprintf(
		'<p class="must-log-in">%s <a href="%s">%s</a></p>',
		esc_html__( 'You must be logged in to comment.', 'dv-private-docs' ),
		esc_url( wp_login_url( get_permalink() ) ),
		esc_html__( 'Log in', 'dv-private-docs' )
	);

	$defaults['logged_in_as'] = sprintf(
		'<p class="logged-in-as">%s <a href="%s">%s</a>. <a href="%s">%s</a></p>',
		sprintf(
			/* translators: %s: user display name */
			esc_html__( 'Logged in as %s.', 'dv-private-docs' ),
			wp_get_current_user()->display_name
		),
		esc_url( get_edit_user_link() ),
		esc_html__( 'Edit your profile', 'dv-private-docs' ),
		esc_url( wp_logout_url( get_permalink() ) ),
		esc_html__( 'Log out', 'dv-private-docs' )
	);

	return $defaults;
}
add_filter( 'comment_form_defaults', 'dv_modify_comment_form_defaults' );

/**
 * Add custom comment metadata to track document context
 *
 * @param int $comment_id Comment ID
 */
function dv_add_comment_metadata( $comment_id ) {
	$comment = get_comment( $comment_id );

	if ( ! $comment ) {
		return;
	}

	$post = get_post( $comment->comment_post_ID );

	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Mark as private document comment
	add_comment_meta( $comment_id, 'dv_private_doc_comment', 1, true );
}
add_action( 'comment_post', 'dv_add_comment_metadata' );

/**
 * Fix comment redirect to stay on the shortcode page
 *
 * @param string $location Redirect location
 * @param object $comment Comment object
 * @return string Modified redirect location
 */
function dv_comment_post_redirect( $location, $comment ) {
	// Check if comment is on our post type
	$post = get_post( $comment->comment_post_ID );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return $location;
	}

	// Check if we have the referer (page where comment was submitted from)
	if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
		return $location;
	}

	$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

	// Parse the URL to get the doc parameter
	$parsed_url = wp_parse_url( $referer );

	if ( ! isset( $parsed_url['query'] ) ) {
		return $location;
	}

	parse_str( $parsed_url['query'], $query_vars );

	if ( ! isset( $query_vars['doc'] ) ) {
		return $location;
	}

	// Build the redirect URL preserving the doc parameter
	$base_url = strtok( $referer, '?' );
	$redirect_url = add_query_arg( 'doc', $query_vars['doc'], $base_url );

	// Add comment anchor
	$redirect_url .= '#comment-' . $comment->comment_ID;

	return $redirect_url;
}
add_filter( 'comment_post_redirect', 'dv_comment_post_redirect', 10, 2 );
