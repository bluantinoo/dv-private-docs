<?php
/**
 * Settings page for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add settings page to admin menu
 */
function dv_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=dv_private_doc',
		__( 'Settings', 'dv-private-docs' ),
		__( 'Settings', 'dv-private-docs' ),
		'manage_options',
		'dv-private-docs-settings',
		'dv_render_settings_page'
	);
}
add_action( 'admin_menu', 'dv_add_settings_page' );

/**
 * Register settings
 */
function dv_register_settings() {
	register_setting(
		'dv_private_docs_settings',
		'dv_hide_admin_bar_subscribers',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_show_login_message',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_enable_unstyled_mode',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_delete_data_on_uninstall',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_notification_email',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => get_option( 'admin_email' ),
		)
	);

	// Register page settings with language suffix support
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;

	if ( $current_lang ) {
		// WPML active: register language-specific options
		register_setting(
			'dv_private_docs_settings',
			'dv_list_page_' . $current_lang,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_view_page_' . $current_lang,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_profile_page_' . $current_lang,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	} else {
		// No WPML: register base options
		register_setting(
			'dv_private_docs_settings',
			'dv_list_page',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_view_page',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_profile_page',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	}

	// Login Form Settings
	register_setting(
		'dv_private_docs_settings',
		'dv_enable_embedded_login',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_login_captcha',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'dv_sanitize_login_captcha',
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_recaptcha_site_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_recaptcha_secret_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_turnstile_site_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_turnstile_secret_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_turnstile_theme',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'dv_sanitize_turnstile_theme',
			'default'           => 'auto',
		)
	);

	// Login form content (language-aware)
	if ( $current_lang ) {
		register_setting(
			'dv_private_docs_settings',
			'dv_login_form_before_content_' . $current_lang,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_login_form_after_content_' . $current_lang,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);
	} else {
		register_setting(
			'dv_private_docs_settings',
			'dv_login_form_before_content',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_login_form_after_content',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);
	}

	// Redirect Settings (language-aware)
	if ( $current_lang ) {
		register_setting(
			'dv_private_docs_settings',
			'dv_subscriber_redirect_page_' . $current_lang,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	} else {
		register_setting(
			'dv_private_docs_settings',
			'dv_subscriber_redirect_page',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	}

	// Color scheme setting (global)
	register_setting(
		'dv_private_docs_settings',
		'dv_color_scheme',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'auto',
		)
	);

	// Notification toggles (global)
	register_setting(
		'dv_private_docs_settings',
		'dv_notify_on_assignment',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_notify_user_on_comment',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		)
	);

	register_setting(
		'dv_private_docs_settings',
		'dv_notify_admin_on_comment',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		)
	);

	// Email templates (language-aware)
	if ( $current_lang ) {
		register_setting(
			'dv_private_docs_settings',
			'dv_template_assignment_' . $current_lang,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_template_comment_' . $current_lang,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			)
		);
	} else {
		register_setting(
			'dv_private_docs_settings',
			'dv_template_assignment',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			)
		);

		register_setting(
			'dv_private_docs_settings',
			'dv_template_comment',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			)
		);
	}

	// Add settings section
	add_settings_section(
		'dv_general_settings',
		__( 'General Settings', 'dv-private-docs' ),
		'dv_general_settings_callback',
		'dv-private-docs-settings'
	);

	// Add settings fields
	add_settings_field(
		'dv_list_page',
		__( 'Documents List Page', 'dv-private-docs' ),
		'dv_list_page_callback',
		'dv-private-docs-settings',
		'dv_general_settings'
	);

	add_settings_field(
		'dv_view_page',
		__( 'Document View Page', 'dv-private-docs' ),
		'dv_view_page_callback',
		'dv-private-docs-settings',
		'dv_general_settings'
	);

	add_settings_field(
		'dv_profile_page',
		__( 'User Profile Page', 'dv-private-docs' ),
		'dv_profile_page_callback',
		'dv-private-docs-settings',
		'dv_general_settings'
	);

	add_settings_field(
		'dv_delete_data_on_uninstall',
		__( 'Delete Data on Uninstall', 'dv-private-docs' ),
		'dv_delete_data_on_uninstall_callback',
		'dv-private-docs-settings',
		'dv_general_settings'
	);

	// Appearance Section
	add_settings_section(
		'dv_appearance_settings',
		__( 'Appearance Settings', 'dv-private-docs' ),
		'dv_appearance_settings_callback',
		'dv-private-docs-settings'
	);

	add_settings_field(
		'dv_color_scheme',
		__( 'Color Scheme', 'dv-private-docs' ),
		'dv_color_scheme_callback',
		'dv-private-docs-settings',
		'dv_appearance_settings'
	);

	add_settings_field(
		'dv_enable_unstyled_mode',
		__( 'Enable Unstyled Mode', 'dv-private-docs' ),
		'dv_enable_unstyled_mode_callback',
		'dv-private-docs-settings',
		'dv_appearance_settings'
	);

	// Login Form Section
	add_settings_section(
		'dv_login_settings',
		__( 'Login Form Settings', 'dv-private-docs' ),
		'dv_login_settings_callback',
		'dv-private-docs-settings'
	);

	add_settings_field(
		'dv_hide_admin_bar_subscribers',
		__( 'Hide Admin Bar for Subscribers', 'dv-private-docs' ),
		'dv_hide_admin_bar_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_show_login_message',
		__( 'Show Login Message', 'dv-private-docs' ),
		'dv_show_login_message_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_enable_embedded_login',
		__( 'Enable Embedded Login Form', 'dv-private-docs' ),
		'dv_enable_embedded_login_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_login_captcha',
		__( 'Anti-bot Protection', 'dv-private-docs' ),
		'dv_login_captcha_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_recaptcha_site_key',
		__( 'reCAPTCHA Site Key', 'dv-private-docs' ),
		'dv_recaptcha_site_key_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_recaptcha_secret_key',
		__( 'reCAPTCHA Secret Key', 'dv-private-docs' ),
		'dv_recaptcha_secret_key_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_turnstile_site_key',
		__( 'Turnstile Site Key', 'dv-private-docs' ),
		'dv_turnstile_site_key_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_turnstile_secret_key',
		__( 'Turnstile Secret Key', 'dv-private-docs' ),
		'dv_turnstile_secret_key_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_turnstile_theme',
		__( 'Turnstile Theme', 'dv-private-docs' ),
		'dv_turnstile_theme_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_login_form_before_content',
		__( 'Content Before Login Form', 'dv-private-docs' ),
		'dv_login_form_before_content_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	add_settings_field(
		'dv_login_form_after_content',
		__( 'Content After Login Form', 'dv-private-docs' ),
		'dv_login_form_after_content_callback',
		'dv-private-docs-settings',
		'dv_login_settings'
	);

	// Redirect Section
	add_settings_section(
		'dv_redirect_settings',
		__( 'Redirect Settings', 'dv-private-docs' ),
		'dv_redirect_settings_callback',
		'dv-private-docs-settings'
	);

	add_settings_field(
		'dv_subscriber_redirect_page',
		__( 'Subscriber Redirect Page', 'dv-private-docs' ),
		'dv_subscriber_redirect_callback',
		'dv-private-docs-settings',
		'dv_redirect_settings'
	);

	// User Notification Settings Section
	add_settings_section(
		'dv_user_notification_settings',
		__( 'User Notification Settings', 'dv-private-docs' ),
		'dv_user_notification_settings_callback',
		'dv-private-docs-settings'
	);

	add_settings_field(
		'dv_notify_on_assignment',
		__( 'Notify on Document Assignment', 'dv-private-docs' ),
		'dv_notify_on_assignment_callback',
		'dv-private-docs-settings',
		'dv_user_notification_settings'
	);

	add_settings_field(
		'dv_notify_user_on_comment',
		__( 'Notify User on Comment Reply', 'dv-private-docs' ),
		'dv_notify_user_on_comment_callback',
		'dv-private-docs-settings',
		'dv_user_notification_settings'
	);

	add_settings_field(
		'dv_template_assignment',
		__( 'Document Assignment Email Template', 'dv-private-docs' ),
		'dv_template_assignment_callback',
		'dv-private-docs-settings',
		'dv_user_notification_settings'
	);

	add_settings_field(
		'dv_template_comment',
		__( 'Comment Notification Email Template', 'dv-private-docs' ),
		'dv_template_comment_callback',
		'dv-private-docs-settings',
		'dv_user_notification_settings'
	);

	// Admin Notification Settings Section
	add_settings_section(
		'dv_admin_notification_settings',
		__( 'Admin Notification Settings', 'dv-private-docs' ),
		'dv_admin_notification_settings_callback',
		'dv-private-docs-settings'
	);

	add_settings_field(
		'dv_notify_admin_on_comment',
		__( 'Notify Admins on User Comment', 'dv-private-docs' ),
		'dv_notify_admin_on_comment_callback',
		'dv-private-docs-settings',
		'dv_admin_notification_settings'
	);

	add_settings_field(
		'dv_notification_email',
		__( 'Admin Notification Email', 'dv-private-docs' ),
		'dv_notification_email_callback',
		'dv-private-docs-settings',
		'dv_admin_notification_settings'
	);
}
add_action( 'admin_init', 'dv_register_settings' );

