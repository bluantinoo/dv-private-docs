<?php
/**
 * Email notifications for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send email notification when a document is assigned to a user
 *
 * @param int $post_id Document post ID
 * @param int $old_user_id Previous assigned user ID (if any)
 * @param int $new_user_id New assigned user ID
 */
function dv_notify_user_document_assigned( $post_id, $old_user_id, $new_user_id ) {
	// Check if notification is enabled
	$notify_enabled = get_option( 'dv_notify_on_assignment', true );
	if ( ! $notify_enabled ) {
		return;
	}

	// Only send if there's a new user and it's different from old
	if ( ! $new_user_id || $new_user_id === $old_user_id ) {
		return;
	}

	$user = get_user_by( 'id', $new_user_id );
	if ( ! $user ) {
		return;
	}

	$document = get_post( $post_id );
	if ( ! $document || 'dv_private_doc' !== $document->post_type || 'publish' !== $document->post_status ) {
		return;
	}

	// Determine recipient language and build URL in their language
	$recipient_lang = dv_get_user_language( $new_user_id );
	$original_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;

	// Switch to recipient language if WPML active
	if ( dv_wpml_is_active() && $recipient_lang ) {
		do_action( 'wpml_switch_language', $recipient_lang );
	}

	// Build document URL in recipient's language
	$view_page_url = dv_get_page_url_for_lang_option( 'dv_view_page', 0 );
	if ( $view_page_url ) {
		$doc_url = add_query_arg( 'doc', $post_id, $view_page_url );
	} else {
		$doc_url = home_url( '?doc=' . $post_id );
	}

	// Get email template in recipient's language
	$template = dv_get_email_template( 'dv_template_assignment', $recipient_lang, dv_get_default_assignment_template() );

	// Prepare placeholder data
	$data = array(
		'user_name'      => $user->display_name,
		'user_email'     => $user->user_email,
		'document_title' => $document->post_title,
		'document_url'   => $doc_url,
	);

	// Replace placeholders
	$message = dv_replace_email_placeholders( $template, $data );

	// Email subject (remains translatable)
	$subject = sprintf(
		/* translators: %s: Document title */
		__( 'New document assigned: %s', 'dv-private-docs' ),
		$document->post_title
	);

	// Send email
	wp_mail( $user->user_email, $subject, $message );

	// Restore original language
	if ( dv_wpml_is_active() && $original_lang ) {
		do_action( 'wpml_switch_language', $original_lang );
	}
}

/**
 * Hook into save_post to detect user assignment changes and send notifications
 *
 * @param int $post_id Post ID
 */
function dv_check_user_assignment_change( $post_id ) {
	// Check if it's our post type
	$post = get_post( $post_id );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Only for published documents
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	// Get old and new user IDs
	$old_user_id = get_post_meta( $post_id, 'dv_assigned_user_id_old', true );
	$new_user_id = get_post_meta( $post_id, 'dv_assigned_user_id', true );

	// If there's a new assignment, send notification
	if ( $new_user_id && $new_user_id != $old_user_id ) {
		dv_notify_user_document_assigned( $post_id, $old_user_id, $new_user_id );

		// Update old user ID for next comparison
		update_post_meta( $post_id, 'dv_assigned_user_id_old', $new_user_id );
	}
}
add_action( 'save_post_dv_private_doc', 'dv_check_user_assignment_change', 100 );

/**
 * Send email notification when a comment is posted on a document
 *
 * @param int    $comment_id Comment ID
 * @param string $comment_approved Comment approval status
 */
