<?php
/**
 * Anti-bot check for the embedded login form: Google reCAPTCHA v3 or Cloudflare Turnstile
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anti-bot service chosen in the settings.
 *
 * Sites set up before the provider setting existed only had the reCAPTCHA
 * checkbox: that choice is still honoured until the setting is saved.
 *
 * @return string 'recaptcha', 'turnstile' or '' (none).
 */
function dv_login_captcha_provider() {
	$provider = get_option( 'dv_login_captcha', false );

	if ( false === $provider ) {
		return get_option( 'dv_enable_recaptcha', false ) ? 'recaptcha' : '';
	}

	return in_array( $provider, array( 'recaptcha', 'turnstile' ), true ) ? $provider : '';
}

/**
 * Site key of the chosen service.
 *
 * @return string
 */
function dv_login_captcha_site_key() {
	switch ( dv_login_captcha_provider() ) {
		case 'recaptcha':
			return (string) get_option( 'dv_recaptcha_site_key', '' );
		case 'turnstile':
			return (string) get_option( 'dv_turnstile_site_key', '' );
	}

	return '';
}

/**
 * Secret key of the chosen service.
 *
 * @return string
 */
function dv_login_captcha_secret_key() {
	switch ( dv_login_captcha_provider() ) {
		case 'recaptcha':
			return (string) get_option( 'dv_recaptcha_secret_key', '' );
		case 'turnstile':
			return (string) get_option( 'dv_turnstile_secret_key', '' );
	}

	return '';
}

/**
 * Whether the visitor has given the consent the chosen service needs.
 *
 * Only checked when Complianz is installed. reCAPTCHA defaults to the
 * `marketing` category, Turnstile to `functional`; both are filterable.
 * `functional` means "no consent needed" and is not asked to Complianz: with
 * opt-in consent, cmplz_has_consent( 'functional' ) is false until the visitor
 * has used the banner, which would block the login on a first visit.
 *
 * @return bool True when no consent is needed or it has been given.
 */
function dv_login_captcha_has_consent() {
	if ( ! function_exists( 'cmplz_has_consent' ) ) {
		return true;
	}

	if ( 'turnstile' === dv_login_captcha_provider() ) {
		$category = apply_filters( 'dv_turnstile_consent_category', 'functional' );
	} else {
		$category = apply_filters( 'dv_recaptcha_consent_category', 'marketing' );
	}

	if ( 'functional' === $category ) {
		return true;
	}

	return (bool) cmplz_has_consent( $category );
}

/**
 * Whether the widget/script of the chosen service should load on this request.
 *
 * @return bool
 */
function dv_login_captcha_active() {
	return '' !== dv_login_captcha_provider()
		&& '' !== dv_login_captcha_site_key()
		&& '' !== dv_login_captcha_secret_key()
		&& dv_login_captcha_has_consent();
}

/**
 * Kept for themes and snippets written against 1.0.0.
 *
 * @return bool
 */
function dv_should_use_recaptcha() {
	return 'recaptcha' === dv_login_captcha_provider() && dv_login_captcha_active();
}

/**
 * Field the token of the chosen service is posted in.
 *
 * @return string
 */
function dv_login_captcha_response_field() {
	// Turnstile adds this input to the form by itself.
	return 'turnstile' === dv_login_captcha_provider() ? 'cf-turnstile-response' : 'dv_recaptcha_response';
}

/**
 * Verify a token with the chosen service.
 *
 * A token is accepted only if the service confirms it, it was issued for the
 * `login` action and for this site's host; reCAPTCHA also needs a score.
 *
 * @param string $token Token posted by the form.
 * @return bool
 */
function dv_login_captcha_verify( $token ) {
	$provider = dv_login_captcha_provider();
	$secret   = dv_login_captcha_secret_key();

	if ( '' === $provider || '' === $secret || '' === $token ) {
		return false;
	}

	$endpoint = 'turnstile' === $provider
		? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
		: 'https://www.google.com/recaptcha/api/siteverify';

	$response = wp_remote_post( $endpoint, array(
		'timeout' => 10,
		'body'    => array(
			'secret'   => $secret,
			'response' => $token,
		),
	) );

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['success'] ) ) {
		return false;
	}

	if ( ! isset( $body['action'] ) || 'login' !== $body['action'] ) {
		return false;
	}

	if ( ! isset( $body['hostname'] ) || strtolower( (string) $body['hostname'] ) !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
		return false;
	}

	if ( 'recaptcha' === $provider ) {
		/**
		 * Minimum reCAPTCHA v3 score (0.0 - 1.0) for a login to go through.
		 *
		 * @param float $min_score Default 0.5.
		 */
		$min_score = (float) apply_filters( 'dv_recaptcha_min_score', 0.5 );

		return isset( $body['score'] ) && (float) $body['score'] >= $min_score;
	}

	return true;
}

/**
 * Kept for themes and snippets written against 1.0.0.
 *
 * @param string $response Token.
 * @return bool
 */
function dv_verify_recaptcha( $response ) {
	return 'recaptcha' === dv_login_captcha_provider() && dv_login_captcha_verify( (string) $response );
}

/**
 * Messages shown on the form, by status.
 *
 * @param string $status 'consent' or 'captcha'.
 * @return string
 */
function dv_login_captcha_message( $status ) {
	$turnstile = 'turnstile' === dv_login_captcha_provider();

	if ( 'consent' === $status ) {
		return $turnstile
			? __( 'To log in you must accept the cookies required by the anti-bot protection (Cloudflare Turnstile).', 'dv-private-docs' )
			: __( 'You must accept marketing cookies to log in (required by anti-bot protection).', 'dv-private-docs' );
	}

	return $turnstile
		? __( 'Anti-bot verification failed. Please try again.', 'dv-private-docs' )
		: __( 'reCAPTCHA verification failed. Please try again.', 'dv-private-docs' );
}

/**
 * Warn administrators when a service is chosen but its keys are missing.
 *
 * In that state the widget cannot load while the check still runs, so every
 * login from the embedded form is refused.
 */
function dv_login_captcha_missing_keys_notice() {
	if ( ! current_user_can( 'manage_options' ) || '' === dv_login_captcha_provider() ) {
		return;
	}

	if ( '' !== dv_login_captcha_site_key() && '' !== dv_login_captcha_secret_key() ) {
		return;
	}

	$settings_url = admin_url( 'edit.php?post_type=dv_private_doc&page=dv-private-docs-settings' );
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'DV Private Documents: the anti-bot protection has no keys.', 'dv-private-docs' ); ?></strong>
			<?php esc_html_e( 'Every login from the embedded form is refused until both keys are filled in, or the protection is set to None.', 'dv-private-docs' ); ?>
			<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'dv-private-docs' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'dv_login_captcha_missing_keys_notice' );
