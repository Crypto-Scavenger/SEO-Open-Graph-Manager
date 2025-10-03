<?php
/**
 * Sitemap functionality for SEO & Open Graph Manager
 *
 * @package SEOOpenGraphManager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles sitemap.xml generation
 */
class SEOOG_Sitemap {

	/**
	 * Database instance
	 *
	 * @var SEOOG_Database
	 */
	private $database;

	/**
	 * Settings cache
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Constructor
	 *
	 * @param SEOOG_Database $database Database instance
	 */
	public function __construct( $database ) {
		$this->database = $database;
		
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'serve_sitemap' ) );
	}

	/**
	 * Get settings (lazy loading)
	 *
	 * @return array
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->database->get_all_settings();
		}
		return $this->settings;
	}

	/**
	 * Add rewrite rules for sitemap
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?seoog_sitemap=1', 'top' );
		add_rewrite_tag( '%seoog_sitemap%', '([^&]+)' );
	}

	/**
	 * Serve sitemap when requested
	 */
	public function serve_sitemap() {
		$sitemap_query = get_query_var( 'seoog_sitemap' );
		
		if ( '1' !== $sitemap_query ) {
			return;
		}
		
		$settings = $this->get_settings();
		
		if ( '1' !== $settings['sitemap_enable'] ) {
			wp_die( esc_html__( 'Sitemap is disabled', 'seo-opengraph-manager' ) );
		}
		
		header( 'Content-Type: application/xml; charset=utf-8' );
		echo $this->generate_sitemap();
		exit;
	}

	/**
	 * Generate sitemap XML
	 *
	 * @return string
	 */
	private function generate_sitemap() {
		$settings = $this->get_settings();
		$post_types = $settings['sitemap_post_types'] ?? array( 'post', 'page' );
		$exclude_ids = $settings['sitemap_exclude_ids'] ?? array();
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		
		// Homepage
		$xml .= $this->get_url_entry( home_url(), get_lastpostmodified( 'gmt' ), '1.0', 'daily' );
		
		// Posts and pages
		foreach ( $post_types as $post_type ) {
			$args = array(
				'post_type' => $post_type,
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'orderby' => 'modified',
				'order' => 'DESC',
				'post__not_in' => $exclude_ids,
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);
			
			$query = new WP_Query( $args );
			
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					
					$priority = 'page' === $post_type ? '0.8' : '0.6';
					$changefreq = 'page' === $post_type ? 'monthly' : 'weekly';
					
					$xml .= $this->get_url_entry(
						get_permalink(),
						get_the_modified_date( 'c' ),
						$priority,
						$changefreq
					);
				}
			}
			
			wp_reset_postdata();
		}
		
		$xml .= '</urlset>';
		
		return $xml;
	}

	/**
	 * Get sitemap URL entry
	 *
	 * @param string $loc URL
	 * @param string $lastmod Last modified date
	 * @param string $priority Priority
	 * @param string $changefreq Change frequency
	 * @return string
	 */
	private function get_url_entry( $loc, $lastmod, $priority, $changefreq ) {
		$xml = '  <url>' . "\n";
		$xml .= '    <loc>' . esc_url( $loc ) . '</loc>' . "\n";
		$xml .= '    <lastmod>' . esc_html( $lastmod ) . '</lastmod>' . "\n";
		$xml .= '    <changefreq>' . esc_html( $changefreq ) . '</changefreq>' . "\n";
		$xml .= '    <priority>' . esc_html( $priority ) . '</priority>' . "\n";
		$xml .= '  </url>' . "\n";
		
		return $xml;
	}
}
