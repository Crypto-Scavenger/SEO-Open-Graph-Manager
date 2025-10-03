<?php
/**
 * Database operations for SEO & Open Graph Manager
 *
 * @package SEOOpenGraphManager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all database operations
 */
class SEOOG_Database {

	/**
	 * Settings cache
	 *
	 * @var array|null
	 */
	private $settings_cache = null;

	/**
	 * Whether table exists check has been performed
	 *
	 * @var bool|null
	 */
	private $table_verified = null;

	/**
	 * Get table name
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . SEOOG_TABLE_SETTINGS;
	}

	/**
	 * Ensure table exists before operations
	 *
	 * @return bool True if table exists or was created, false otherwise
	 */
	private function ensure_table_exists() {
		if ( true === $this->table_verified ) {
			return true;
		}

		global $wpdb;
		$table_name = $this->get_table_name();
		
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );

		if ( $table_name === $table_exists ) {
			$this->table_verified = true;
			return true;
		}

		// Table doesn't exist, create it
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			) %s',
			$table_name,
			$charset_collate
		);
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		
		// Verify creation
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );

		if ( $table_name === $table_exists ) {
			$this->table_verified = true;
			$this->initialize_defaults();
			return true;
		}

		return false;
	}

	/**
	 * Activate plugin
	 */
	public static function activate() {
		$instance = new self();
		$instance->ensure_table_exists();
		
		// Flush rewrite rules for sitemap
		flush_rewrite_rules();
	}

	/**
	 * Initialize default settings
	 */
	private function initialize_defaults() {
		$defaults = array(
			'og_site_name' => get_bloginfo( 'name' ),
			'og_default_image' => '',
			'og_default_type' => 'article',
			'og_twitter_card' => 'summary_large_image',
			'og_twitter_site' => '',
			'seo_default_description' => get_bloginfo( 'description' ),
			'seo_enable_jsonld' => '1',
			'sitemap_enable' => '1',
			'sitemap_post_types' => array( 'post', 'page' ),
			'sitemap_exclude_ids' => array(),
			'robots_txt' => "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: " . home_url( '/sitemap.xml' ),
			'cleanup_on_uninstall' => '0',
		);
		
		foreach ( $defaults as $key => $value ) {
			if ( false === $this->get_setting( $key ) ) {
				$this->save_setting( $key, $value );
			}
		}
	}

	/**
	 * Deactivate plugin
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Get setting value
	 *
	 * @param string $key Setting key
	 * @param mixed  $default Default value
	 * @return mixed
	 */
	public function get_setting( $key, $default = false ) {
		if ( ! $this->ensure_table_exists() ) {
			return $default;
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$value = $wpdb->get_var( $wpdb->prepare(
			'SELECT setting_value FROM %i WHERE setting_key = %s',
			$table,
			$key
		) );
		
		if ( null === $value ) {
			return $default;
		}
		
		return maybe_unserialize( $value );
	}

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function get_all_settings() {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}

		if ( ! $this->ensure_table_exists() ) {
			return array();
		}
		
		global $wpdb;
		$table = $this->get_table_name();
		
		$results = $wpdb->get_results( $wpdb->prepare(
			'SELECT setting_key, setting_value FROM %i',
			$table
		) );
		
		if ( ! is_array( $results ) ) {
			return array();
		}
		
		$settings = array();
		foreach ( $results as $row ) {
			$settings[ $row->setting_key ] = maybe_unserialize( $row->setting_value );
		}
		
		$this->settings_cache = $settings;
		return $settings;
	}

	/**
	 * Save setting
	 *
	 * @param string $key Setting key
	 * @param mixed  $value Setting value
	 * @return bool
	 */
	public function save_setting( $key, $value ) {
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$result = $wpdb->replace(
			$table,
			array(
				'setting_key' => $key,
				'setting_value' => maybe_serialize( $value ),
			),
			array( '%s', '%s' )
		);
		
		if ( false === $result ) {
			return false;
		}
		
		// Clear cache
		$this->settings_cache = null;
		
		return true;
	}

	/**
	 * Delete setting
	 *
	 * @param string $key Setting key
	 * @return bool
	 */
	public function delete_setting( $key ) {
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$result = $wpdb->delete(
			$table,
			array( 'setting_key' => $key ),
			array( '%s' )
		);
		
		if ( false === $result ) {
			return false;
		}
		
		// Clear cache
		$this->settings_cache = null;
		
		return true;
	}
}
