<?php
/**
 * Frontend shortcodes for DV Private Documents
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shortcodes
 */
function dv_register_shortcodes() {
	add_shortcode( 'dv_private_docs', 'dv_private_docs_list_shortcode' );
	add_shortcode( 'dv_private_doc_view', 'dv_private_doc_view_shortcode' );
}
add_action( 'init', 'dv_register_shortcodes' );


/**
 * Shortcode: [dv_private_docs]
 * Displays list of documents assigned to current user
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function dv_private_docs_list_shortcode( $atts ) {
	// Get view URL from language-aware settings
	$view_url = dv_get_page_url_for_lang_option( 'dv_view_page', 0 );

	// Fallback to current page if no setting configured
	if ( ! $view_url ) {
		$view_url = get_permalink();
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		// Check if embedded login is enabled
		$enable_embedded = get_option( 'dv_enable_embedded_login', false );

		if ( $enable_embedded ) {
			// Show embedded login form
			return dv_render_embedded_login_form();
		}

		// Check if we should show login message
		$show_login_message = get_option( 'dv_show_login_message', false );

		if ( ! $show_login_message ) {
			return '';
		}

		// Use template for login notice
		return dv_get_template_html(
			'login-notice.php',
			array(
				'message'            => __( 'You must be logged in to view your documents.', 'dv-private-docs' ),
				'redirect_to'        => wp_login_url( get_permalink() ),
				'show_embedded_form' => false,
			)
		);
	}

	$current_user_id = get_current_user_id();
	$is_admin = current_user_can( 'manage_options' );

	// Build query for user's documents
	$args = array(
		'post_type'      => 'dv_private_doc',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'   => 'dv_assigned_user_id',
				'value' => $current_user_id,
				'type'  => 'NUMERIC',
			),
		),
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	// Admins see all documents
	if ( $is_admin ) {
		unset( $args['meta_query'] );
	}

	// WPML: Filter by current language if CPT is translatable (prevents duplicates)
	if ( dv_wpml_is_active() && dv_is_cpt_translatable( 'dv_private_doc' ) ) {
		$current_lang = dv_get_current_lang();
		if ( $current_lang ) {
			$args['suppress_filters'] = false; // Ensure WPML filters run
			// Note: WPML automatically filters queries when suppress_filters = false
			// Alternatively, explicitly set language: $args['lang'] = $current_lang;
		}
	}

	$query = new WP_Query( $args );

	// Use template for document list
	return dv_get_template_html(
		'document-list.php',
		array(
			'documents'       => $query,
			'current_user_id' => $current_user_id,
			'is_admin'        => $is_admin,
			'view_url'        => $view_url,
		)
	);
}

/**
 * Shortcode: [dv_private_doc_view]
 * Displays single document view with download and comments
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function dv_private_doc_view_shortcode( $atts ) {
	// Get back URL from language-aware settings
	$back_url = dv_get_page_url_for_lang_option( 'dv_list_page', 0 );

	// Fallback to home URL if no setting configured
	if ( ! $back_url ) {
		$back_url = home_url();
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		// Check if embedded login is enabled
		$enable_embedded = get_option( 'dv_enable_embedded_login', false );

		if ( $enable_embedded ) {
			// Show embedded login form
			return dv_render_embedded_login_form();
		}

		// Use template for login notice
		return dv_get_template_html(
			'login-notice.php',
			array(
				'message'            => __( 'You must be logged in to view this document.', 'dv-private-docs' ),
				'redirect_to'        => wp_login_url( get_permalink() ),
				'show_embedded_form' => false,
			)
		);
	}

	// Get document ID from query string
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for read-only content display, no data modification; sanitized with absint() and access controlled via dv_user_can_access_doc()
	$doc_id = isset( $_GET['doc'] ) ? absint( $_GET['doc'] ) : 0;

	if ( ! $doc_id ) {
		return dv_get_template_html(
			'login-notice.php',
			array(
				'message'            => __( 'No document specified.', 'dv-private-docs' ),
				'redirect_to'        => dv_get_page_url_for_lang_option( 'dv_list_page', 0 ) ?: home_url(),
				'show_embedded_form' => false,
			)
		);
	}

	// Check access
	if ( ! dv_user_can_access_doc( $doc_id ) ) {
		return dv_get_template_html(
			'login-notice.php',
			array(
				'message'            => __( 'You do not have permission to view this document.', 'dv-private-docs' ),
				'redirect_to'        => '',
				'show_embedded_form' => false,
			)
		);
	}

	// Get the document
	$document = get_post( $doc_id );

	if ( ! $document || 'dv_private_doc' !== $document->post_type ) {
		return '<p>' . esc_html__( 'Document not found.', 'dv-private-docs' ) . '</p>';
	}

	// Generate download URL
	$download_url = add_query_arg(
		array(
			'action'   => 'dv_private_doc_download',
			'doc'      => $doc_id,
			'_wpnonce' => wp_create_nonce( 'dv_download_' . $doc_id ),
		),
		admin_url( 'admin-post.php' )
	);

	// Prepare comments data
	$has_comments  = get_comments_number( $doc_id ) > 0;
	$comments_open = comments_open( $doc_id );

	$all_comments = array();
	if ( $has_comments || $comments_open ) {
		$comments_args = array(
			'post_id' => $doc_id,
			'status'  => 'approve',
			'orderby' => 'comment_date',
			'order'   => 'ASC',
		);

		$all_comments = get_comments( $comments_args );

		// Add current user's unapproved comments
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$user_unapproved = get_comments(
				array(
					'post_id' => $doc_id,
					'status'  => 'hold',
					'user_id' => $user_id,
					'orderby' => 'comment_date',
					'order'   => 'ASC',
				)
			);

			if ( $user_unapproved ) {
				$all_comments = array_merge( $all_comments, $user_unapproved );
				usort(
					$all_comments,
					function ( $a, $b ) {
						return strtotime( $a->comment_date ) - strtotime( $b->comment_date );
					}
				);
			}
		}

		// Force flat display
		foreach ( $all_comments as $comment ) {
			$comment->comment_parent = 0;
		}
	}

	// Use template for document view
	return dv_get_template_html(
		'document-view.php',
		array(
			'doc_id'        => $doc_id,
			'document'      => $document,
			'back_url'      => $back_url,
			'download_url'  => $download_url,
			'has_file'      => dv_document_has_file( $doc_id ),
			'comments_data' => array(
				'has_comments'  => $has_comments,
				'comments_open' => $comments_open,
				'all_comments'  => $all_comments,
			),
		)
	);
}


/**
 * Custom comment display with moderation notice
 *
 * @param object $comment Comment object
 * @param array  $args Arguments
 * @param int    $depth Comment depth
 */
