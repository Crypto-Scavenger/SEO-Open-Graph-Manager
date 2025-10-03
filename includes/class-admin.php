<?php
/**
 * Admin functionality for SEO & Open Graph Manager
 *
 * @package SEOOpenGraphManager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin interface
 */
class SEOOG_Admin {

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
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_seoog_save_settings', array( $this, 'save_settings' ) );
		
		// Meta box for posts
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ) );
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
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'SEO & Open Graph Manager', 'seo-opengraph-manager' ),
			__( 'SEO Manager', 'seo-opengraph-manager' ),
			'manage_options',
			'seo-opengraph-manager',
			array( $this, 'render_admin_page' ),
			'dashicons-chart-line',
			65
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_seo-opengraph-manager' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'seoog-admin',
			SEOOG_URL . 'assets/admin.css',
			array(),
			SEOOG_VERSION
		);
		
		wp_enqueue_script(
			'seoog-admin',
			SEOOG_URL . 'assets/admin.js',
			array( 'jquery' ),
			SEOOG_VERSION,
			true
		);
		
		wp_enqueue_media();
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'seo-opengraph-manager' ) );
		}
		
		$settings = $this->get_settings();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'opengraph';
		?>
		<div class="wrap seoog-admin">
			<h1><i class="dashicons dashicons-chart-line"></i> <?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'seo-opengraph-manager' ); ?></p>
				</div>
			<?php endif; ?>
			
			<h2 class="nav-tab-wrapper">
				<a href="?page=seo-opengraph-manager&tab=opengraph" class="nav-tab <?php echo 'opengraph' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Open Graph', 'seo-opengraph-manager' ); ?>
				</a>
				<a href="?page=seo-opengraph-manager&tab=seo" class="nav-tab <?php echo 'seo' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'SEO Settings', 'seo-opengraph-manager' ); ?>
				</a>
				<a href="?page=seo-opengraph-manager&tab=sitemap" class="nav-tab <?php echo 'sitemap' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Sitemap', 'seo-opengraph-manager' ); ?>
				</a>
				<a href="?page=seo-opengraph-manager&tab=robots" class="nav-tab <?php echo 'robots' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Robots.txt', 'seo-opengraph-manager' ); ?>
				</a>
				<a href="?page=seo-opengraph-manager&tab=advanced" class="nav-tab <?php echo 'advanced' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Advanced', 'seo-opengraph-manager' ); ?>
				</a>
			</h2>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'seoog_save_settings', 'seoog_nonce' ); ?>
				<input type="hidden" name="action" value="seoog_save_settings">
				<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>">
				
				<?php
				switch ( $active_tab ) {
					case 'opengraph':
						$this->render_opengraph_tab( $settings );
						break;
					case 'seo':
						$this->render_seo_tab( $settings );
						break;
					case 'sitemap':
						$this->render_sitemap_tab( $settings );
						break;
					case 'robots':
						$this->render_robots_tab( $settings );
						break;
					case 'advanced':
						$this->render_advanced_tab( $settings );
						break;
				}
				?>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Open Graph tab
	 *
	 * @param array $settings Settings
	 */
	private function render_opengraph_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="og_site_name"><?php esc_html_e( 'Site Name', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<input type="text" id="og_site_name" name="og_site_name" value="<?php echo esc_attr( $settings['og_site_name'] ?? '' ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Your site name for Open Graph tags.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="og_default_image"><?php esc_html_e( 'Default Image', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<input type="text" id="og_default_image" name="og_default_image" value="<?php echo esc_attr( $settings['og_default_image'] ?? '' ); ?>" class="regular-text">
					<button type="button" class="button seoog-upload-image"><?php esc_html_e( 'Select Image', 'seo-opengraph-manager' ); ?></button>
					<p class="description"><?php esc_html_e( 'Default image for posts without featured images.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="og_default_type"><?php esc_html_e( 'Default Type', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<select id="og_default_type" name="og_default_type">
						<option value="article" <?php selected( $settings['og_default_type'] ?? 'article', 'article' ); ?>><?php esc_html_e( 'Article', 'seo-opengraph-manager' ); ?></option>
						<option value="website" <?php selected( $settings['og_default_type'] ?? 'article', 'website' ); ?>><?php esc_html_e( 'Website', 'seo-opengraph-manager' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Default Open Graph type for content.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="og_twitter_card"><?php esc_html_e( 'Twitter Card Type', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<select id="og_twitter_card" name="og_twitter_card">
						<option value="summary" <?php selected( $settings['og_twitter_card'] ?? 'summary_large_image', 'summary' ); ?>><?php esc_html_e( 'Summary', 'seo-opengraph-manager' ); ?></option>
						<option value="summary_large_image" <?php selected( $settings['og_twitter_card'] ?? 'summary_large_image', 'summary_large_image' ); ?>><?php esc_html_e( 'Summary Large Image', 'seo-opengraph-manager' ); ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="og_twitter_site"><?php esc_html_e( 'Twitter Site Handle', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<input type="text" id="og_twitter_site" name="og_twitter_site" value="<?php echo esc_attr( $settings['og_twitter_site'] ?? '' ); ?>" class="regular-text" placeholder="@yoursite">
					<p class="description"><?php esc_html_e( 'Your Twitter username (optional).', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render SEO tab
	 *
	 * @param array $settings Settings
	 */
	private function render_seo_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="seo_default_description"><?php esc_html_e( 'Default Description', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<textarea id="seo_default_description" name="seo_default_description" rows="3" class="large-text"><?php echo esc_textarea( $settings['seo_default_description'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Default meta description for your site.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Enable JSON-LD', 'seo-opengraph-manager' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="seo_enable_jsonld" value="1" <?php checked( '1', $settings['seo_enable_jsonld'] ?? '1' ); ?>>
						<?php esc_html_e( 'Enable structured data (JSON-LD) for better search results', 'seo-opengraph-manager' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Sitemap tab
	 *
	 * @param array $settings Settings
	 */
	private function render_sitemap_tab( $settings ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Enable Sitemap', 'seo-opengraph-manager' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="sitemap_enable" value="1" <?php checked( '1', $settings['sitemap_enable'] ?? '1' ); ?>>
						<?php esc_html_e( 'Generate sitemap.xml', 'seo-opengraph-manager' ); ?>
					</label>
					<p class="description">
						<?php
						printf(
							/* translators: %s: sitemap URL */
							esc_html__( 'Sitemap will be available at: %s', 'seo-opengraph-manager' ),
							'<a href="' . esc_url( home_url( '/sitemap.xml' ) ) . '" target="_blank">' . esc_html( home_url( '/sitemap.xml' ) ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Include Post Types', 'seo-opengraph-manager' ); ?>
				</th>
				<td>
					<?php foreach ( $post_types as $post_type ) : ?>
						<label style="display:block;margin-bottom:8px;">
							<input type="checkbox" name="sitemap_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, (array) ( $settings['sitemap_post_types'] ?? array() ), true ) ); ?>>
							<?php echo esc_html( $post_type->labels->name ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="sitemap_exclude_ids"><?php esc_html_e( 'Exclude IDs', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<input type="text" id="sitemap_exclude_ids" name="sitemap_exclude_ids" value="<?php echo esc_attr( implode( ',', (array) ( $settings['sitemap_exclude_ids'] ?? array() ) ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Comma-separated post IDs to exclude from sitemap.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Robots.txt tab
	 *
	 * @param array $settings Settings
	 */
	private function render_robots_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="robots_txt"><?php esc_html_e( 'Robots.txt Content', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<textarea id="robots_txt" name="robots_txt" rows="10" class="large-text code"><?php echo esc_textarea( $settings['robots_txt'] ?? '' ); ?></textarea>
					<p class="description">
						<?php
						printf(
							/* translators: %s: robots.txt URL */
							esc_html__( 'Edit your virtual robots.txt file. View at: %s', 'seo-opengraph-manager' ),
							'<a href="' . esc_url( home_url( '/robots.txt' ) ) . '" target="_blank">' . esc_html( home_url( '/robots.txt' ) ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Advanced tab
	 *
	 * @param array $settings Settings
	 */
	private function render_advanced_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Cleanup on Uninstall', 'seo-opengraph-manager' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="cleanup_on_uninstall" value="1" <?php checked( '1', $settings['cleanup_on_uninstall'] ?? '0' ); ?>>
						<?php esc_html_e( 'Remove all plugin data when uninstalling', 'seo-opengraph-manager' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save settings
	 */
	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'seo-opengraph-manager' ) );
		}
		
		if ( ! isset( $_POST['seoog_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seoog_nonce'] ) ), 'seoog_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'seo-opengraph-manager' ) );
		}
		
		// Sanitize and save settings
		$settings = array(
			'og_site_name' => isset( $_POST['og_site_name'] ) ? sanitize_text_field( wp_unslash( $_POST['og_site_name'] ) ) : '',
			'og_default_image' => isset( $_POST['og_default_image'] ) ? esc_url_raw( wp_unslash( $_POST['og_default_image'] ) ) : '',
			'og_default_type' => isset( $_POST['og_default_type'] ) ? sanitize_text_field( wp_unslash( $_POST['og_default_type'] ) ) : 'article',
			'og_twitter_card' => isset( $_POST['og_twitter_card'] ) ? sanitize_text_field( wp_unslash( $_POST['og_twitter_card'] ) ) : 'summary_large_image',
			'og_twitter_site' => isset( $_POST['og_twitter_site'] ) ? sanitize_text_field( wp_unslash( $_POST['og_twitter_site'] ) ) : '',
			'seo_default_description' => isset( $_POST['seo_default_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seo_default_description'] ) ) : '',
			'seo_enable_jsonld' => isset( $_POST['seo_enable_jsonld'] ) ? '1' : '0',
			'sitemap_enable' => isset( $_POST['sitemap_enable'] ) ? '1' : '0',
			'sitemap_post_types' => isset( $_POST['sitemap_post_types'] ) && is_array( $_POST['sitemap_post_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['sitemap_post_types'] ) ) : array(),
			'sitemap_exclude_ids' => isset( $_POST['sitemap_exclude_ids'] ) ? array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $_POST['sitemap_exclude_ids'] ) ) ) ) : array(),
			'robots_txt' => isset( $_POST['robots_txt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['robots_txt'] ) ) : '',
			'cleanup_on_uninstall' => isset( $_POST['cleanup_on_uninstall'] ) ? '1' : '0',
		);
		
		foreach ( $settings as $key => $value ) {
			$this->database->save_setting( $key, $value );
		}
		
		$active_tab = isset( $_POST['active_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['active_tab'] ) ) : 'opengraph';
		
		wp_safe_redirect( add_query_arg(
			array(
				'page' => 'seo-opengraph-manager',
				'tab' => $active_tab,
				'settings-updated' => 'true',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		$post_types = get_post_types( array( 'public' => true ) );
		
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'seoog_meta_box',
				__( 'SEO & Open Graph', 'seo-opengraph-manager' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render meta box
	 *
	 * @param WP_Post $post Post object
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'seoog_save_post_meta', 'seoog_post_nonce' );
		
		$og_title = get_post_meta( $post->ID, '_seoog_og_title', true );
		$og_description = get_post_meta( $post->ID, '_seoog_og_description', true );
		$og_image = get_post_meta( $post->ID, '_seoog_og_image', true );
		$og_type = get_post_meta( $post->ID, '_seoog_og_type', true );
		$seo_description = get_post_meta( $post->ID, '_seoog_seo_description', true );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="seoog_og_title"><?php esc_html_e( 'Open Graph Title', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<input type="text" id="seoog_og_title" name="seoog_og_title" value="<?php echo esc_attr( $og_title ); ?>" class="large-text" placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>">
					<p class="description"><?php esc_html_e( 'Leave empty to use post title.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="seoog_og_description"><?php esc_html_e( 'Open Graph Description', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<textarea id="seoog_og_description" name="seoog_og_description" rows="3" class="large-text"><?php echo esc_textarea( $og_description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave empty to use excerpt.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="seoog_og_image"><?php esc_html_e( 'Open Graph Image', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<input type="text" id="seoog_og_image" name="seoog_og_image" value="<?php echo esc_attr( $og_image ); ?>" class="large-text">
					<button type="button" class="button seoog-upload-image"><?php esc_html_e( 'Select Image', 'seo-opengraph-manager' ); ?></button>
					<p class="description"><?php esc_html_e( 'Leave empty to use featured image.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="seoog_og_type"><?php esc_html_e( 'Open Graph Type', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<select id="seoog_og_type" name="seoog_og_type">
						<option value=""><?php esc_html_e( 'Use default', 'seo-opengraph-manager' ); ?></option>
						<option value="article" <?php selected( $og_type, 'article' ); ?>><?php esc_html_e( 'Article', 'seo-opengraph-manager' ); ?></option>
						<option value="website" <?php selected( $og_type, 'website' ); ?>><?php esc_html_e( 'Website', 'seo-opengraph-manager' ); ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="seoog_seo_description"><?php esc_html_e( 'SEO Meta Description', 'seo-opengraph-manager' ); ?></label>
				</th>
				<td>
					<textarea id="seoog_seo_description" name="seoog_seo_description" rows="3" class="large-text"><?php echo esc_textarea( $seo_description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave empty to use excerpt.', 'seo-opengraph-manager' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save post meta
	 *
	 * @param int $post_id Post ID
	 */
	public function save_post_meta( $post_id ) {
		if ( ! isset( $_POST['seoog_post_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seoog_post_nonce'] ) ), 'seoog_save_post_meta' ) ) {
			return;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		$meta_fields = array(
			'seoog_og_title' => '_seoog_og_title',
			'seoog_og_description' => '_seoog_og_description',
			'seoog_og_image' => '_seoog_og_image',
			'seoog_og_type' => '_seoog_og_type',
			'seoog_seo_description' => '_seoog_seo_description',
		);
		
		foreach ( $meta_fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				update_post_meta( $post_id, $meta_key, $value );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}
	}
}
