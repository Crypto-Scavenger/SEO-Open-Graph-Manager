<?php
/**
 * Core functionality for SEO & Open Graph Manager
 *
 * @package SEOOpenGraphManager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles meta tag generation
 */
class SEOOG_Core {

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
		
		add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );
		add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 10, 2 );
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
	 * Output meta tags in wp_head
	 */
	public function output_meta_tags() {
		$settings = $this->get_settings();
		
		// Get current post/page data
		$post_id = get_queried_object_id();
		$is_singular = is_singular();
		
		// Open Graph tags
		$this->output_open_graph_tags( $post_id, $is_singular, $settings );
		
		// SEO meta tags
		$this->output_seo_tags( $post_id, $is_singular, $settings );
		
		// JSON-LD structured data
		if ( '1' === $settings['seo_enable_jsonld'] && $is_singular ) {
			$this->output_jsonld( $post_id );
		}
	}

	/**
	 * Output Open Graph tags
	 *
	 * @param int   $post_id Post ID
	 * @param bool  $is_singular Is singular
	 * @param array $settings Settings
	 */
	private function output_open_graph_tags( $post_id, $is_singular, $settings ) {
		echo "\n<!-- Open Graph Meta Tags by SEO & Open Graph Manager -->\n";
		
		// og:site_name
		$site_name = $settings['og_site_name'] ?? get_bloginfo( 'name' );
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
		
		// og:locale
		echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
		
		if ( $is_singular && $post_id ) {
			// Get post data
			$post = get_post( $post_id );
			
			// og:type
			$og_type = get_post_meta( $post_id, '_seoog_og_type', true );
			if ( empty( $og_type ) ) {
				$og_type = $settings['og_default_type'] ?? 'article';
			}
			echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
			
			// og:title
			$og_title = get_post_meta( $post_id, '_seoog_og_title', true );
			if ( empty( $og_title ) ) {
				$og_title = get_the_title( $post_id );
			}
			echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
			
			// og:description
			$og_description = get_post_meta( $post_id, '_seoog_og_description', true );
			if ( empty( $og_description ) ) {
				$og_description = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( $post->post_content, 30, '...' );
			}
			echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
			
			// og:url
			echo '<meta property="og:url" content="' . esc_url( get_permalink( $post_id ) ) . '">' . "\n";
			
			// og:image
			$og_image = get_post_meta( $post_id, '_seoog_og_image', true );
			if ( empty( $og_image ) && has_post_thumbnail( $post_id ) ) {
				$og_image = get_the_post_thumbnail_url( $post_id, 'large' );
			}
			if ( empty( $og_image ) && ! empty( $settings['og_default_image'] ) ) {
				$og_image = $settings['og_default_image'];
			}
			if ( ! empty( $og_image ) ) {
				echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
			}
			
			// article:published_time
			if ( 'article' === $og_type ) {
				echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . "\n";
				echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '">' . "\n";
				echo '<meta property="article:author" content="' . esc_attr( get_the_author_meta( 'display_name', $post->post_author ) ) . '">' . "\n";
			}
		} else {
			// Homepage or archive
			echo '<meta property="og:type" content="website">' . "\n";
			echo '<meta property="og:title" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( get_bloginfo( 'description' ) ) . '">' . "\n";
			echo '<meta property="og:url" content="' . esc_url( home_url() ) . '">' . "\n";
			
			if ( ! empty( $settings['og_default_image'] ) ) {
				echo '<meta property="og:image" content="' . esc_url( $settings['og_default_image'] ) . '">' . "\n";
			}
		}
		
		// Twitter Card
		$twitter_card = $settings['og_twitter_card'] ?? 'summary_large_image';
		echo '<meta name="twitter:card" content="' . esc_attr( $twitter_card ) . '">' . "\n";
		
		if ( ! empty( $settings['og_twitter_site'] ) ) {
			echo '<meta name="twitter:site" content="' . esc_attr( $settings['og_twitter_site'] ) . '">' . "\n";
		}
	}

	/**
	 * Output SEO meta tags
	 *
	 * @param int   $post_id Post ID
	 * @param bool  $is_singular Is singular
	 * @param array $settings Settings
	 */
	private function output_seo_tags( $post_id, $is_singular, $settings ) {
		echo "\n<!-- SEO Meta Tags by SEO & Open Graph Manager -->\n";
		
		if ( $is_singular && $post_id ) {
			$post = get_post( $post_id );
			
			// meta description
			$seo_description = get_post_meta( $post_id, '_seoog_seo_description', true );
			if ( empty( $seo_description ) ) {
				$seo_description = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( $post->post_content, 30, '...' );
			}
			echo '<meta name="description" content="' . esc_attr( $seo_description ) . '">' . "\n";
			
			// meta author
			echo '<meta name="author" content="' . esc_attr( get_the_author_meta( 'display_name', $post->post_author ) ) . '">' . "\n";
			
			// Canonical URL
			echo '<link rel="canonical" href="' . esc_url( get_permalink( $post_id ) ) . '">' . "\n";
		} else {
			// Homepage description
			$description = $settings['seo_default_description'] ?? get_bloginfo( 'description' );
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( home_url() ) . '">' . "\n";
		}
	}

	/**
	 * Output JSON-LD structured data
	 *
	 * @param int $post_id Post ID
	 */
	private function output_jsonld( $post_id ) {
		if ( ! $post_id ) {
			return;
		}
		
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		
		$jsonld = array(
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'headline' => get_the_title( $post_id ),
			'datePublished' => get_the_date( 'c', $post_id ),
			'dateModified' => get_the_modified_date( 'c', $post_id ),
			'author' => array(
				'@type' => 'Person',
				'name' => get_the_author_meta( 'display_name', $post->post_author ),
			),
		);
		
		// Add image if available
		if ( has_post_thumbnail( $post_id ) ) {
			$jsonld['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
		}
		
		// Add description
		if ( has_excerpt( $post_id ) ) {
			$jsonld['description'] = get_the_excerpt( $post_id );
		}
		
		echo "\n<!-- JSON-LD Structured Data -->\n";
		echo '<script type="application/ld+json">';
		echo wp_json_encode( $jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		echo '</script>' . "\n";
	}

	/**
	 * Filter robots.txt content
	 *
	 * @param string $output Robots.txt output
	 * @param bool   $public Whether site is public
	 * @return string
	 */
	public function filter_robots_txt( $output, $public ) {
		$settings = $this->get_settings();
		
		if ( ! empty( $settings['robots_txt'] ) ) {
			return $settings['robots_txt'];
		}
		
		return $output;
	}
}
