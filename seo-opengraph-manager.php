<?php
/**
 * Plugin Name: SEO & Open Graph Manager
 * Description: Automatically generate Open Graph and SEO meta tags from post metadata with configurable defaults, sitemap.xml, and robots.txt management
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: seo-opengraph-manager
 * Domain Path: /languages
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'SEOOG_VERSION', '1.0.0' );
define( 'SEOOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOOG_URL', plugin_dir_url( __FILE__ ) );
define( 'SEOOG_TABLE_SETTINGS', 'seoog_settings' );

// Include classes
require_once SEOOG_DIR . 'includes/class-database.php';
require_once SEOOG_DIR . 'includes/class-core.php';
require_once SEOOG_DIR . 'includes/class-admin.php';
require_once SEOOG_DIR . 'includes/class-sitemap.php';

/**
 * Initialize plugin
 */
function seoog_init() {
	$database = new SEOOG_Database();
	$core = new SEOOG_Core( $database );
	$sitemap = new SEOOG_Sitemap( $database );
	
	if ( is_admin() ) {
		new SEOOG_Admin( $database );
	}
}
add_action( 'plugins_loaded', 'seoog_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, array( 'SEOOG_Database', 'activate' ) );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, array( 'SEOOG_Database', 'deactivate' ) );
