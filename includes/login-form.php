<?php
/**
 * Embedded login form: posts to wp-login.php, optional reCAPTCHA or Turnstile
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marker field that identifies a login posted from the embedded form.
 */
const DV_LOGIN_FORM_FIELD = 'dv_login_form';

/**
 * URL of the current request, without the login status parameter.
 *
 * @return string
 */
function dv_login_current_url() {
	// Scheme and host come from the site address, never from the request headers.
	$home        = wp_parse_url( home_url() );
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	if ( '' === $request_uri || '/' !== $request_uri[0] ) {
		$request_uri = '/';
	}

	$url = $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . $request_uri;

	return remove_query_arg( 'dv_login', wp_validate_redirect( $url, home_url( '/' ) ) );
}

/**
 * Whether a login from the embedded form is being processed right now.
 *
 * True only inside dv_handle_embedded_login(), so the anti-bot check and the
 * redirect below never apply to a login posted anywhere else.
 *
 * @param bool|null $set Internal: start or end the login.
 * @return bool
 */
function dv_is_embedded_login_request( $set = null ) {
	static $active = false;

	if ( null !== $set ) {
		$active = (bool) $set;
	}

	return $active;
}

/**
 * Message for the status the embedded form was sent back with.
 *
 * Only fixed strings: the query value selects one, it is never printed.
 *
 * @return string Empty when there is nothing to show.
 */
function dv_login_status_message() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag
	$status = isset( $_GET['dv_login'] ) ? sanitize_key( wp_unslash( $_GET['dv_login'] ) ) : '';

	switch ( $status ) {
		case 'failed':
			return __( 'Invalid username or password.', 'dv-private-docs' );
		case 'captcha':
		case 'consent':
			return dv_login_captcha_message( $status );
		case 'other':
			return __( 'The login could not be completed here. Please use the login page.', 'dv-private-docs' );
	}

	return '';
}

/**
 * Render embedded login form
 *
 * The form posts back to the page it is on, where dv_handle_embedded_login()
 * signs the user in with wp_signon(): two-factor plugins, login limiters and
 * any other check hooked to the core login apply without special handling,
 * while visitors never go through wp-login.php (and whatever guards it).
 *
 * @return string HTML output of login form
 */
