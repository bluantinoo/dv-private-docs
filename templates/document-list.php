<?php
/**
 * Template: Document List
 *
 * Displays a list of documents assigned to the current user.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/document-list.php
 *
 * @var WP_Query $documents       Query object with documents
 * @var int      $current_user_id Current user ID
 * @var bool     $is_admin        Is user admin?
 * @var string   $view_url        URL to view page
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;

if ( $documents->have_posts() ) : ?>
	<div class="dv-private-docs-list">
		<?php
		while ( $documents->have_posts() ) :
			$documents->the_post();

			dv_get_template(
				'document-list-item.php',
				array(
					'doc_id'          => get_the_ID(),
					'view_url'        => $view_url,
					'current_user_id' => $current_user_id,
					'is_admin'        => $is_admin,
				)
			);
		endwhile;
		?>
	</div>
	<?php wp_reset_postdata(); ?>
<?php else : ?>
	<?php dv_get_template( 'document-list-empty.php' ); ?>
<?php endif; ?>
