# SEO & Open Graph Manager

A comprehensive WordPress plugin that automatically generates Open Graph and SEO meta tags from post metadata, with configurable defaults, sitemap.xml generation, and robots.txt management.

## Features

### Open Graph Tags
- **Automatic Generation**: Uses post title, featured image, excerpt, author, and dates
- **Custom Overrides**: Per-post custom fields for title, description, image, and type
- **Twitter Card Support**: Configurable Twitter card types
- **Fallback System**: Falls back to defaults when post-specific data unavailable

### SEO Meta Tags
- **Auto Description**: Generates meta description from excerpt or content
- **Canonical URLs**: Automatic canonical URL generation
- **Author Meta**: Includes author information
- **JSON-LD Structured Data**: Schema.org Article markup for better search results

### Sitemap.xml
- **Dynamic Generation**: Automatically generates sitemap at `/sitemap.xml`
- **Post Type Selection**: Choose which post types to include
- **Exclude Specific Posts**: Comma-separated IDs to exclude
- **Priority & Frequency**: Intelligent defaults with customizable settings

### Robots.txt
- **Virtual Editor**: Edit robots.txt through WordPress admin
- **Automatic Sitemap**: Includes sitemap URL automatically
- **Custom Rules**: Full control over crawler directives

### Per-Post Controls
- **Meta Box**: Custom fields on every post/page editor
- **Override Defaults**: Set custom Open Graph and SEO values per post
- **Image Selector**: WordPress media library integration

## Installation

1. Upload the `seo-opengraph-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **SEO Manager** in the admin menu to configure settings

## Usage

### Global Configuration

Navigate to **SEO Manager** in the WordPress admin to configure global settings:

#### Open Graph Tab
- **Site Name**: Your site's name for social media
- **Default Image**: Fallback image for posts without featured images
- **Default Type**: Choose between "article" or "website"
- **Twitter Card Type**: Summary or Summary Large Image
- **Twitter Handle**: Your Twitter username (optional)

#### SEO Settings Tab
- **Default Description**: Meta description for homepage and archives
- **Enable JSON-LD**: Toggle structured data output

#### Sitemap Tab
- **Enable Sitemap**: Turn sitemap generation on/off
- **Include Post Types**: Select which content types appear in sitemap
- **Exclude IDs**: Comma-separated post IDs to exclude
- **View Sitemap**: Link to generated sitemap.xml

#### Robots.txt Tab
- **Edit Content**: Full editor for robots.txt rules
- **View Live**: Link to current robots.txt output

#### Advanced Tab
- **Cleanup on Uninstall**: Choose whether to remove all data when plugin deleted

### Per-Post Configuration

On each post/page edit screen, find the **SEO & Open Graph** meta box:

1. **Open Graph Title**: Override default title (leave empty to use post title)
2. **Open Graph Description**: Custom description for social media
3. **Open Graph Image**: Custom image (leave empty to use featured image)
4. **Open Graph Type**: Choose article or website
5. **SEO Meta Description**: Custom meta description for search engines

All fields are optional and fall back to intelligent defaults.

## File Structure

```
seo-opengraph-manager/
├── seo-opengraph-manager.php    # Main plugin file (initialization)
├── README.md                     # This file
├── uninstall.php                 # Cleanup on uninstall
├── index.php                     # Security stub
├── assets/
│   ├── admin.css                 # Admin interface styles
│   ├── admin.js                  # Admin scripts (media uploader)
│   └── index.php                 # Security stub
└── includes/
    ├── class-database.php        # Database operations
    ├── class-core.php            # Meta tag generation
    ├── class-admin.php           # Admin interface
    ├── class-sitemap.php         # Sitemap generation
    └── index.php                 # Security stub