/**
 * Settings section callback
 */
function dv_general_settings_callback() {
	echo '<p>' . esc_html__( 'Configure the plugin settings below.', 'dv-private-docs' ) . '</p>';

	// Show language indicator if WPML is active
	if ( dv_wpml_is_active() ) {
		$current_lang = dv_get_current_lang();
		$lang_name    = $current_lang ? strtoupper( $current_lang ) : __( 'Unknown', 'dv-private-docs' );
		echo '<div style="margin: 20px 0; padding: 15px; background: #fff; border: 2px solid #2271b1; border-radius: 4px;">';
		echo '<h4 style="margin-top: 0; margin-bottom: 10px; color: #2271b1;">' . esc_html__( 'WPML Compatibility', 'dv-private-docs' ) . '</h4>';
		echo '<p style="margin: 0; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
		echo '<strong>' . esc_html__( 'Current language:', 'dv-private-docs' ) . '</strong> ';
		echo esc_html( $lang_name );
		echo ' &mdash; ' . esc_html__( 'Page settings below apply to this language only.', 'dv-private-docs' );
		echo '</p>';
		echo '</div>';
	}
}

/**
 * Appearance settings section callback
 */
function dv_appearance_settings_callback() {
	echo '<p>' . esc_html__( 'Customize the visual appearance of the plugin frontend.', 'dv-private-docs' ) . '</p>';
}