function dv_render_embedded_login_form() {
	$provider          = dv_login_captcha_provider();
	$captcha_active    = dv_login_captcha_active();
	$site_key          = dv_login_captcha_site_key();
	$consent_missing   = '' !== $provider && ! dv_login_captcha_has_consent();
	$current_url       = dv_login_current_url();
	$status_message    = dv_login_status_message();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag
	$status            = isset( $_GET['dv_login'] ) ? sanitize_key( wp_unslash( $_GET['dv_login'] ) ) : '';

	ob_start();
	?>
	<?php do_action( 'dv_after_login_form_wrapper' ); ?>

	<div class="dv-login-form-wrapper">

		<h3><?php esc_html_e( 'Access Required', 'dv-private-docs' ); ?></h3>

		<?php if ( $consent_missing ) : ?>
			<div class="dv-login-consent-notice" role="alert">
				<p>
					<?php
					if ( 'turnstile' === $provider ) {
						esc_html_e( 'To log in you must accept the cookies required by the anti-bot protection (Cloudflare Turnstile).', 'dv-private-docs' );
					} else {
						esc_html_e( 'To log in you must accept marketing cookies, required by the anti-bot protection (Google reCAPTCHA).', 'dv-private-docs' );
					}
					?>
					<a href="#" class="dv-open-consent"><?php esc_html_e( 'Manage cookie preferences', 'dv-private-docs' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<form id="dv-login-form" method="post" action="<?php echo esc_url( $current_url ); ?>">
			<input type="hidden" name="<?php echo esc_attr( DV_LOGIN_FORM_FIELD ); ?>" value="1" />
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_url ); ?>" />

			<p>
				<label for="dv_username">
					<?php esc_html_e( 'Username or Email', 'dv-private-docs' ); ?>
				</label>
				<input type="text" name="log" id="dv_username" required autocomplete="username" />
			</p>

			<p>
				<label for="dv_password">
					<?php esc_html_e( 'Password', 'dv-private-docs' ); ?>
				</label>
				<input type="password" name="pwd" id="dv_password" required autocomplete="current-password" />
			</p>

			<?php
			// Fields that other plugins add to the standard login form (e.g. a captcha),
			// required by their checks in the core login.
			do_action( 'login_form' );
			?>

			<p>
				<label>
					<input type="checkbox" name="rememberme" value="forever" />
					<span><?php esc_html_e( 'Remember Me', 'dv-private-docs' ); ?></span>
				</label>
			</p>

			<?php if ( $captcha_active && 'recaptcha' === $provider ) : ?>
				<input type="hidden" name="dv_recaptcha_response" id="recaptcha_response" />
			<?php elseif ( $captcha_active && 'turnstile' === $provider ) : ?>
				<div class="cf-turnstile dv-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>" data-action="login" data-theme="<?php echo esc_attr( dv_sanitize_turnstile_theme( get_option( 'dv_turnstile_theme', 'auto' ) ) ); ?>"></div>
			<?php endif; ?>

			<div id="dv-login-message" class="<?php echo $status_message ? 'error' : ''; ?>" <?php echo $status_message ? 'role="alert"' : 'hidden'; ?>><?php echo esc_html( $status_message ); ?>
				<?php if ( 'other' === $status ) : ?>
					<a href="<?php echo esc_url( wp_login_url( $current_url ) ); ?>"><?php esc_html_e( 'Log in', 'dv-private-docs' ); ?></a>
				<?php endif; ?>
			</div>

			<p>
				<button type="submit" class="button button-primary button-large">
					<?php esc_html_e( 'Log In', 'dv-private-docs' ); ?>
				</button>
			</p>

			<p class="dv-login-links">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
					<?php esc_html_e( 'Forgot your password?', 'dv-private-docs' ); ?>
				</a>
				<?php if ( get_option( 'users_can_register' ) ) : ?>
					&nbsp;&bull;&nbsp;
					<a href="<?php echo esc_url( wp_registration_url() ); ?>">
						<?php esc_html_e( 'Register', 'dv-private-docs' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</form>

	</div>
	<?php do_action( 'dv_after_login_form' ); ?>

	<?php
	return ob_get_clean();
}

/**
 * Output content before the login form from settings option
 */
function dv_render_login_form_before_content() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key   = $current_lang ? 'dv_login_form_before_content_' . $current_lang : 'dv_login_form_before_content';

	$content = get_option( $option_key, '' );
	if ( '' === $content && $current_lang ) {
		$content = get_option( 'dv_login_form_before_content', '' );
	}

	if ( ! empty( $content ) ) {
		echo do_shortcode( wp_kses_post( $content ) );
	}
}
add_action( 'dv_after_login_form_wrapper', 'dv_render_login_form_before_content' );

/**
 * Output content after the login form from settings option
 */
function dv_render_login_form_after_content() {
	$current_lang = dv_wpml_is_active() ? dv_get_current_lang() : null;
	$option_key   = $current_lang ? 'dv_login_form_after_content_' . $current_lang : 'dv_login_form_after_content';

	$content = get_option( $option_key, '' );
	if ( '' === $content && $current_lang ) {
		$content = get_option( 'dv_login_form_after_content', '' );
	}

	if ( ! empty( $content ) ) {
		echo do_shortcode( wp_kses_post( $content ) );
	}
}
add_action( 'dv_after_login_form', 'dv_render_login_form_after_content' );

/**
 * Check the anti-bot token on logins posted from the embedded form.
 *
 * Runs after the credential checks (core at 20) and after plugins that
 * rewrite login errors, so its error is the one that reaches the form. The
 * check does not depend on the credentials: a request without a valid token
 * gets the same answer whether the password was right or wrong, so the
 * answer reveals nothing about the account.
 *
 * Only the embedded form is covered: wp-login.php itself is left to whatever
 * protects it on the site.
 *
 * @param WP_User|WP_Error|null $user Result of the previous checks.
 * @return WP_User|WP_Error|null
 */
