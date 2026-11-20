<?php
/**
 * Template: Login Notice
 *
 * Displays a notice when user needs to log in or doesn't have access.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/login-notice.php
 *
 * @var string $message             Message to display
 * @var string $redirect_to         Redirect URL after login
 * @var bool   $show_embedded_form  Show embedded login form?
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="dv-private-docs-notice">
	<p><?php echo esc_html( $message ); ?></p>

	<?php if ( ! empty( $redirect_to ) ) : ?>
		<p>
			<a href="<?php echo esc_url( dv_get_page_url_for_lang_option( 'dv_list_page', 0 ) ?: home_url() ); ?>" class="button">
				<?php esc_html_e( 'View All Documents', 'dv-private-docs' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