/**
 * Hide admin bar checkbox callback
 */
function dv_hide_admin_bar_callback() {
	$value = get_option( 'dv_hide_admin_bar_subscribers', false );
	?>
	<label>
		<input type="checkbox" name="dv_hide_admin_bar_subscribers" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Hide the WordPress admin bar for users with the Subscriber role', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: shortcode example */
				__( 'When enabled, subscribers will not see the admin bar. Combine this with the <code>[dv_user_profile]</code> shortcode to create a page where users can edit their profile (name, email, password, organization) without accessing wp-admin. This creates a complete self-service portal experience for your users.', 'dv-private-docs' ),
				'[dv_user_profile]'
			)
		);
		?>
	</p>
	<?php
}

/**
 * Show login message checkbox callback
 */
function dv_show_login_message_callback() {
	$value = get_option( 'dv_show_login_message', false );
	?>
	<label>
		<input type="checkbox" name="dv_show_login_message" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Show login message on [dv_private_docs] shortcode for non-logged-in users', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'If unchecked, non-logged-in users will see nothing. If checked, they will see a message with a login link.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Notification email field callback
 */
function dv_notification_email_callback() {
	$value = get_option( 'dv_notification_email', get_option( 'admin_email' ) );
	?>
	<input type="email" name="dv_notification_email" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description">
		<?php esc_html_e( 'Email address to receive notifications when users comment on documents. Leave empty to use the default admin email.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Documents list page dropdown callback
 */
function dv_list_page_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key = $current_lang ? 'dv_list_page_' . $current_lang : 'dv_list_page';

	// Get value with fallback to base option for backwards compatibility
	$value = get_option( $option_key, 0 );
	if ( ! $value && $current_lang ) {
		$value = get_option( 'dv_list_page', 0 );
	}
	?>
	<?php
	wp_dropdown_pages(
		array(
			'name'              => esc_attr( $option_key ),
			'selected'          => absint( $value ),
			'show_option_none'  => esc_html( __( '— Select —', 'dv-private-docs' ) ),
			'option_none_value' => 0,
		)
	);
	?>
	<p class="description">
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: shortcode example */
				__( 'Page containing the <code>[dv_private_docs]</code> shortcode. This displays a list of documents assigned to the current user. Each document links to the view page with a <code>?doc=ID</code> parameter.', 'dv-private-docs' ),
				'[dv_private_docs]'
			)
		);
		?>
	</p>
	<?php
}