function dv_embedded_login_check_captcha( $user ) {
	if ( ! dv_is_embedded_login_request() || '' === dv_login_captcha_provider() ) {
		return $user;
	}

	// Consent required and missing: block the login (do NOT skip the check).
	if ( ! dv_login_captcha_has_consent() ) {
		return new WP_Error( 'dv_captcha_consent', dv_login_captcha_message( 'consent' ) );
	}

	$field = dv_login_captcha_response_field();
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- login form, verified by the anti-bot service itself
	$token = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';

	if ( ! dv_login_captcha_verify( $token ) ) {
		return new WP_Error( 'dv_captcha_failed', dv_login_captcha_message( 'captcha' ) );
	}

	return $user;
}
add_filter( 'authenticate', 'dv_embedded_login_check_captcha', 50 );

/**
 * Status shown on the form for a failed login.
 *
 * A wrong name or password and the form's own anti-bot check get their own
 * message; anything else (a lockout, a two-factor plugin that needs its own
 * screen, another plugin's captcha) gets a generic one pointing to the login
 * page, because its text may say more than the form should.
 *
 * @param WP_Error $error Why the login failed.
 * @return string
 */
function dv_embedded_login_status( $error ) {
	$code = $error->get_error_code();

	if ( 'dv_captcha_consent' === $code ) {
		return 'consent';
	}
	// captcha_error: WP reCaptcha Integration, when it guards the login.
	if ( in_array( $code, array( 'dv_captcha_failed', 'captcha_error' ), true ) ) {
		return 'captcha';
	}
	if ( in_array( $code, array( 'incorrect_password', 'invalid_username', 'invalid_email', 'empty_username', 'empty_password' ), true ) ) {
		return 'failed';
	}

	return 'other';
}

/**
 * IDs of the pages that may show the embedded login form.
 *
 * The documents list, document view and profile pages from the settings, in
 * every language they are set for, plus their WPML translations.
 *
 * @return int[]
 */
function dv_login_form_page_ids() {
	global $wpdb;

	$bases = array( 'dv_list_page', 'dv_view_page', 'dv_profile_page' );
	$ids   = array();

	foreach ( $bases as $base ) {
		$ids[] = absint( get_option( $base, 0 ) );

		// Per-language copies (dv_list_page_en, ...).
		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a handful of autoloaded options
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $base . '_' ) . '%' )
		);
		foreach ( $rows as $row ) {
			$ids[] = absint( $row );
		}
	}

	$ids = array_filter( array_unique( $ids ) );

	if ( dv_wpml_is_active() ) {
		foreach ( $ids as $id ) {
			$translated = apply_filters( 'wpml_object_id', $id, 'page', false );
			if ( $translated ) {
				$ids[] = (int) $translated;
			}
		}
	}

	/**
	 * Pages where a login from the embedded form is accepted.
	 *
	 * @param int[] $ids Page IDs.
	 */
	return array_map( 'intval', array_unique( (array) apply_filters( 'dv_login_form_page_ids', $ids ) ) );
}

/**
 * Whether the current request is for a page that may show the form.
 *
 * With no pages in the settings, any single page counts, so a site that
 * placed the shortcode without configuring the plugin keeps working.
 *
 * @return bool
 */
function dv_is_login_form_page() {
	if ( ! is_singular() ) {
		return false;
	}

	$ids = dv_login_form_page_ids();

	return empty( $ids ) || in_array( (int) get_queried_object_id(), $ids, true );
}

/**
 * Keep pages with the embedded form out of other sites' frames.
 *
 * wp-login.php sends the same header; without it a page could put the form
 * in a frame and get a visitor to log in to an account of its choosing.
 */
function dv_login_form_frame_options() {
	if ( ! is_user_logged_in() && get_option( 'dv_enable_embedded_login', false ) && dv_is_login_form_page() ) {
		send_frame_options_header();
	}
}
add_action( 'template_redirect', 'dv_login_form_frame_options', 1 );

