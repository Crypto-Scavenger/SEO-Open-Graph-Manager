<?php
/**
 * Uninstall handler for SEO & Open Graph Manager
 *
 * @package SEOOpenGraphManager
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'seoog_settings';

$cleanup = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT setting_value FROM %i WHERE setting_key = %s",
		$table_name,
		'cleanup_on_uninstall'
	)
);

if ( '1' === $cleanup ) {
	// Drop custom table
	$wpdb->query(
		$wpdb->prepare( "DROP TABLE IF EXISTS %i", $table_name )
	);
	
	// Delete post meta
	$wpdb->query(
		"DELETE FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE '_seoog_%'"
	);
	
	// Flush rewrite rules
	flush_rewrite_rules();
	
	// Clear object cache
	wp_cache_flush();
}
