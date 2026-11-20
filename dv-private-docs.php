<?php
/**
 * Plugin Name:       DV Private Documents
 * Plugin URI:        https://github.com/bluantinoo/dv-private-docs
 * Description:       Private document area: administrators assign each document to one user, who downloads it through a protected link and can comment on it. Files never go through the Media Library.
 * Version:           1.3.2
 * Author:            Diego Vagnoli
 * Author URI:        https://diegovagnoli.it
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dv-private-docs
 * Domain Path:       /languages
 * Requires at least: 6.3
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Update URI:        https://github.com/bluantinoo/dv-private-docs
 *
 * @package DV_Private_Docs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'DV_PRIVATE_DOCS_VERSION', '1.3.2' );
define( 'DV_PRIVATE_DOCS_FILE', __FILE__ );
define( 'DV_PRIVATE_DOCS_PATH', plugin_dir_path( __FILE__ ) );
define( 'DV_PRIVATE_DOCS_URL', plugin_dir_url( __FILE__ ) );
define( 'DV_PRIVATE_DOCS_BASENAME', plugin_basename( __FILE__ ) );

// Private storage directory (relative to WP_CONTENT_DIR)
define( 'DV_PRIVATE_DOCS_DIR', 'private-docs' );

/*
 * Updates come from the GitHub releases of the plugin, not from wordpress.org
 * (the Update URI header keeps WordPress from looking there). Only the zip
 * attached to a release is installed, never the source archive of the tag.
 */
require_once DV_PRIVATE_DOCS_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

( static function (): void {
	$checker = \YahnisElsts\PluginUpdateChecker\v6\PucFactory::buildUpdateChecker(
		'https://github.com/bluantinoo/dv-private-docs/',
		DV_PRIVATE_DOCS_FILE,
		'dv-private-docs'
	);
	$checker->getVcsApi()->enableReleaseAssets(
		'/^dv-private-docs\.zip$/',
		\YahnisElsts\PluginUpdateChecker\v6p0\Vcs\Api::REQUIRE_RELEASE_ASSETS
	);

	// Releases only: without this the library falls back to the latest tag and
	// then to the main branch, whose code was never built or released.
	add_filter(
		'puc_vcs_update_detection_strategies-dv-private-docs',
		static function ( $strategies ) {
			return array_intersect_key( $strategies, array( 'latest_release' => true ) );
		}
	);
} )();

// Loaded before the activation hook, which relies on it.
require_once DV_PRIVATE_DOCS_PATH . 'includes/storage-protection.php';

/**
 * Plugin activation hook
 * Creates the private documents directory and protects it
 */
function dv_private_docs_activate() {
	dv_ensure_private_dir_protection();

	// Re-run the checks on the next admin load rather than waiting out the
	// throttling transients left over from a previous activation.
	delete_transient( 'dv_private_docs_protection_checked' );
	delete_transient( 'dv_private_docs_probe_checked' );
}
register_activation_hook( __FILE__, 'dv_private_docs_activate' );

/**
 * Load plugin text domain for translations
 *
 * Note: While WordPress.org automatically loads translations since WP 4.6+,
 * this function is kept for compatibility with:
 * - Installations from sources other than WordPress.org
 * - Custom/local translations in /languages directory
 * - Development and testing environments
 */
function dv_private_docs_load_textdomain() {
	load_plugin_textdomain( 'dv-private-docs', false, dirname( DV_PRIVATE_DOCS_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'dv_private_docs_load_textdomain' );

/**
 * Include required files
 */
require_once DV_PRIVATE_DOCS_PATH . 'includes/helpers.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/cpt.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/attachment-cleanup.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/metaboxes.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/admin-columns.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/template-functions.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/frontend-shortcodes.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/download-handler.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/comments-guard.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/notifications.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/settings.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/login-captcha.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/login-form.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/redirects.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/user-profile.php';
require_once DV_PRIVATE_DOCS_PATH . 'includes/user-menu.php';