/**
 * Document view page dropdown callback
 */
function dv_view_page_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key = $current_lang ? 'dv_view_page_' . $current_lang : 'dv_view_page';

	// Get value with fallback to base option for backwards compatibility
	$value = get_option( $option_key, 0 );
	if ( ! $value && $current_lang ) {
		$value = get_option( 'dv_view_page', 0 );
	}
	?>
	<?php
	wp_dropdown_pages(
		array(
			'name'              => esc_attr( $option_key ),
			'selected'          => absint( $value ),
			'show_option_none'  => esc_html( __( '— Select —', 'dv-private-docs' ) ),
			'option_none_value' => 0,
		)
	);
	?>
	<p class="description">
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: shortcode example */
				__( 'Page containing the <code>[dv_private_doc_view]</code> shortcode. This displays a single document based on the <code>?doc=ID</code> URL parameter, with download button, comments, and a back link to the documents list page.', 'dv-private-docs' ),
				'[dv_private_doc_view]'
			)
		);
		?>
	</p>
	<?php
}

/**
 * User profile page dropdown callback
 */
function dv_profile_page_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key = $current_lang ? 'dv_profile_page_' . $current_lang : 'dv_profile_page';

	// Get value with fallback to base option for backwards compatibility
	$value = get_option( $option_key, 0 );
	if ( ! $value && $current_lang ) {
		$value = get_option( 'dv_profile_page', 0 );
	}
	?>
	<?php
	wp_dropdown_pages(
		array(
			'name'              => esc_attr( $option_key ),
			'selected'          => absint( $value ),
			'show_option_none'  => esc_html( __( '— Select —', 'dv-private-docs' ) ),
			'option_none_value' => 0,
		)
	);
	?>
	<p class="description">
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: shortcode example */
				__( 'Page containing the <code>[dv_user_profile]</code> shortcode. This page will be linked in the user menu for logged-in users to view and edit their profile information.', 'dv-private-docs' ),
				'[dv_user_profile]'
			)
		);
		?>
	</p>
	<?php
}

/**
 * Render settings page
 */
