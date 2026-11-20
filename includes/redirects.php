<?php
/**
 * Post-login redirects for subscribers
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect subscribers after login
 *
 * @param string  $redirect_to URL to redirect to
 * @param string  $request Requested redirect URL
 * @param WP_User $user User object
 * @return string Modified redirect URL
 */
function dv_subscriber_login_redirect( $redirect_to, $request, $user ) {
	// A login that asked for a page of the site (the embedded form sends the
	// page it is on) goes there. Only a missing or dashboard destination falls
	// through to the redirect page below. Checked on the requested URL rather
	// than on the form's marker field: the second step of a two-factor plugin
	// posts again without that field.
	if ( $user instanceof WP_User && $request ) {
		$back       = wp_validate_redirect( $request, '' );
		$back_path  = (string) wp_parse_url( $back, PHP_URL_PATH );
		$admin_path = (string) wp_parse_url( admin_url(), PHP_URL_PATH );
		// Compare paths: a relative "/wp-admin/" is a dashboard destination too.
		if ( $back && 0 !== strpos( trailingslashit( $back_path ), $admin_path ) ) {
			return $back;
		}
	}

	// Only redirect subscribers (not admins/editors)
	if ( ! isset( $user->roles ) || ! is_array( $user->roles ) ) {
		return $redirect_to;
	}

	// If user is admin or editor, let them go to admin
	if ( in_array( 'administrator', $user->roles, true ) || in_array( 'editor', $user->roles, true ) ) {
		return $redirect_to;
	}

	// Only redirect subscribers
	if ( ! in_array( 'subscriber', $user->roles, true ) ) {
		return $redirect_to;
	}

	// Get redirect page from settings (language-aware)
	$redirect_page_id = 0;

	if ( dv_wpml_is_active() ) {
		// Try user's language first
		$user_lang = dv_get_user_language( $user->ID );
		if ( $user_lang ) {
			$redirect_page_id = absint( get_option( 'dv_subscriber_redirect_page_' . $user_lang, 0 ) );
		}

		// Fallback to site default language
		if ( ! $redirect_page_id ) {
			$default_lang = dv_get_default_lang();
			if ( $default_lang ) {
				$redirect_page_id = absint( get_option( 'dv_subscriber_redirect_page_' . $default_lang, 0 ) );
			}
		}
	}

	// Fallback to base option
	if ( ! $redirect_page_id ) {
		$redirect_page_id = absint( get_option( 'dv_subscriber_redirect_page', 0 ) );
	}

	// If no redirect page set, use default behavior
	if ( ! $redirect_page_id ) {
		return $redirect_to;
	}

	// Get page URL (translate if WPML active)
	if ( dv_wpml_is_active() && function_exists( 'dv_get_user_language' ) ) {
		$user_lang = dv_get_user_language( $user->ID );
		$redirect_page_id = dv_get_translated_post_id( $redirect_page_id, 'page', $user_lang );
	}

	$redirect_url = get_permalink( $redirect_page_id );

	// Only redirect if we have a valid URL
	return $redirect_url ? $redirect_url : $redirect_to;
}
add_filter( 'login_redirect', 'dv_subscriber_login_redirect', 10, 3 );
