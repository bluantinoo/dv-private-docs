<?php
/**
 * Template: Document List Empty State
 *
 * Displayed when there are no documents available.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/document-list-empty.php
 *
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="dv-private-docs-empty">
	<p><?php esc_html_e( 'No documents available at the moment.', 'dv-private-docs' ); ?></p>
</div>