function dv_notify_comment_posted( $comment_id, $comment_approved ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		return;
	}

	$post = get_post( $comment->comment_post_ID );
	if ( ! $post || 'dv_private_doc' !== $post->post_type ) {
		return;
	}

	// Only send notification for approved comments
	if ( 1 !== $comment_approved && 'approve' !== $comment_approved ) {
		return;
	}

	// Get assigned user
	$assigned_user_id = get_post_meta( $post->ID, 'dv_assigned_user_id', true );
	if ( ! $assigned_user_id ) {
		return;
	}

	$assigned_user = get_user_by( 'id', $assigned_user_id );
	if ( ! $assigned_user ) {
		return;
	}

	// Determine who to notify
	$comment_author_id = $comment->user_id;

	// If comment is from the assigned user, notify admins
	// If comment is from admin/someone else, notify the assigned user
	if ( $comment_author_id == $assigned_user_id ) {
		// Check if admin notification is enabled
		$notify_admin_enabled = get_option( 'dv_notify_admin_on_comment', true );
		if ( ! $notify_admin_enabled ) {
			return;
		}

		// Notify custom email or admins
		$custom_email = get_option( 'dv_notification_email', '' );

		if ( $custom_email && is_email( $custom_email ) ) {
			// Use custom notification email
			$recipient_emails = array( $custom_email );
		} else {
			// Fallback to all administrators
			$recipients = get_users( array( 'role' => 'administrator' ) );
			$recipient_emails = array_map( function( $admin ) {
				return $admin->user_email;
			}, $recipients );
		}
	} else {
		// Check if user notification is enabled
		$notify_user_enabled = get_option( 'dv_notify_user_on_comment', true );
		if ( ! $notify_user_enabled ) {
			return;
		}

		// Notify assigned user
		$recipient_emails = array( $assigned_user->user_email );
	}

	// Don't send to comment author
	$comment_author_email = $comment->comment_author_email;
	$recipient_emails = array_filter( $recipient_emails, function( $email ) use ( $comment_author_email ) {
		return $email !== $comment_author_email;
	});

	if ( empty( $recipient_emails ) ) {
		return;
	}

	// Send emails to each recipient in their language
	foreach ( $recipient_emails as $email ) {
		// Try to find user by email to get their language preference
		$recipient_user = get_user_by( 'email', $email );
		$recipient_lang = $recipient_user ? dv_get_user_language( $recipient_user->ID ) : null;
		$original_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;

		// Switch to recipient language if WPML active
		if ( dv_wpml_is_active() && $recipient_lang ) {
			do_action( 'wpml_switch_language', $recipient_lang );
		}

		// Build document URL in recipient's language
		$view_page_url = dv_get_page_url_for_lang_option( 'dv_view_page', 0 );
		if ( $view_page_url ) {
			$doc_url = add_query_arg( 'doc', $post->ID, $view_page_url ) . '#comment-' . $comment_id;
		} else {
			$doc_url = home_url( '?doc=' . $post->ID . '#comment-' . $comment_id );
		}

		// Email subject (will use recipient's language after switch)
		$subject = sprintf(
			/* translators: %s: Document title */
			__( 'New comment on document: %s', 'dv-private-docs' ),
			$post->post_title
		);

		// Determine recipient type and build message
		if ( $comment_author_id == $assigned_user_id ) {
			// User commenting → notify admin (keep hardcoded, no template)
			$message = sprintf(
				/* translators: 1: Comment author name, 2: Document title, 3: Comment text, 4: Document URL */
				__( "New comment from %1\$s on document \"%2\$s\":\n\n%3\$s\n\nView document:\n%4\$s", 'dv-private-docs' ),
				$comment->comment_author,
				$post->post_title,
				wp_strip_all_tags( $comment->comment_content ),
				$doc_url
			);
		} else {
			// Admin/other commenting → notify user (use template)
			$template = dv_get_email_template( 'dv_template_comment', $recipient_lang, dv_get_default_comment_template() );

			$data = array(
				'user_name'       => $assigned_user->display_name,
				'document_title'  => $post->post_title,
				'document_url'    => $doc_url,
				'comment_author'  => $comment->comment_author,
				'comment_text'    => wp_strip_all_tags( $comment->comment_content ),
			);

			$message = dv_replace_email_placeholders( $template, $data );
		}

		// Send email
		wp_mail( $email, $subject, $message );

		// Restore original language
		if ( dv_wpml_is_active() && $original_lang ) {
			do_action( 'wpml_switch_language', $original_lang );
		}
	}
}
add_action( 'comment_post', 'dv_notify_comment_posted', 10, 2 );

/**
 * Send notification when a comment is approved from moderation
 *
 * @param string $new_status New comment status
 * @param string $old_status Old comment status
 * @param object $comment Comment object
 */
function dv_notify_comment_approved( $new_status, $old_status, $comment ) {
	// Only notify when transitioning from unapproved to approved
	if ( 'approved' !== $new_status || 'approved' === $old_status ) {
		return;
	}

	// Use same notification function
	dv_notify_comment_posted( $comment->comment_ID, 1 );
}
add_action( 'transition_comment_status', 'dv_notify_comment_approved', 10, 3 );
