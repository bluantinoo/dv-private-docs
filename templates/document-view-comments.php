<?php
/**
 * Template: Document View Comments
 *
 * Displays the comments section with list and form.
 *
 * Override this template by copying it to:
 * your-theme/dv-private-docs/document-view-comments.php
 *
 * @var int   $doc_id        Document post ID
 * @var bool  $has_comments  Are there comments?
 * @var bool  $comments_open Are comments open?
 * @var array $all_comments  Array of comment objects
 * @package DV_Private_Docs
 */

defined( 'ABSPATH' ) || exit;

if ( $comments_open || $has_comments ) : ?>
	<div id="comments" class="dv-doc-comments">
		<h2><?php esc_html_e( 'Comments', 'dv-private-docs' ); ?></h2>

		<?php if ( $has_comments ) : ?>
			<div class="dv-comments-list">
				<?php
				wp_list_comments(
					array(
						'style'        => 'div',
						'avatar_size'  => 50,
						'max_depth'    => 1,
						'hierarchical' => false,
						'callback'     => 'dv_custom_comment_display',
					),
					$all_comments
				);
				?>
			</div>
		<?php endif; ?>

		<?php if ( $comments_open ) : ?>
			<div class="dv-comment-form">
				<?php
				comment_form(
					array(
						'comment_field' => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your comment', 'dv-private-docs' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
						'title_reply'   => esc_html__( 'Add a comment', 'dv-private-docs' ),
						'label_submit'  => esc_html__( 'Submit Comment', 'dv-private-docs' ),
						'logged_in_as'  => '',
					),
					$doc_id
				);
				?>
			</div>
		<?php elseif ( $has_comments ) : ?>
			<div class="dv-conversation-closed">
				<p><?php esc_html_e( 'This conversation is closed.', 'dv-private-docs' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
