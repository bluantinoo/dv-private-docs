<?php
/**
 * Template: Document View
 *
 * Displays a single document with download and comments.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/document-view.php
 *
 * @var int    $doc_id        Document post ID
 * @var object $document      WP_Post object
 * @var string $back_url      Back to list URL
 * @var string $download_url  Secure download URL
 * @var bool   $has_file      Does document have file?
 * @var array  $comments_data Comments configuration
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="dv-private-doc-view">
	<!-- Back link -->
	<p class="dv-back-link">
		<a href="<?php echo esc_url( $back_url ); ?>">
			← <?php esc_html_e( 'Back to documents', 'dv-private-docs' ); ?>
		</a>
	</p>

	<!-- Document header -->
	<?php
	dv_get_template(
		'document-view-header.php',
		array(
			'doc_id'       => $doc_id,
			'document'     => $document,
			'download_url' => $download_url,
			'has_file'     => $has_file,
		)
	);
	?>

	<!-- Comments section -->
	<?php
	dv_get_template(
		'document-view-comments.php',
		array(
			'doc_id'        => $doc_id,
			'has_comments'  => $comments_data['has_comments'],
			'comments_open' => $comments_data['comments_open'],
			'all_comments'  => $comments_data['all_comments'],
		)
	);
	?>
</div>