function dv_custom_comment_display( $comment, $args, $depth ) {
	$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
	?>
	<<?php echo esc_html( $tag ); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent', $comment ); ?>>
		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body" >
			<footer class="comment-meta" >
				<div class="comment-author vcard" >
					<?php
					if ( 0 !== $args['avatar_size'] ) {
						echo get_avatar( $comment, $args['avatar_size'], '', '', array( 'style' => 'border-radius: 50%;' ) );
					}
					?>
					<div>
						<strong><?php echo get_comment_author_link( $comment ); ?></strong>
						<div class="comment-time">
							<time datetime="<?php comment_time( 'c' ); ?>">
								<?php
								/* translators: 1: Comment date, 2: Comment time */
								printf( esc_html__( '%1$s at %2$s', 'dv-private-docs' ), esc_html( get_comment_date( '', $comment ) ), esc_html( get_comment_time() ) );
								?>
							</time>
						</div>
					</div>
				</div>
			</footer>

			<?php if ( '0' == $comment->comment_approved ) : ?>
				<p class="comment-moderation">
					<strong>⏳ <?php esc_html_e( 'Your comment is awaiting moderation.', 'dv-private-docs' ); ?></strong>
				</p>
			<?php endif; ?>

			<div class="comment-content" >
				<?php comment_text(); ?>
			</div>

		</article>
	<?php
}

/**
 * Enqueue frontend shortcode styles
 */
function dv_enqueue_shortcode_styles() {
	// Skip CSS if unstyled mode is enabled
	if ( get_option( 'dv_enable_unstyled_mode', false ) ) {
		return;
	}

	// Check if current post/page has any of our shortcodes
	if ( is_singular() ) {
		$post = get_post();
		if ( $post && ( has_shortcode( $post->post_content, 'dv_private_docs' ) || has_shortcode( $post->post_content, 'dv_private_doc_view' ) ) ) {
			wp_enqueue_style(
				'dv-private-docs-frontend',
				DV_PRIVATE_DOCS_URL . 'assets/css/frontend.css',
				array(),
				DV_PRIVATE_DOCS_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'dv_enqueue_shortcode_styles' );
