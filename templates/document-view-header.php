<?php
/**
 * Template: Document View Header
 *
 * Displays the document header section with title, date, description, and download button.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/document-view-header.php
 *
 * @var int    $doc_id       Document post ID
 * @var object $document     WP_Post object
 * @var string $download_url Secure download URL
 * @var bool   $has_file     Does document have file?
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;

// Prepare file data
$file_path         = dv_get_document_file_path( $doc_id );
$original_filename = dv_get_document_original_filename( $doc_id );
$file_extension    = pathinfo( $original_filename, PATHINFO_EXTENSION );
$file_size         = $file_path ? dv_get_file_size( $file_path ) : '';
?>

<div class="dv-doc-header">
	<?php if ( current_user_can( 'edit_post', $doc_id ) ) : ?>
		<span class="dv-doc-admin-link">
			<a href="<?php echo esc_url( get_edit_post_link( $doc_id ) ); ?>" target="_blank">
				<?php esc_html_e( 'Edit Document', 'dv-private-docs' ); ?>
			</a>
	</span>
	<?php endif; ?>
	<?php if ( has_post_thumbnail( $doc_id ) ) : ?>
		<div class="dv-doc-thumbnail-wrapper">
			<?php echo get_the_post_thumbnail( $doc_id, 'medium' ); ?>
		</div>
	<?php endif; ?>

	<div class="dv-doc-header-content">
		<h1><?php echo esc_html( get_the_title( $doc_id ) ); ?></h1>

		<p class="dv-doc-date">
			<?php
			/* translators: %s: Document date */
			printf( esc_html__( 'Published on %s', 'dv-private-docs' ), esc_html( get_the_date( get_option( 'date_format' ), $doc_id ) ) );
			?>
		</p>

		<?php if ( $document->post_content ) : ?>
			<div class="dv-doc-description">
				<?php echo wp_kses_post( wpautop( $document->post_content ) ); ?>
			</div>
		<?php endif; ?>

		<!-- Download button -->
		<?php if ( $has_file ) : ?>
			<p class="dv-download-button">
				<span class="dv-doc-file">
					<span class="dv-doc-filetype">
						<?php echo esc_html( strtoupper( $file_extension ) ); ?>
					</span>
					<span class="dv-doc-filesize">
						<?php if ( $file_size ) : ?>
							<?php echo esc_html( $file_size ); ?>
						<?php endif; ?>
					</span>
				</span>
				<a href="<?php echo esc_url( $download_url ); ?>" class="button button-primary button-large">
					<?php esc_html_e( 'Download Document', 'dv-private-docs' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p class="dv-file-unavailable">
				<?php esc_html_e( 'File is not available for download.', 'dv-private-docs' ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>
