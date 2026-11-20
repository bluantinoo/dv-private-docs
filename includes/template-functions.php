<?php
/**
 * Template Functions for DV Private Documents
 * Based on WooCommerce template system
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get template file
 *
 * Searches for template in:
 * 1. Theme: {child-theme}/dv-private-docs/{template-name}
 * 2. Theme: {parent-theme}/dv-private-docs/{template-name}
 * 3. Plugin: {plugin}/templates/{template-name}
 *
 * @param string $template_name Template file name (e.g., 'document-list.php')
 * @param array  $args          Variables to pass to template (accessed via extract())
 * @param string $template_path Path within theme (default: 'dv-private-docs')
 * @param string $default_path  Default plugin templates path
 * @return void
 */
function dv_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	// Allow args to be filtered
	$args = apply_filters( 'dv_get_template_args', $args, $template_name );

	// Locate the template file
	$template = dv_locate_template( $template_name, $template_path, $default_path );

	// Allow template path to be filtered
	$template = apply_filters( 'dv_get_template', $template, $template_name, $args, $template_path, $default_path );

	// Load the template
	dv_load_template( $template, $args );
}

/**
 * Locate template file in theme or plugin
 *
 * @param string $template_name Template file name
 * @param string $template_path Path within theme (default: 'dv-private-docs')
 * @param string $default_path  Default plugin templates path
 * @return string Template file path
 */
function dv_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	// Set default template path in theme
	if ( ! $template_path ) {
		$template_path = 'dv-private-docs/';
	}

	// Set default plugin path
	if ( ! $default_path ) {
		$default_path = DV_PRIVATE_DOCS_PATH . 'templates/';
	}

	// Look in child/parent theme first
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template if no theme override
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	// Allow filtering of template location
	return apply_filters( 'dv_locate_template', $template, $template_name, $template_path );
}

/**
 * Load template file with scoped variables
 *
 * @param string $template Template file path
 * @param array  $args     Variables to pass to template
 * @return void
 */
function dv_load_template( $template, $args = array() ) {
	// Ensure template exists
	if ( ! file_exists( $template ) ) {
		/* translators: %s: Template path */
		_doing_it_wrong(
			__FUNCTION__,
			wp_kses(
				sprintf(
					/* translators: %s: Template path */
					__( 'Template %s does not exist.', 'dv-private-docs' ),
					'<code>' . esc_html( $template ) . '</code>'
				),
				array( 'code' => array() )
			),
			esc_html( DV_PRIVATE_DOCS_VERSION )
		);
		return;
	}

	// Allow filtering before load
	$args = apply_filters( 'dv_load_template_args', $args, $template );

	// Extract variables into local scope
	if ( is_array( $args ) && ! empty( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract_ok -- Intentional for template scope
		extract( $args );
	}

	// Allow hooking before template
	do_action( 'dv_before_template_part', $template, $args );

	// Load the template
	include $template;

	// Allow hooking after template
	do_action( 'dv_after_template_part', $template, $args );
}

/**
 * Get template HTML (with output buffering)
 *
 * @param string $template_name Template file name
 * @param array  $args          Variables to pass to template
 * @param string $template_path Path within theme
 * @param string $default_path  Default plugin templates path
 * @return string Template HTML
 */
function dv_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	dv_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}