/**
 * Sign in a user from the embedded form.
 *
 * Runs early on template_redirect, once WordPress knows which page was
 * requested: only the pages that show the form accept a login, so the entry
 * point is the same set of URLs a rate limit or a log filter can target.
 * wp_signon() goes through the same filters and actions as wp-login.php
 * (authenticate, wp_login_failed, wp_login): a two-factor plugin that
 * challenges on wp_login shows its screen and stops the request here, as it
 * would on wp-login.php.
 */
function dv_handle_embedded_login() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- a login form, like wp-login.php: credentials and the anti-bot check are the verification
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST[ DV_LOGIN_FORM_FIELD ] ) ) {
		return;
	}

	// Only on a front-end page: wp-login.php, the dashboard, AJAX, REST,
	// XML-RPC and cron requests load WordPress too and fire wp_loaded.
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return;
	}
	if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
		return;
	}

	if ( ! dv_is_login_form_page() ) {
		return;
	}

	// A login posted from another site (login CSRF): browsers send Origin on
	// cross-site POSTs. No Origin (old browsers, privacy settings) is let through,
	// as wp-login.php would.
	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		$origin_host = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ), PHP_URL_HOST );
		if ( ! $origin_host || strtolower( $origin_host ) !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return;
		}
	}

	$back = isset( $_POST['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ), '' ) : '';
	if ( ! $back ) {
		$back = dv_login_current_url();
	}

	$credentials = array(
		'user_login'    => isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords must not be altered
		'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '',
		'remember'      => ! empty( $_POST['rememberme'] ),
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	// Never cache this answer.
	nocache_headers();

	dv_is_embedded_login_request( true );
	$user = wp_signon( $credentials );
	dv_is_embedded_login_request( false );

	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( add_query_arg( 'dv_login', dv_embedded_login_status( $user ), $back ) );
		exit;
	}

	// Same destination logic as wp-login.php.
	$redirect_to = apply_filters( 'login_redirect', $back, $back, $user );
	wp_safe_redirect( $redirect_to ? $redirect_to : $back );
	exit;
}
add_action( 'template_redirect', 'dv_handle_embedded_login', 2 );

/**
 * Enqueue login form scripts and styles
 */
function dv_enqueue_login_scripts() {
	if ( is_user_logged_in() ) {
		return;
	}

	$enable_embedded = get_option( 'dv_enable_embedded_login', false );

	if ( ! $enable_embedded ) {
		return;
	}

	// CSS only when unstyled mode is off; the script is needed either way.
	if ( ! get_option( 'dv_enable_unstyled_mode', false ) ) {
		wp_enqueue_style(
			'dv-private-docs-frontend',
			DV_PRIVATE_DOCS_URL . 'assets/css/frontend.css',
			array(),
			DV_PRIVATE_DOCS_VERSION
		);
	}

	// Anti-bot script, only once consent (if Complianz asks for it) is there.
	$provider       = dv_login_captcha_provider();
	$captcha_active = dv_login_captcha_active();
	$site_key       = dv_login_captcha_site_key();

	if ( $captcha_active && 'recaptcha' === $provider ) {
		wp_enqueue_script(
			'google-recaptcha',
			'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ),
			array(),
			null, // Google reCAPTCHA version is managed by Google
			false // Load in head for proper initialization
		);
	} elseif ( $captcha_active && 'turnstile' === $provider ) {
		wp_enqueue_script(
			'cloudflare-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			array(),
			null, // Turnstile version is managed by Cloudflare
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	// Enqueue JavaScript
	wp_enqueue_script(
		'dv-login-form',
		DV_PRIVATE_DOCS_URL . 'assets/js/login-form.js',
		array( 'jquery' ),
		DV_PRIVATE_DOCS_VERSION,
		true
	);

	wp_localize_script( 'dv-login-form', 'dvLogin', array(
		'recaptcha' => $captcha_active && 'recaptcha' === $provider,
		'sitekey'   => 'recaptcha' === $provider ? $site_key : '',
	) );
}
add_action( 'wp_enqueue_scripts', 'dv_enqueue_login_scripts' );
