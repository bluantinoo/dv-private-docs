<?php
/**
 * Template: Document List Item
 *
 * Displays a single document card in the list.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/document-list-item.php
 *
 * @var int    $doc_id          Document post ID
 * @var string $view_url        Base view URL
 * @var int    $current_user_id Current user ID
 * @var bool   $is_admin        Is user admin?
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;

// Prepare data
$assigned_user_id  = get_post_meta( $doc_id, 'dv_assigned_user_id', true );
$file_path         = dv_get_document_file_path( $doc_id );
$original_filename = dv_get_document_original_filename( $doc_id );
$file_extension    = pathinfo( $original_filename, PATHINFO_EXTENSION );
$file_size         = $file_path ? dv_get_file_size( $file_path ) : '';
$doc_url           = add_query_arg( 'doc', $doc_id, $view_url );
?>

<div class="dv-private-doc-item"
	data-doc-id="<?php echo esc_attr( $doc_id ); ?>"
	data-user-id="<?php echo esc_attr( $assigned_user_id ); ?>"
	data-file-type="<?php echo esc_attr( $file_extension ); ?>">

	<?php if ( has_post_thumbnail( $doc_id ) ) : ?>
		<div class="dv-doc-thumbnail">
			<?php echo get_the_post_thumbnail( $doc_id, 'thumbnail' ); ?>
		</div>
	<?php endif; ?>

	<div class="dv-doc-content">
		<h3><?php echo esc_html( get_the_title( $doc_id ) ); ?></h3>

		<p class="dv-doc-meta">
			<span class="dv-doc-pubdate">
			<?php
			/* translators: %s: Document date */
			printf( esc_html__( 'Published on %s', 'dv-private-docs' ), esc_html( get_the_date( '', $doc_id ) ) );
			?>
			</span>
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
			<?php
			$comments_count = get_comments_number( $doc_id );
			if ( $comments_count > 0 ) :
				?>
				<span class="dv-doc-comments-count">
					<a href="<?php echo esc_url( $doc_url ); ?>#comments">
						<?php
						/* translators: %s: Number of comments */
						printf(
							esc_html( _n( '%s comment', '%s comments', $comments_count, 'dv-private-docs' ) ),
							number_format_i18n( $comments_count )
						);
						?>
					</a>
				</span>
			<?php endif; ?>
		</p>

		<?php if ( $is_admin && $assigned_user_id ) : ?>
			<?php $assigned_user = get_user_by( 'id', $assigned_user_id ); ?>
			<?php if ( $assigned_user ) : ?>
				<p class="dv-doc-assigned-notice">
					<strong><?php esc_html_e( 'Assigned to:', 'dv-private-docs' ); ?></strong>
					<?php echo esc_html( $assigned_user->display_name ); ?>
					(<?php echo esc_html( $assigned_user->user_email ); ?>)
				</p>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( has_excerpt( $doc_id ) ) : ?>
			<div class="dv-doc-excerpt">
				<?php echo wp_kses_post( get_the_excerpt( $doc_id ) ); ?>
			</div>
		<?php endif; ?>

		<a href="<?php echo esc_url( $doc_url ); ?>" class="button button-primary">
			<?php esc_html_e( 'Open Document', 'dv-private-docs' ); ?>
		</a>

	</div>
</div>