```

## Requirements

- **WordPress**: 6.2 or higher (uses `%i` placeholder for table names)
- **PHP**: 7.4 or higher
- **Permissions**: `manage_options` capability for settings, `edit_posts` for meta boxes

## Technical Details

### Database Structure

The plugin creates a custom table `{prefix}_seoog_settings`:

```sql
CREATE TABLE {prefix}_seoog_settings (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    setting_key varchar(191) NOT NULL,
    setting_value longtext,
    PRIMARY KEY (id),
    UNIQUE KEY setting_key (setting_key)
)
```

### Stored Settings

- Open Graph defaults (site_name, default_image, default_type, twitter settings)
- SEO defaults (description, JSON-LD enable)
- Sitemap configuration (enable, post_types, exclude_ids)
- Robots.txt content
- Cleanup preference

### Hooks Used

**Actions:**
- `wp_head` (priority 1) - Outputs meta tags
- `init` - Registers sitemap rewrite rules
- `template_redirect` - Serves sitemap.xml
- `admin_menu` - Adds admin page
- `add_meta_boxes` - Adds post meta box
- `save_post` - Saves post meta fields

**Filters:**
- `robots_txt` - Filters robots.txt output

### Security Features

- Nonce verification on all forms and AJAX requests
- Capability checks (`manage_options` for settings, `edit_posts` for meta)
- SQL injection prevention using `$wpdb->prepare()` with placeholders
- Output escaping with context-appropriate functions
- Input sanitization for all user data

### Performance

- **Lazy Loading**: Settings loaded only when needed
- **Query Optimization**: Sitemap uses optimized WP_Query args
- **Rewrite Rules**: Sitemap uses WordPress rewrite system
- **Caching**: Settings cached in memory during request

## How It Works

### Meta Tag Generation

1. **Check Context**: Determines if singular post/page or homepage/archive
2. **Get Post Data**: Retrieves post metadata (title, excerpt, dates, author, image)
3. **Check Custom Fields**: Looks for per-post overrides in post meta
4. **Apply Defaults**: Falls back to configured defaults if no custom data
5. **Output Tags**: Generates properly escaped meta tags in `<head>`

### Open Graph Flow

```
Post Title → Custom OG Title? → Yes → Use custom
                              → No  → Use post title

Featured Image → Custom OG Image? → Yes → Use custom
                                   → No  → Has featured? → Yes → Use featured
                                                         → No  → Use default

Excerpt → Custom OG Description? → Yes → Use custom
                                 → No  → Has excerpt? → Yes → Use excerpt
                                                      → No  → Use content preview
```

### Sitemap Generation

1. **Rewrite Rule**: `/sitemap.xml` maps to query var `seoog_sitemap=1`
2. **Template Redirect**: Checks for query var on every request
3. **Generate XML**: Queries selected post types with optimizations
4. **Output**: Returns XML with proper headers and exits

### Robots.txt

1. **Filter Hook**: Hooks into `robots_txt` filter
2. **Return Custom**: If custom content set, returns that
3. **Default**: Otherwise returns WordPress default

## Compatibility

- All modern themes (uses WordPress standards)
- Compatible with caching plugins
- Works with multisite installations
- No conflicts with major SEO plugins (runs independently)

## Frequently Asked Questions

### Will this conflict with other SEO plugins?

The plugin runs independently and outputs standard Open Graph and SEO tags. However, for best results, use only one SEO plugin at a time. This plugin is designed as a complete solution.

### Can I use custom Open Graph images per post?

Yes. In the post editor, find the "SEO & Open Graph" meta box and use the "Select Image" button to choose a custom image. Leave empty to use the featured image.

### Does this work with custom post types?

Yes. The meta box appears on all public post types, and you can include any post type in the sitemap configuration.

### Where is the sitemap located?

Your sitemap is accessible at `https://yoursite.com/sitemap.xml`. The URL is also shown in the Sitemap settings tab.

### Can I disable specific features?

Yes. Each major feature (Open Graph, SEO, Sitemap, JSON-LD) can be controlled independently through the settings pages.

### Does this affect page load speed?

No. The plugin uses optimized queries, lazy loading, and minimal overhead. Meta tags are generated once per page load with minimal processing.

### What happens to my data if I uninstall?

By default, settings are preserved. Enable "Cleanup on Uninstall" in Advanced settings if you want all data removed when the plugin is deleted.

## Changelog

### 1.0.0
- Initial release
- Open Graph tag generation from post metadata
- SEO meta tag generation
- JSON-LD structured data support
- Dynamic sitemap.xml generation
- Virtual robots.txt editor
- Per-post custom fields
- Custom database table for settings
- Tabbed admin interface
- Media library integration

## License

This plugin is released under the GPL v2 or later license.
