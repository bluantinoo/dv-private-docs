<?php
/**
 * User profile management shortcode
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register user profile shortcode
 */
function dv_register_profile_shortcode() {
	add_shortcode( 'dv_user_profile', 'dv_user_profile_shortcode' );
}
add_action( 'init', 'dv_register_profile_shortcode' );

/**
 * User profile shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function dv_user_profile_shortcode( $atts ) {
	// Must be logged in
	if ( ! is_user_logged_in() ) {
		$login_page_url = dv_get_page_url_for_lang_option( 'dv_list_page', 0 );

		if ( ! $login_page_url ) {
			$login_page_url = wp_login_url( get_permalink() );
		}

		ob_start();
		?>
		<div class="dv-private-docs-notice" style="background: #f0f0f1; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
			<p style="margin: 0;">
				<?php esc_html_e( 'You must be logged in to view this page.', 'dv-private-docs' ); ?>
			</p>
			<p style="margin: 10px 0 0 0;">
				<a href="<?php echo esc_url( $login_page_url ); ?>" class="button">
					<?php esc_html_e( 'Log In', 'dv-private-docs' ); ?>
				</a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	$user_id = get_current_user_id();
	$user = get_userdata( $user_id );

	// Handle form submission
	$message = '';
	if ( isset( $_POST['dv_update_profile'] ) && isset( $_POST['dv_profile_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dv_profile_nonce'] ) ), 'dv_update_profile_' . $user_id ) ) {
		$message = dv_handle_profile_update( $user_id );
	}

	// Get user data
	$first_name = $user->first_name;
	$last_name = $user->last_name;
	$nickname = $user->nickname;
	$email = $user->user_email;
	$website = $user->user_url;
	$organization = get_user_meta( $user_id, 'dv_organization', true );
	$phone        = get_user_meta( $user_id, 'dv_phone', true );

	// Get user language (WPML)
	$user_lang = '';
	if ( dv_wpml_is_active() ) {
		$user_lang = get_user_meta( $user_id, 'icl_admin_language', true );
	}

	// Check for pending email change
	$pending_email = get_user_meta( $user_id, 'dv_pending_email', true );

	ob_start();
	?>
	<div class="dv-user-profile">
		<?php if ( $message ) : ?>
			<div class="dv-profile-message <?php echo strpos( $message, __( 'Profile updated successfully!', 'dv-private-docs' ), true ) !== false ? 'success' : 'error'; ?>">
				<?php echo wp_kses_post( $message ); ?>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'dv_update_profile_' . $user_id, 'dv_profile_nonce' ); ?>

			<div class="dv-profile-info">
				<!-- <?php echo get_avatar( $user_id, 96 ); ?> -->
				<h3><?php esc_html_e( 'Account Information', 'dv-private-docs' ); ?></h3>

				<p>
					<label><?php esc_html_e( 'Username', 'dv-private-docs' ); ?></label><br>
					<input type="text" value="<?php echo esc_attr( $user->user_login ); ?>" class="regular-text" disabled />
					<span class="description"><?php esc_html_e( '(Cannot be changed)', 'dv-private-docs' ); ?></span>
				</p>

				<p>
					<label for="first_name"><?php esc_html_e( 'First Name', 'dv-private-docs' ); ?></label><br>
					<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" />
				</p>

				<p>
					<label for="last_name"><?php esc_html_e( 'Last Name', 'dv-private-docs' ); ?></label><br>
					<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" />
				</p>

				<p>
					<label for="nickname"><?php esc_html_e( 'Nickname', 'dv-private-docs' ); ?></label><br>
					<input type="text" name="nickname" id="nickname" value="<?php echo esc_attr( $nickname ); ?>" class="regular-text" />
				</p>

				<p>
					<label for="dv_organization"><?php esc_html_e( 'Organization', 'dv-private-docs' ); ?></label><br>
					<input type="text" name="dv_organization" id="dv_organization" value="<?php echo esc_attr( $organization ); ?>" class="regular-text" />
					<span class="description"><?php esc_html_e( '(Optional)', 'dv-private-docs' ); ?></span>
				</p>
			</div>

			<div class="dv-profile-contact">
				<h3><?php esc_html_e( 'Contact Information', 'dv-private-docs' ); ?></h3>

				<p>
					<label for="email"><?php esc_html_e( 'Email', 'dv-private-docs' ); ?></label><br>
					<input type="email" name="email" id="email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" required />
					<?php if ( $pending_email ) : ?>
						<br><span class="description dv-pending-email-notice">
							<?php /* translators: %s: New email address awaiting verification */ printf( esc_html__( 'Pending verification: %s', 'dv-private-docs' ), esc_html( $pending_email['new_email'] ) ); ?>
						</span>
					<?php endif; ?>
				</p>

				<p>
					<label for="dv_phone"><?php esc_html_e( 'Phone', 'dv-private-docs' ); ?></label><br>
					<input type="tel" name="dv_phone" id="dv_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
				</p>

				<p>
					<label for="website"><?php esc_html_e( 'Website', 'dv-private-docs' ); ?></label><br>
					<input type="url" name="website" id="website" value="<?php echo esc_attr( $website ); ?>" class="regular-text" placeholder="https://" />
				</p>
			</div>

			<?php if ( dv_wpml_is_active() && function_exists( 'icl_get_languages' ) ) : ?>
				<div class="dv-profile-language">	
					<h3><?php esc_html_e( 'Language Preference', 'dv-private-docs' ); ?></h3>

					<p>
						<label for="user_language"><?php esc_html_e( 'Preferred Language', 'dv-private-docs' ); ?></label><br>
						<select name="user_language" id="user_language" class="regular-text">
							<option value="_default_" <?php selected( $user_lang, '_default_' ); ?>><?php esc_html_e( 'Site Default', 'dv-private-docs' ); ?></option>
							<?php
							$languages = icl_get_languages();
							foreach ( $languages as $lang ) :
								?>
								<option value="<?php echo esc_attr( $lang['language_code'] ); ?>" <?php selected( $user_lang, $lang['language_code'] ); ?>>
									<?php echo esc_html( $lang['native_name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>
			<?php endif; ?>
			
			<div class="dv-profile-password">
				<h3><?php esc_html_e( 'Change Password', 'dv-private-docs' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Leave blank if you don\'t want to change your password.', 'dv-private-docs' ); ?></p>

				<p>
					<label for="current_password"><?php esc_html_e( 'Current Password', 'dv-private-docs' ); ?></label><br>
					<input type="password" name="current_password" id="current_password" class="regular-text" autocomplete="current-password" />
				</p>

				<p>
					<label for="new_password"><?php esc_html_e( 'New Password', 'dv-private-docs' ); ?></label><br>
					<input type="password" name="new_password" id="new_password" class="regular-text" autocomplete="new-password" />
				</p>

				<p>
					<label for="confirm_password"><?php esc_html_e( 'Confirm New Password', 'dv-private-docs' ); ?></label><br>
					<input type="password" name="confirm_password" id="confirm_password" class="regular-text" autocomplete="new-password" />
				</p>
			</div>
			<div class="dv-profile-submit">
				<p>
					<button type="submit" name="dv_update_profile" class="button button-primary button-large">
						<?php esc_html_e( 'Update Profile', 'dv-private-docs' ); ?>
					</button>
				</p>
			</div>
		</form>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Handle profile update
 * Note: Nonce verification occurs before this function is called (see dv_render_user_profile_page)
 *
 * @param int $user_id User ID
 * @return string Success or error message
 */
function dv_handle_profile_update( $user_id ) {
	$errors = array();

	// Update basic fields
	$user_data = array(
		'ID' => $user_id,
	);

	// First name
	if ( isset( $_POST['first_name'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$user_data['first_name'] = sanitize_text_field( wp_unslash( $_POST['first_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Last name
	if ( isset( $_POST['last_name'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$user_data['last_name'] = sanitize_text_field( wp_unslash( $_POST['last_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Nickname
	if ( isset( $_POST['nickname'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$user_data['nickname'] = sanitize_text_field( wp_unslash( $_POST['nickname'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Website
	if ( isset( $_POST['website'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$user_data['user_url'] = esc_url_raw( wp_unslash( $_POST['website'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Handle email change
	if ( isset( $_POST['email'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$new_email = sanitize_email( wp_unslash( $_POST['email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$current_email = get_userdata( $user_id )->user_email;

		if ( $new_email !== $current_email ) {
			// Email changed - send verification
			$result = dv_request_email_verification( $user_id, $new_email );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			}
		}
	}

	// Update user
	$result = wp_update_user( $user_data );

	if ( is_wp_error( $result ) ) {
		$errors[] = $result->get_error_message();
	}

	// Update custom meta: organization
	if ( isset( $_POST['dv_organization'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		update_user_meta( $user_id, 'dv_organization', sanitize_text_field( wp_unslash( $_POST['dv_organization'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Update custom meta: phone
	if ( isset( $_POST['dv_phone'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		update_user_meta( $user_id, 'dv_phone', sanitize_text_field( wp_unslash( $_POST['dv_phone'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Update WPML language preference
	if ( isset( $_POST['user_language'] ) && dv_wpml_is_active() ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		update_user_meta( $user_id, 'icl_admin_language', sanitize_text_field( wp_unslash( $_POST['user_language'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
	}

	// Handle password change
	if ( ! empty( $_POST['new_password'] ) || ! empty( $_POST['current_password'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function dv_user_profile_shortcode() line 57 before this is invoked
		$password_result = dv_handle_password_change( $user_id );
		if ( is_wp_error( $password_result ) ) {
			$errors[] = $password_result->get_error_message();
		}
	}

	if ( ! empty( $errors ) ) {
		return '<strong>' . esc_html__( 'Error:', 'dv-private-docs' ) . '</strong><br>' . implode( '<br>', array_map( 'esc_html', $errors ) );
	}

	return '<strong>' . esc_html__( 'Profile updated successfully!', 'dv-private-docs' ) . '</strong>';
}

/**
 * Handle password change
 * Note: Nonce verification occurs before this function is called (see dv_handle_profile_update)
 * Note: Passwords are intentionally NOT sanitized - they are passed to wp_set_password() which handles them securely
 *
 * @param int $user_id User ID
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function dv_handle_password_change( $user_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified before this function is called
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords should not be sanitized, passed to wp_set_password()
	$current_password = isset( $_POST['current_password'] ) ? wp_unslash( $_POST['current_password'] ) : '';
	$new_password = isset( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : '';
	$confirm_password = isset( $_POST['confirm_password'] ) ? wp_unslash( $_POST['confirm_password'] ) : '';
	// phpcs:enable

	// All fields required
	if ( empty( $current_password ) || empty( $new_password ) || empty( $confirm_password ) ) {
		return new WP_Error( 'empty_fields', __( 'All password fields are required.', 'dv-private-docs' ) );
	}

	// Verify current password
	$user = get_userdata( $user_id );
	if ( ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
		return new WP_Error( 'wrong_password', __( 'Current password is incorrect.', 'dv-private-docs' ) );
	}

	// Check password match
	if ( $new_password !== $confirm_password ) {
		return new WP_Error( 'password_mismatch', __( 'New passwords do not match.', 'dv-private-docs' ) );
	}

	// Minimum length
	if ( strlen( $new_password ) < 8 ) {
		return new WP_Error( 'weak_password', __( 'Password must be at least 8 characters long.', 'dv-private-docs' ) );
	}

	// Update password
	wp_set_password( $new_password, $user_id );

	return true;
}

/**
 * Request email verification
 *
 * @param int    $user_id User ID
 * @param string $new_email New email address
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function dv_request_email_verification( $user_id, $new_email ) {
	// Validate email
	if ( ! is_email( $new_email ) ) {
		return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'dv-private-docs' ) );
	}

	// Check if email exists
	if ( email_exists( $new_email ) && $new_email !== get_userdata( $user_id )->user_email ) {
		return new WP_Error( 'email_exists', __( 'This email is already registered.', 'dv-private-docs' ) );
	}

	// Random token: only its hash is stored, the token itself travels in the link.
	$token = wp_generate_password( 32, false );

	// Store pending email
	update_user_meta( $user_id, 'dv_pending_email', array(
		'new_email' => $new_email,
		'token'     => wp_hash( $token ),
		'requested' => current_time( 'mysql' ),
	) );

	// Build verification URL
	$verify_url = add_query_arg( array(
		'action'  => 'dv_verify_email',
		'user_id' => $user_id,
		'token'   => $token,
	), home_url() );

	// Send verification email
	$subject = sprintf( __( 'Verify your new email address', 'dv-private-docs' ) );

	/* translators: 1: New email address, 2: Verification URL */
	$message = sprintf(
		__( "Hello,\n\nYou requested to change your email address to: %1\$s\n\nPlease click the link below to confirm this change:\n%2\$s\n\nThis link will expire in 24 hours.\n\nIf you did not request this change, please ignore this email.\n\nBest regards", 'dv-private-docs' ),
		$new_email,
		$verify_url
	);

	wp_mail( $new_email, $subject, $message );

	return true;
}

/**
 * Process email verification
 * Note: This uses token-based verification instead of nonces (token is verified against stored hash)
 */
function dv_process_email_verification() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Token-based verification used instead, token verified against stored hash
	if ( ! isset( $_GET['action'] ) || 'dv_verify_email' !== $_GET['action'] ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Token-based verification used instead, token verified against stored hash below
	$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Token-based verification used instead, token verified against stored hash below
	$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

	if ( ! $user_id || ! $token ) {
		wp_die( esc_html__( 'Invalid verification link.', 'dv-private-docs' ) );
	}

	$pending = get_user_meta( $user_id, 'dv_pending_email', true );

	if ( ! is_array( $pending ) || empty( $pending['token'] ) || ! hash_equals( $pending['token'], wp_hash( $token ) ) ) {
		wp_die( esc_html__( 'Invalid or expired verification link.', 'dv-private-docs' ) );
	}

	// Check expiration (24 hours)
	$requested_time = strtotime( $pending['requested'] );
	if ( ( current_time( 'timestamp' ) - $requested_time ) > ( 24 * HOUR_IN_SECONDS ) ) {
		delete_user_meta( $user_id, 'dv_pending_email' );
		wp_die( esc_html__( 'Verification link has expired. Please request a new one.', 'dv-private-docs' ) );
	}

	// The address may have been taken since the request.
	$owner = email_exists( $pending['new_email'] );
	if ( $owner && (int) $owner !== $user_id ) {
		delete_user_meta( $user_id, 'dv_pending_email' );
		wp_die( esc_html__( 'This email is already registered.', 'dv-private-docs' ) );
	}

	// Update email
	$updated = wp_update_user( array(
		'ID'         => $user_id,
		'user_email' => $pending['new_email'],
	) );

	if ( is_wp_error( $updated ) ) {
		wp_die( esc_html__( 'Invalid or expired verification link.', 'dv-private-docs' ) );
	}

	// Core notifies the previous address of the change (send_email_change_email).

	// Clean up
	delete_user_meta( $user_id, 'dv_pending_email' );

	wp_die( esc_html__( 'Email verified and updated successfully! You can close this window.', 'dv-private-docs' ), esc_html__( 'Success', 'dv-private-docs' ), array( 'response' => 200 ) );
}
add_action( 'init', 'dv_process_email_verification' );

/**
 * Enqueue user profile styles
 */
function dv_enqueue_profile_styles() {
	// Skip CSS if unstyled mode is enabled
	if ( get_option( 'dv_enable_unstyled_mode', false ) ) {
		return;
	}

	// Only enqueue on pages that might have the profile shortcode
	// Check if the current post/page has the shortcode
	if ( is_singular() && has_shortcode( get_post()->post_content, 'dv_user_profile' ) ) {
		wp_enqueue_style(
			'dv-private-docs-frontend',
			DV_PRIVATE_DOCS_URL . 'assets/css/frontend.css',
			array(),
			DV_PRIVATE_DOCS_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'dv_enqueue_profile_styles' );

/**
 * Add organization field to WordPress admin user profile
 *
 * @param WP_User $user User object
 */
function dv_add_organization_field_to_admin_profile( $user ) {
	$organization = get_user_meta( $user->ID, 'dv_organization', true );
	$phone        = get_user_meta( $user->ID, 'dv_phone', true );
	?>
	<h2><?php esc_html_e( 'Additional Information', 'dv-private-docs' ); ?></h2>
	<table class="form-table">
		<tr>
			<th>
				<label for="dv_organization"><?php esc_html_e( 'Organization', 'dv-private-docs' ); ?></label>
			</th>
			<td>
				<input type="text" name="dv_organization" id="dv_organization" value="<?php echo esc_attr( $organization ); ?>" class="regular-text" />
				<p class="description">
					<?php esc_html_e( 'Enter the organization or company name.', 'dv-private-docs' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="dv_phone"><?php esc_html_e( 'Phone', 'dv-private-docs' ); ?></label>
			</th>
			<td>
				<input type="tel" name="dv_phone" id="dv_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
				<p class="description">
					<?php esc_html_e( 'Enter the phone number.', 'dv-private-docs' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'dv_add_organization_field_to_admin_profile' );
add_action( 'edit_user_profile', 'dv_add_organization_field_to_admin_profile' );

/**
 * Save organization field from WordPress admin user profile
 *
 * @param int $user_id User ID
 */
function dv_save_organization_field_from_admin_profile( $user_id ) {
	// Check user capabilities
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	// Save organization field
	if ( isset( $_POST['dv_organization'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress in wp-admin/user-edit.php via check_admin_referer() before personal_options_update and edit_user_profile_update hooks fire
		update_user_meta( $user_id, 'dv_organization', sanitize_text_field( wp_unslash( $_POST['dv_organization'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress in wp-admin/user-edit.php via check_admin_referer() before personal_options_update and edit_user_profile_update hooks fire
	}

	// Save phone field
	if ( isset( $_POST['dv_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress in wp-admin/user-edit.php via check_admin_referer() before personal_options_update and edit_user_profile_update hooks fire
		update_user_meta( $user_id, 'dv_phone', sanitize_text_field( wp_unslash( $_POST['dv_phone'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress in wp-admin/user-edit.php via check_admin_referer() before personal_options_update and edit_user_profile_update hooks fire
	}
}
add_action( 'personal_options_update', 'dv_save_organization_field_from_admin_profile' );
add_action( 'edit_user_profile_update', 'dv_save_organization_field_from_admin_profile' );
