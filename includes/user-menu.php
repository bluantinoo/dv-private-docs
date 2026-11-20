<?php
/**
 * User Menu Shortcode
 *
 * Displays a dynamic menu based on user login status
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register user menu shortcode
 */
function dv_register_user_menu_shortcode() {
	add_shortcode( 'dv_user_menu', 'dv_user_menu_shortcode' );
}
add_action( 'init', 'dv_register_user_menu_shortcode' );

/**
 * User menu shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Menu HTML
 */
function dv_user_menu_shortcode( $atts ) {
	// Parse attributes
	$atts = shortcode_atts(
		array(
			'class' => '',
		),
		$atts,
		'dv_user_menu'
	);

	ob_start();

	if ( ! is_user_logged_in() ) {
		// User not logged in - show login link
		$documents_url = dv_get_page_url_for_lang_option( 'dv_list_page', 0 );

		if ( ! $documents_url ) {
			$documents_url = home_url();
		}

		?>
		<div class="dv-user-menu dv-user-menu-logged-out <?php echo esc_attr( $atts['class'] ); ?>">
			<a href="<?php echo esc_url( $documents_url ); ?>" class="dv-menu-link dv-menu-login">
				<?php esc_html_e( 'Login', 'dv-private-docs' ); ?>
			</a>
		</div>
		<?php
	} else {
		// User logged in - show documents, profile, logout
		$current_user   = wp_get_current_user();
		$documents_url  = dv_get_page_url_for_lang_option( 'dv_list_page', 0 );
		$profile_url    = dv_get_page_url_for_lang_option( 'dv_profile_page', 0 );
		$logout_url     = wp_logout_url( home_url() );

		// Get page IDs (considering WPML)
		$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
		$list_page_id = $current_lang ? get_option( 'dv_list_page_' . $current_lang, 0 ) : get_option( 'dv_list_page', 0 );
		$view_page_id = $current_lang ? get_option( 'dv_view_page_' . $current_lang, 0 ) : get_option( 'dv_view_page', 0 );
		$profile_page_id = $current_lang ? get_option( 'dv_profile_page_' . $current_lang, 0 ) : get_option( 'dv_profile_page', 0 );

		// Fallback to base options if language-specific not found
		if ( ! $list_page_id && $current_lang ) {
			$list_page_id = get_option( 'dv_list_page', 0 );
		}
		if ( ! $view_page_id && $current_lang ) {
			$view_page_id = get_option( 'dv_view_page', 0 );
		}
		if ( ! $profile_page_id && $current_lang ) {
			$profile_page_id = get_option( 'dv_profile_page', 0 );
		}

		// Determine current page
		$current_page_id = get_the_ID();
		$is_documents_area = ( $current_page_id == $list_page_id || $current_page_id == $view_page_id );
		$is_profile_page = ( $current_page_id == $profile_page_id );

		// Fallback URLs
		if ( ! $documents_url ) {
			$documents_url = home_url();
		}
		if ( ! $profile_url ) {
			$profile_url = home_url();
		}

		// Build CSS classes for links
		$documents_class = 'dv-menu-link dv-menu-documents' . ( $is_documents_area ? ' current-page' : '' );
		$profile_class = 'dv-menu-link dv-menu-profile' . ( $is_profile_page ? ' current-page' : '' );

		?>
		<div class="dv-user-menu dv-user-menu-logged-in <?php echo esc_attr( $atts['class'] ); ?>">
			<span class="dv-menu-user-name">
				<?php
				printf(
					/* translators: %s: User display name */
					esc_html__( 'Hello, %s', 'dv-private-docs' ),
					esc_html( $current_user->display_name )
				);
				?>
			</span>
			<nav class="dv-menu-nav">
				<a href="<?php echo esc_url( $documents_url ); ?>" class="<?php echo esc_attr( $documents_class ); ?>">
					<?php esc_html_e( 'My Documents', 'dv-private-docs' ); ?>
				</a>
				<?php if ( $profile_url && $profile_url !== home_url() ) : ?>
					<a href="<?php echo esc_url( $profile_url ); ?>" class="<?php echo esc_attr( $profile_class ); ?>">
						<?php esc_html_e( 'My Profile', 'dv-private-docs' ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( $logout_url ); ?>" class="dv-menu-link dv-menu-logout">
					<?php esc_html_e( 'Logout', 'dv-private-docs' ); ?>
				</a>
			</nav>
		</div>
		<?php
	}

	return ob_get_clean();
}

/**
 * Enqueue user menu styles
 */
function dv_enqueue_user_menu_styles() {
	// Skip CSS if unstyled mode is enabled
	if ( get_option( 'dv_enable_unstyled_mode', false ) ) {
		return;
	}

	// Check if the current page/post has the shortcode
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dv_user_menu' ) ) {
		wp_enqueue_style( 'dv-private-docs-frontend' );
	}
}
add_action( 'wp_enqueue_scripts', 'dv_enqueue_user_menu_styles' );