function dv_render_settings_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Show success message if settings were saved
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core sets this parameter after settings save via Settings API
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error(
			'dv_private_docs_messages',
			'dv_private_docs_message',
			__( 'Settings saved successfully.', 'dv-private-docs' ),
			'success'
		);
	}

	// Show error/success messages
	settings_errors( 'dv_private_docs_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<!-- Setup Instructions -->
		<div style="margin: 20px 0 30px 0; padding: 20px; background: #fff; border: 2px solid #2271b1; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0; margin-bottom: 15px; color: #2271b1; display: flex; align-items: center; gap: 10px;">
				<span class="dashicons dashicons-admin-generic" style="font-size: 24px;"></span>
				<?php esc_html_e( 'Initial Setup Instructions', 'dv-private-docs' ); ?>
			</h2>
			<p style="margin-bottom: 15px; font-size: 14px;">
				<?php esc_html_e( 'Follow these steps to configure the plugin:', 'dv-private-docs' ); ?>
			</p>
			<ol style="margin: 0; padding-left: 25px; line-height: 2;">
				<li style="margin-bottom: 12px;">
					<strong><?php esc_html_e( 'Create a page for the documents list', 'dv-private-docs' ); ?></strong>
					<?php esc_html_e( 'and add the shortcode', 'dv-private-docs' ); ?>
					<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">[dv_private_docs]</code><br>
					<span style="color: #646970; font-size: 13px;">
						<?php esc_html_e( 'This will be a restricted page where logged-in users can see only the documents assigned to them.', 'dv-private-docs' ); ?>
					</span>
				</li>
				<li style="margin-bottom: 12px;">
					<strong><?php esc_html_e( 'Create a page for viewing individual documents', 'dv-private-docs' ); ?></strong>
					<?php esc_html_e( 'and add the shortcode', 'dv-private-docs' ); ?>
					<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">[dv_private_doc_view]</code><br>
					<span style="color: #646970; font-size: 13px;">
						<?php esc_html_e( 'This secure page displays the document title, description, protected download button, and comments that admins can reply to.', 'dv-private-docs' ); ?>
					</span>
				</li>
				<li style="margin-bottom: 12px;">
					<strong><?php esc_html_e( 'Create a page for the user profile', 'dv-private-docs' ); ?></strong>
					<?php esc_html_e( 'and add the shortcode', 'dv-private-docs' ); ?>
					<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">[dv_user_profile]</code><br>
					<span style="color: #646970; font-size: 13px;">
						<?php esc_html_e( 'Use this if you don\'t want users to access the WordPress dashboard to manage their profile.', 'dv-private-docs' ); ?>
					</span>
				</li>
				<li style="margin-bottom: 12px;">
					<strong><?php esc_html_e( '(Optional) Add the user menu', 'dv-private-docs' ); ?></strong>
					<?php esc_html_e( 'to any page with the shortcode', 'dv-private-docs' ); ?>
					<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">[dv_user_menu]</code><br>
					<span style="color: #646970; font-size: 13px;">
						<?php esc_html_e( 'The menu displays: link to documents page, link to profile page (if configured), and login/logout link.', 'dv-private-docs' ); ?>
					</span>
				</li>
				<li style="margin-bottom: 12px;">
					<strong><?php esc_html_e( 'Configure the settings below', 'dv-private-docs' ); ?></strong>
					<?php esc_html_e( 'to select the pages you created and customize the plugin behavior.', 'dv-private-docs' ); ?>
				</li>
				<?php if ( dv_wpml_is_active() ) : ?>
					<li style="margin-bottom: 12px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
						<strong style="color: #856404;">
							<span class="dashicons dashicons-translation" style="font-size: 16px; vertical-align: middle;"></span>
							<?php esc_html_e( 'WPML: Configure settings for each language', 'dv-private-docs' ); ?>
						</strong><br>
						<span style="color: #856404; font-size: 13px;">
							<?php esc_html_e( 'Settings are language-specific. Use the WPML language switcher at the top of the page to change the current language, then select the appropriate translated pages for that language and save. Repeat this process for all active languages on your site.', 'dv-private-docs' ); ?>
						</span>
					</li>
				<?php endif; ?>
			</ol>
		</div>

		<form action="options.php" method="post">
			<?php
			settings_fields( 'dv_private_docs_settings' );
			do_settings_sections( 'dv-private-docs-settings' );
			submit_button( __( 'Save Settings', 'dv-private-docs' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Login settings section callback
 */
function dv_login_settings_callback() {
	echo '<p>' . esc_html__( 'Configure the embedded login form that appears on document pages.', 'dv-private-docs' ) . '</p>';
}

/**
 * Enable embedded login callback
 */
function dv_enable_embedded_login_callback() {
	$value = get_option( 'dv_enable_embedded_login', false );
	?>
	<label>
		<input type="checkbox" name="dv_enable_embedded_login" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Show login form instead of login link on document pages', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, non-logged-in users will see an inline login form instead of a link to wp-login.php', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Sanitize the anti-bot service choice.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function dv_sanitize_login_captcha( $value ) {
	return in_array( $value, array( 'recaptcha', 'turnstile' ), true ) ? $value : '';
}

/**
 * Anti-bot service select callback
 */
function dv_login_captcha_callback() {
	$value = dv_login_captcha_provider();
	?>
	<select name="dv_login_captcha">
		<option value="" <?php selected( $value, '' ); ?>><?php esc_html_e( 'None', 'dv-private-docs' ); ?></option>
		<option value="recaptcha" <?php selected( $value, 'recaptcha' ); ?>><?php esc_html_e( 'Google reCAPTCHA v3', 'dv-private-docs' ); ?></option>
		<option value="turnstile" <?php selected( $value, 'turnstile' ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'dv-private-docs' ); ?></option>
	</select>
	<p class="description">
		<?php esc_html_e( 'Protects the embedded login form against automated login attempts. Fill in the keys of the service you choose. The standard login page (wp-login.php) is not covered: protect it with a security plugin.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * reCAPTCHA site key callback
 */
function dv_recaptcha_site_key_callback() {
	$value = get_option( 'dv_recaptcha_site_key', '' );
	?>
	<input type="text" name="dv_recaptcha_site_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description">
		<?php echo wp_kses_post( __( 'Get your keys from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a>', 'dv-private-docs' ) ); ?>
	</p>
	<?php
}

/**
 * reCAPTCHA secret key callback
 */
function dv_recaptcha_secret_key_callback() {
	$value = get_option( 'dv_recaptcha_secret_key', '' );
	?>
	<input type="password" name="dv_recaptcha_secret_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description">
		<?php esc_html_e( 'Keep this key secret. Never share it publicly.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Turnstile site key callback
 */
function dv_turnstile_site_key_callback() {
	$value = get_option( 'dv_turnstile_site_key', '' );
	?>
	<input type="text" name="dv_turnstile_site_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description">
		<?php echo wp_kses_post( __( 'Get your keys from <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">Cloudflare Turnstile</a>. Add this site\'s domain to the widget.', 'dv-private-docs' ) ); ?>
	</p>
	<?php
}

/**
 * Turnstile secret key callback
 */
function dv_turnstile_secret_key_callback() {
	$value = get_option( 'dv_turnstile_secret_key', '' );
	?>
	<input type="password" name="dv_turnstile_secret_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
	<p class="description">
		<?php esc_html_e( 'Keep this key secret. Never share it publicly.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Sanitize the Turnstile widget theme.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function dv_sanitize_turnstile_theme( $value ) {
	return in_array( $value, array( 'auto', 'light', 'dark' ), true ) ? $value : 'auto';
}

/**
 * Turnstile theme callback
 */
function dv_turnstile_theme_callback() {
	$value = get_option( 'dv_turnstile_theme', 'auto' );
	?>
	<select name="dv_turnstile_theme">
		<option value="auto" <?php selected( $value, 'auto' ); ?>><?php esc_html_e( 'Auto (follow system preference)', 'dv-private-docs' ); ?></option>
		<option value="light" <?php selected( $value, 'light' ); ?>><?php esc_html_e( 'Light mode', 'dv-private-docs' ); ?></option>
		<option value="dark" <?php selected( $value, 'dark' ); ?>><?php esc_html_e( 'Dark mode', 'dv-private-docs' ); ?></option>
	</select>
	<p class="description">
		<?php esc_html_e( 'Colors of the Turnstile widget. Auto follows the light or dark setting of the visitor\'s device, whatever the colors of the site.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Content before login form textarea callback
 */
function dv_login_form_before_content_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key   = $current_lang ? 'dv_login_form_before_content_' . $current_lang : 'dv_login_form_before_content';

	$value = get_option( $option_key, '' );
	if ( '' === $value && $current_lang ) {
		$value = get_option( 'dv_login_form_before_content', '' );
	}
	?>
	<textarea name="<?php echo esc_attr( $option_key ); ?>" rows="6" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'HTML or shortcodes to display inside the login wrapper, before the form. Rendered via the dv_after_login_form_wrapper hook.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Content after login form textarea callback
 */
function dv_login_form_after_content_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key   = $current_lang ? 'dv_login_form_after_content_' . $current_lang : 'dv_login_form_after_content';

	$value = get_option( $option_key, '' );
	if ( '' === $value && $current_lang ) {
		$value = get_option( 'dv_login_form_after_content', '' );
	}
	?>
	<textarea name="<?php echo esc_attr( $option_key ); ?>" rows="6" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'HTML or shortcodes to display inside the login wrapper, after the form. Rendered via the dv_after_login_form hook.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Redirect settings section callback
 */
function dv_redirect_settings_callback() {
	echo '<p>' . esc_html__( 'Configure where users are redirected after login.', 'dv-private-docs' ) . '</p>';
}

/**
 * Subscriber redirect page callback
 */
function dv_subscriber_redirect_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key = $current_lang ? 'dv_subscriber_redirect_page_' . $current_lang : 'dv_subscriber_redirect_page';

	$value = get_option( $option_key, 0 );
	if ( ! $value && $current_lang ) {
		$value = get_option( 'dv_subscriber_redirect_page', 0 );
	}

	wp_dropdown_pages(
		array(
			'name'              => esc_attr( $option_key ),
			'selected'          => absint( $value ),
			'show_option_none'  => esc_html( __( '— WordPress Default —', 'dv-private-docs' ) ),
			'option_none_value' => 0,
		)
	);
	?>
	<p class="description">
		<?php esc_html_e( 'Redirect subscribers to this page after login. Typically the documents list page. Administrators always go to wp-admin.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * User notification settings section callback
 */
function dv_user_notification_settings_callback() {
	echo '<p>' . esc_html__( 'Configure email notifications sent to users when documents are assigned or comments are posted.', 'dv-private-docs' ) . '</p>';

	if ( dv_wpml_is_active() ) {
		$current_lang = dv_get_current_lang();
		$lang_name    = $current_lang ? strtoupper( $current_lang ) : __( 'Unknown', 'dv-private-docs' );
		echo '<div style="margin: 20px 0; padding: 15px; background: #fff; border: 2px solid #2271b1; border-radius: 4px;">';
		echo '<h4 style="margin-top: 0; margin-bottom: 10px; color: #2271b1;">' . esc_html__( 'WPML Compatibility', 'dv-private-docs' ) . '</h4>';
		echo '<p style="margin: 0; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
		echo '<strong>' . esc_html__( 'Current language:', 'dv-private-docs' ) . '</strong> ';
		echo esc_html( $lang_name );
		echo ' &mdash; ' . esc_html__( 'Email templates below apply to this language only.', 'dv-private-docs' );
		echo '</p>';
		echo '</div>';
	}
}

/**
 * Admin notification settings section callback
 */
function dv_admin_notification_settings_callback() {
	echo '<p>' . esc_html__( 'Configure email notifications sent to administrators when users comment on documents.', 'dv-private-docs' ) . '</p>';
}

/**
 * Notify on assignment checkbox callback
 */
function dv_notify_on_assignment_callback() {
	$value = get_option( 'dv_notify_on_assignment', true );
	?>
	<label>
		<input type="checkbox" name="dv_notify_on_assignment" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Send email notification when a document is assigned to a user', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Users will receive notifications in their preferred language when a document is assigned to them.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Notify user on comment checkbox callback
 */
function dv_notify_user_on_comment_callback() {
	$value = get_option( 'dv_notify_user_on_comment', true );
	?>
	<label>
		<input type="checkbox" name="dv_notify_user_on_comment" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Send notification to users when they receive a comment reply on their document', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Assigned users will be notified when admins or others comment on their documents.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Notify admin on comment checkbox callback
 */
function dv_notify_admin_on_comment_callback() {
	$value = get_option( 'dv_notify_admin_on_comment', true );
	?>
	<label>
		<input type="checkbox" name="dv_notify_admin_on_comment" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Send notification to admins when a user comments on a document', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Administrators will be notified when assigned users comment on documents.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Assignment template textarea callback
 */
function dv_template_assignment_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key   = $current_lang ? 'dv_template_assignment_' . $current_lang : 'dv_template_assignment';

	$value = dv_get_email_template( 'dv_template_assignment', $current_lang, '' );

	if ( empty( $value ) ) {
		$value = dv_get_default_assignment_template();
	}
	?>
	<textarea name="<?php echo esc_attr( $option_key ); ?>" rows="10" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Customize the email sent when a document is assigned. Available placeholders:', 'dv-private-docs' ); ?>
		<code>{user_name}</code>, <code>{user_email}</code>, <code>{document_title}</code>, <code>{document_url}</code>
	</p>
	<?php
}

/**
 * Comment template textarea callback
 */
function dv_template_comment_callback() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key   = $current_lang ? 'dv_template_comment_' . $current_lang : 'dv_template_comment';

	$value = dv_get_email_template( 'dv_template_comment', $current_lang, '' );

	if ( empty( $value ) ) {
		$value = dv_get_default_comment_template();
	}
	?>
	<textarea name="<?php echo esc_attr( $option_key ); ?>" rows="10" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Customize the email sent when users receive comment replies. Available placeholders:', 'dv-private-docs' ); ?>
		<code>{user_name}</code>, <code>{document_title}</code>, <code>{document_url}</code>, <code>{comment_author}</code>, <code>{comment_text}</code>
	</p>
	<?php
}

/**
 * Delete data on uninstall checkbox callback
 */
function dv_delete_data_on_uninstall_callback() {
	$value = get_option( 'dv_delete_data_on_uninstall', false );
	?>
	<label>
		<input type="checkbox" name="dv_delete_data_on_uninstall" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Delete all documents, uploaded files and settings when the plugin is deleted', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Applies only when the plugin is deleted from the Plugins screen, never when it is deactivated. Documents, their comments, the files in the private storage folder, the profile fields added by the plugin and all its settings are removed for good. Leave it off to keep everything for a later reinstall.', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Enable unstyled mode checkbox callback
 */
function dv_enable_unstyled_mode_callback() {
	$value = get_option( 'dv_enable_unstyled_mode', false );
	?>
	<label>
		<input type="checkbox" name="dv_enable_unstyled_mode" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Remove all plugin styling to allow full theme customization', 'dv-private-docs' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, the plugin will not apply any default styles. Use this if you want to completely customize the appearance through your theme CSS. All semantic CSS classes will still be present for styling.', 'dv-private-docs' ); ?>
	</p>

	<!-- Template override info box -->
	<div style="margin: 20px 0 0 0; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
		<h4 style="margin-top: 0; margin-bottom: 10px; color: #2271b1;">
			<?php esc_html_e( 'Advanced: Template Override System', 'dv-private-docs' ); ?>
		</h4>
		<p style="margin: 0 0 10px 0;">
			<?php esc_html_e( 'For complete HTML customization, you can override plugin templates by copying them to your theme:', 'dv-private-docs' ); ?>
		</p>
		<p style="margin: 0; font-family: monospace; font-size: 13px; background: #fff; padding: 10px; border-radius: 3px;">
			<?php echo esc_html( 'wp-content/themes/your-theme/dv-private-docs/template-name.php' ); ?>
		</p>
		<p style="margin: 10px 0 0 0; font-size: 13px; color: #646970;">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: path to templates folder */
					__( 'Available templates in: <code>wp-content/plugins/dv-private-docs/templates/</code>', 'dv-private-docs' ),
					'wp-content/plugins/dv-private-docs/templates/'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Color scheme radio buttons callback
 */
function dv_color_scheme_callback() {
	$value = get_option( 'dv_color_scheme', 'auto' );
	?>
	<fieldset>
		<label>
			<input type="radio" name="dv_color_scheme" value="auto" <?php checked( $value, 'auto' ); ?> />
			<?php esc_html_e( 'Auto (follow system preference)', 'dv-private-docs' ); ?>
		</label><br>
		<label>
			<input type="radio" name="dv_color_scheme" value="light" <?php checked( $value, 'light' ); ?> />
			<?php esc_html_e( 'Light mode', 'dv-private-docs' ); ?>
		</label><br>
		<label>
			<input type="radio" name="dv_color_scheme" value="dark" <?php checked( $value, 'dark' ); ?> />
			<?php esc_html_e( 'Dark mode', 'dv-private-docs' ); ?>
		</label>
	</fieldset>
	<p class="description">
		<?php esc_html_e( 'Choose the color scheme for the plugin frontend. Auto follows the user\'s system preference (light/dark mode setting in their operating system).', 'dv-private-docs' ); ?>
	</p>
	<?php
}

/**
 * Hide admin bar for subscribers based on settings
 */
function dv_maybe_hide_admin_bar() {
	$hide_for_subscribers = get_option( 'dv_hide_admin_bar_subscribers', false );

	if ( $hide_for_subscribers && current_user_can( 'subscriber' ) && ! current_user_can( 'edit_posts' ) ) {
		show_admin_bar( false );
	}
}
add_action( 'after_setup_theme', 'dv_maybe_hide_admin_bar' );

/**
 * Apply color scheme body class based on settings
 *
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function dv_apply_color_scheme_class( $classes ) {
	$color_scheme = get_option( 'dv_color_scheme', 'auto' );

	if ( 'light' === $color_scheme ) {
		$classes[] = 'dv-light-mode';
	} elseif ( 'dark' === $color_scheme ) {
		$classes[] = 'dv-dark-mode';
	}
	// If 'auto', don't add any class - let CSS media query handle it

	return $classes;
}
add_filter( 'body_class', 'dv_apply_color_scheme_class' );
