<?php
/**
 * Plugin Name: CTLawHelp Permalinks
 * Description: Multilingual custom permalink manager with Polylang integration, per-post overrides, global redirect management, and automatic language prefix handling.
 * Version: 1.0.7
 * Author: CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Emergency disable switch - set to true if causing 404 errors
if ( ! defined( 'DISABLE_LEGAL_PERMALINKS' ) ) {
    define( 'DISABLE_LEGAL_PERMALINKS', false );
}

// Debug mode
if ( ! defined( 'LEGAL_PERMALINKS_DEBUG' ) ) {
    define( 'LEGAL_PERMALINKS_DEBUG', false );
}

// Disable interactive guide custom permalinks if needed
if ( ! defined( 'DISABLE_IG_PERMALINKS' ) ) {
    define( 'DISABLE_IG_PERMALINKS', false );
}

class LegalPermalinks {

    private static $needs_flush = false;

    public function __construct() {
        if ( defined( 'DISABLE_LEGAL_PERMALINKS' ) && DISABLE_LEGAL_PERMALINKS ) {
            return;
        }

        // Meta box
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);

        // Rewrite + redirects
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_redirects']);

        // Filters for permalink
        add_filter('post_type_link', [$this, 'filter_permalink'], 10, 3);
        add_filter('post_link', [$this, 'filter_permalink'], 10, 3);

        // Register query var for redirects
        add_action('init', [$this, 'register_query_vars'], 1);

        // Flush rewrite rules when categories or posts change
        add_action('set_object_terms', [$this, 'flush_on_category_change'], 10, 6);
        

        add_action('wp_after_insert_post', [$this, 'check_category_change_on_save'], 10, 1);
        add_action('save_post', [$this, 'immediate_flush_on_save'], 999, 1);

        // Admin UI
        add_action('admin_menu', [$this, 'add_tools_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_legal_save_row', [$this, 'ajax_save_row']);
        


        // Scheduled flush handler
        add_action('legal_permalinks_flush_rules', [$this, 'scheduled_flush']);
        
        // Flush when primary NSMI category changes (affects automatic URLs)
        add_action('updated_post_meta', [$this, 'flush_on_primary_category_change'], 10, 4);
        
        // Debug hook
        if (defined('LEGAL_PERMALINKS_DEBUG') && LEGAL_PERMALINKS_DEBUG) {
            add_action('wp', [$this, 'debug_current_request']);
        }
        
        // Add flush permalinks button to admin bar
        add_action('admin_bar_menu', [$this, 'add_flush_permalinks_admin_bar'], 999);
        add_action('wp_ajax_flush_permalinks_quick', [$this, 'ajax_flush_permalinks']);
    }
    
    /** ---------- Debug Current Request ---------- */
    public function debug_current_request() {
        if (!current_user_can('manage_options')) return;
        
        global $wp_query;
        if (is_404() && isset($_GET['debug_permalinks'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            
            // Show debug info for admins
            if (current_user_can('manage_options')) {
                echo "<div style='background: #fff; border: 2px solid red; padding: 10px; margin: 10px; position: fixed; top: 0; left: 0; z-index: 9999; max-width: 500px; font-size: 12px;'>";
                echo "<h3>Permalink Debug</h3>";
                echo "<p><strong>Request:</strong> $request_uri</p>";
                echo "<p><strong>404 Error:</strong> Page not found</p>";
                echo "<p><strong>Query Vars:</strong><br><pre>" . print_r($wp_query->query_vars, true) . "</pre></p>";
                echo "<p><strong>Post Type:</strong> " . (isset($wp_query->query_vars['post_type']) ? $wp_query->query_vars['post_type'] : 'none') . "</p>";
                echo "<p><strong>Name:</strong> " . (isset($wp_query->query_vars['name']) ? $wp_query->query_vars['name'] : 'none') . "</p>";
                echo "</div>";
            }
        }
    }



    /** ---------- Flush Triggers ---------- */
    public function flush_on_category_change($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
        $post_type = get_post_type($object_id);

        // Flush for NSMI category changes on legal content — but only when the post
        // uses an automatic (taxonomy-derived) URL. If _legal_custom_path is set the
        // canonical URL is fixed and taxonomy changes cannot affect it.
        if ($taxonomy === 'nsmi_category' && in_array($post_type, ['legal_article', 'interactive_guide'], true)) {
            if ( get_post_meta( $object_id, '_legal_custom_path', true ) ) {
                return; // Custom path is canonical; taxonomy change has no URL effect.
            }
            sort($tt_ids);
            sort($old_tt_ids);
            if ($tt_ids !== $old_tt_ids) {
                self::$needs_flush = true;
                $this->invalidate_rules_cache();
            }
        } else {
            // For other cases, only flush if post has custom permalinks
            $has_custom = get_post_meta($object_id, '_legal_custom_path', true);
            $has_redirects = get_post_meta($object_id, '_legal_redirect_paths', true);

            if ($has_custom || (!empty($has_redirects) && is_array($has_redirects))) {
                sort($tt_ids);
                sort($old_tt_ids);
                if ($tt_ids !== $old_tt_ids) {
                    self::$needs_flush = true;
                    $this->invalidate_rules_cache();
                }
            }
        }
    }

    public function immediate_flush_on_save($post_id) {
        if ( defined( 'LATB_DISABLE_PERMALINK_FLUSH' ) && LATB_DISABLE_PERMALINK_FLUSH ) {
            return;
        }

        $post_type = get_post_type($post_id);

        // Only flush for supported post types
        if (!in_array($post_type, ['post', 'page', 'legal_article', 'interactive_guide', 'nsmi_landing'], true)) {
            return;
        }
        
        if (self::$needs_flush) {
            if (!wp_next_scheduled('legal_permalinks_flush_rules')) {
                wp_schedule_single_event(time() + 5, 'legal_permalinks_flush_rules');
            }
            self::$needs_flush = false;
        }
    }

    /** ---------- Primary Category Change Handler ---------- */
    public function flush_on_primary_category_change($meta_id, $post_id, $meta_key, $meta_value) {
        if ( defined( 'LATB_DISABLE_PERMALINK_FLUSH' ) && LATB_DISABLE_PERMALINK_FLUSH ) {
            return;
        }
        if ($meta_key === '_primary_nsmi_category') {
            $post_type = get_post_type($post_id);
            if (in_array($post_type, ['legal_article', 'interactive_guide'], true)) {
                // If the post already has a custom path, its canonical URL is fixed.
                // Changing the primary NSMI category affects breadcrumbs only, not the
                // URL — no cache rebuild needed.
                if ( get_post_meta( $post_id, '_legal_custom_path', true ) ) {
                    return;
                }
                self::$needs_flush = true;
                $this->invalidate_rules_cache();
                if (!wp_next_scheduled('legal_permalinks_flush_rules')) {
                    wp_schedule_single_event(time() + 3, 'legal_permalinks_flush_rules');
                }
            }
        }
    }

    public function check_category_change_on_save($post_id) {
        $post_type = get_post_type($post_id);
        
        // Flush for legal content (automatic URLs) or posts with custom settings
        if (in_array($post_type, ['legal_article', 'interactive_guide'], true)) {
            self::$needs_flush = true;
            $this->invalidate_rules_cache();
        } else {
            // For other post types, only flush if they have custom permalinks
            $has_custom = get_post_meta($post_id, '_legal_custom_path', true);
            $has_redirects = get_post_meta($post_id, '_legal_redirect_paths', true);

            if ($has_custom || (!empty($has_redirects) && is_array($has_redirects))) {
                self::$needs_flush = true;
                $this->invalidate_rules_cache();
            }
        }
    }

    /** ---------- Query Vars ---------- */
    public function register_query_vars() {
        add_filter('query_vars', [$this, 'register_query_var']);
    }

    public function register_query_var($vars) {
        $vars[] = 'legal_redirect_to';
        return $vars;
    }

    /** ---------- Meta Box ---------- */
    public function add_meta_box() {
        // Apply to ALL public post types
        $screens = get_post_types(['public' => true], 'names');
        foreach ($screens as $screen) {
            add_meta_box(
                'legal_permalink',
                'Smart Permalinks',
                [$this, 'render_meta_box'],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public function render_meta_box($post) {
        $custom = get_post_meta($post->ID, '_legal_custom_path', true);
        $redirects = (array) get_post_meta($post->ID, '_legal_redirect_paths', true);
        
        // Show what the automatic URL would be (applies to all post types now)
        $auto_path = '';
        if (empty($custom)) {
            $auto_path = $this->build_automatic_path($post);
        }
        ?>
        
        <div style="background: #f0f6fc; border: 1px solid #c3d9ff; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
            <h4 style="margin-top: 0;">How URLs Work (All Content Types):</h4>
            <ol style="margin-left: 20px;">
                <li><strong>Edit your slug</strong> in the WordPress editor above</li>
                <li><strong>NSMI categories</strong> build the folder path automatically</li>
                <li><strong>No categories?</strong> Just uses: language/your-slug</li>
                <li><strong>Custom path below</strong> overrides everything (still gets language prefix)</li>
            </ol>
            <?php if ($auto_path): ?>
                <p><strong>Current automatic URL:</strong> <code><?php echo home_url('/' . $auto_path); ?></code></p>
            <?php endif; ?>
        </div>

        <p><label><strong>Custom URL Path Override</strong> <em>(optional - overrides automatic generation)</em></label></p>
        <input type="text" name="legal_custom_path" value="<?php echo esc_attr($custom); ?>" style="width:100%;" placeholder="example: homes-apartments/foreclosure/custom-guide-name" />
        <p class="description">Leave empty to use automatic URL generation based on NSMI categories + your slug. <strong>No leading/trailing slashes needed</strong> - just the path like: <code>category/subcategory/page-name</code></p>

        <p><label><strong>Alternative Paths</strong> (redirect old URLs here, one per line)</label></p>
        <textarea name="legal_redirect_paths" rows="3" style="width:100%;" placeholder="old-url-1&#10;legacy/old-path&#10;another-old-url"><?php echo esc_textarea(implode("\n", $redirects)); ?></textarea>
        <p class="description">These old URLs will redirect to this post. <strong>No slashes needed</strong> - enter paths like: <code>old-page-name</code> or <code>old-folder/old-page</code></p>
        <?php
    }

    public function save_meta($post_id) {
        if (array_key_exists('legal_custom_path', $_POST)) {
            update_post_meta($post_id, '_legal_custom_path', sanitize_text_field($_POST['legal_custom_path']));
        }
        if (array_key_exists('legal_redirect_paths', $_POST)) {
            $lines = array_filter(array_map('trim', explode("\n", $_POST['legal_redirect_paths'])));
            update_post_meta($post_id, '_legal_redirect_paths', $lines);
        }

        // Invalidate cache and schedule a rebuild (both suppressed during import).
        $this->invalidate_rules_cache();
        if ( ! defined( 'LATB_DISABLE_PERMALINK_FLUSH' ) || ! LATB_DISABLE_PERMALINK_FLUSH ) {
            if ( ! wp_next_scheduled( 'legal_permalinks_flush_rules' ) ) {
                wp_schedule_single_event( time() + 1, 'legal_permalinks_flush_rules' );
            }
        }
    }

    /** ---------- Add Language Prefix ---------- */
    private function add_language_prefix($path, $post = null) {
        // Add language prefix if Polylang is active
        if (function_exists('pll_get_post_language') && $post) {
            $lang = pll_get_post_language($post->ID);
            if ($lang) {
                return $lang . '/' . $path;
            }
        }
        
        // Fallback to default language
        if (function_exists('pll_default_language')) {
            $lang = pll_default_language();
            if ($lang) {
                return $lang . '/' . $path;
            }
        }
        
        // Default to 'en' if no Polylang
        return 'en/' . $path;
    }

    /** ---------- Build Automatic Taxonomy-Based Path ---------- */
    private function build_automatic_path($post) {
        // Use the WordPress post slug (which user can edit in the editor)
        $slug = $post->post_name;
        
        // If no slug exists yet (new post), generate one from title
        if (empty($slug) && !empty($post->post_title)) {
            $slug = sanitize_title($post->post_title);
        }
        
        // If still no slug, return empty (shouldn't happen)
        if (empty($slug)) {
            return '';
        }
        
        $path_parts = [];

        // IMPORTER NOTE: _primary_nsmi_category holds a WordPress term_id (integer).
        // During import, map Drupal NSMI term names to existing WP nsmi_category terms.
        // Unmatched terms must be skipped and logged by the importer — do not create
        // new terms here. Always assign taxonomy terms via wp_set_object_terms() before
        // writing this meta so the save_post validation in admin-metabox.php can verify
        // the primary is one of the assigned terms.
        $primary_id = (int) get_post_meta( $post->ID, '_primary_nsmi_category', true );

        if ( $primary_id ) {
            $primary = get_term( $primary_id, 'nsmi_category' );
            if ( $primary && ! is_wp_error( $primary ) ) {
                $ancestors = array_reverse( get_ancestors( $primary->term_id, 'nsmi_category', 'taxonomy' ) );
                foreach ( $ancestors as $ancestor_id ) {
                    $ancestor = get_term( $ancestor_id, 'nsmi_category' );
                    if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                        $path_parts[] = $ancestor->slug;
                    }
                }
                $path_parts[] = $primary->slug;
            } else {
                // Term ID is stored but the term no longer exists — log and fall through
                // to slug-only path rather than producing a broken URL.
                error_log( sprintf(
                    'ctlawhelp-permalinks: post %d has _primary_nsmi_category=%d but term not found; generating slug-only path.',
                    $post->ID,
                    $primary_id
                ) );
            }
        } else {
            // No primary set. For legal_article and interactive_guide this means the
            // content was saved without a primary category, which produces a bare
            // lang/slug URL. Log so the issue is detectable without being fatal.
            if ( in_array( $post->post_type, [ 'legal_article', 'interactive_guide' ], true ) ) {
                error_log( sprintf(
                    'ctlawhelp-permalinks: post %d (%s) has no _primary_nsmi_category set; generating slug-only path.',
                    $post->ID,
                    $post->post_type
                ) );
            }
            // Other post types (post, page, nsmi_landing) do not require a primary —
            // no log needed for them.
        }
        
        // Add the post slug at the end
        $path_parts[] = $slug;
        
        // Build path without language (will be added by add_language_prefix)
        $path_without_lang = implode('/', $path_parts);
        
        // Add language prefix with post context
        return $this->add_language_prefix($path_without_lang, $post);
    }

    /** ---------- Permalink Filtering ---------- */
    public function filter_permalink($permalink, $post, $leavename) {
        // Don't interfere with Elementor editor
        if ( isset( $_GET['elementor-preview'] ) || 
             (isset( $_GET['action'] ) && $_GET['action'] === 'elementor') ||
             (isset( $_GET['post_type'] ) && $_GET['post_type'] === 'elementor_library') ||
             (isset( $post ) && $post->post_type === 'elementor_library') ||
             (isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'elementor' ) !== false) ) {
            return $permalink;
        }
        

        
        // Allow WordPress to show editable slug in admin by skipping our filter
        // when $leavename is true (WordPress uses this to show the editable version)
        // But still generate permalinks for admin views, permalink displays, etc.
        if ($leavename) {
            return $permalink;
        }
        
        // _legal_custom_path is canonical: when set it is always the live URL.
        // Changing NSMI taxonomy or _primary_nsmi_category on a post with a custom
        // path affects breadcrumbs only — the URL does not change. (The flush
        // triggers in flush_on_category_change() and flush_on_primary_category_change()
        // enforce this contract by skipping cache invalidation for these posts.)
        $custom = get_post_meta($post->ID, '_legal_custom_path', true);
        if ($custom) {
            $custom_with_lang = $this->add_language_prefix(trim($custom, '/'), $post);
            return home_url('/' . $custom_with_lang);
        }

        // No custom path — derive URL automatically from NSMI taxonomy + post slug.
        // Automatic path generation is the fallback for posts that do not yet have
        // _legal_custom_path set. It is driven solely by _primary_nsmi_category.
        $path = $this->build_automatic_path($post);
        if ($path) {
            return home_url("/$path");
        }
        
        // Fallback to WordPress default (shouldn't happen with our logic)
        return $permalink;
    }

    /** ---------- Rewrite Rules ---------- */
    public function add_rewrite_rules() {
        // Load from cache; rebuild and store on miss.
        $map = get_option( 'legal_permalinks_rules_map', null );
        if ( $map === null ) {
            $map = $this->build_rules_map();
            update_option( 'legal_permalinks_rules_map', $map, false ); // false = don't autoload
        }

        foreach ( $map as $regex => $query ) {
            add_rewrite_rule( $regex, $query, 'top' );
        }

        // Always register the tag — it is cheap and must be present on every request.
        add_rewrite_tag( '%legal_redirect_to%', '([0-9]+)' );
    }

    /** ---------- Build Rules Map (expensive — only called on cache miss) ---------- */
    private function build_rules_map() {
        $map = [];

        global $wpdb;

        // Posts with manual custom paths
        $custom_posts = $wpdb->get_results(
            "SELECT p.ID, p.post_name, p.post_type
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = '_legal_custom_path' AND pm.meta_value != ''
             AND p.post_status = 'publish'"
        );

        // Posts with redirect paths
        $posts_with_redirects = $wpdb->get_results(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = '_legal_redirect_paths' AND pm.meta_value != '' AND pm.meta_value != 'a:0:{}'
             AND p.post_status = 'publish'"
        );

        // All legal content for automatic taxonomy-based URLs
        $legal_posts = get_posts([
            'post_type'   => ['legal_article', 'interactive_guide', 'post', 'nsmi_landing'],
            'numberposts' => -1,
            'post_status' => 'publish',
        ]);

        // Manual custom paths
        foreach ( $custom_posts as $post_data ) {
            $post = get_post( $post_data->ID );
            if ( ! $post ) continue;

            $custom = get_post_meta( $post->ID, '_legal_custom_path', true );
            if ( ! $custom ) continue;

            $clean_path = trim( $custom, '/' );

            if ( $post->post_type === 'post' ) {
                $query_string = 'index.php?name=' . $post->post_name;
            } elseif ( $post->post_type === 'legal_article' ) {
                $query_string = 'index.php?post_type=legal_article&name=' . $post->post_name;
            } elseif ( $post->post_type === 'interactive_guide' ) {
                $query_string = 'index.php?post_type=interactive_guide&name=' . $post->post_name;
            } else {
                $query_string = 'index.php?p=' . $post->ID;
            }

            // Without language prefix (legacy/direct URLs)
            $map[ '^' . $clean_path . '/?$' ] = $query_string;

            // With language prefix (Polylang)
            if ( function_exists( 'pll_languages_list' ) ) {
                foreach ( pll_languages_list() as $lang ) {
                    $map[ '^' . $lang . '/' . $clean_path . '/?$' ] = $query_string;
                }
            }
        }

        // Redirect paths
        $all_redirect_ids = wp_list_pluck( $posts_with_redirects, 'ID' );
        foreach ( $all_redirect_ids as $post_id ) {
            $redirects = (array) get_post_meta( $post_id, '_legal_redirect_paths', true );
            foreach ( $redirects as $rpath ) {
                if ( trim( $rpath ) === '' ) continue;
                $map[ '^' . trim( $rpath, '/' ) . '/?$' ] = 'index.php?legal_redirect_to=' . $post_id;
            }
        }

        // Automatic taxonomy-based URLs (skip posts that already have a custom path)
        $processed_ids = wp_list_pluck( $custom_posts, 'ID' );
        foreach ( $legal_posts as $post ) {
            if ( in_array( $post->ID, $processed_ids ) ) continue;

            $auto_path = $this->build_automatic_path( $post );
            if ( ! $auto_path ) continue;

            $regex = '^' . trim( $auto_path, '/' ) . '/?$';

            if ( $post->post_type === 'interactive_guide' ) {
                $map[ $regex ] = 'index.php?post_type=interactive_guide&name=' . $post->post_name;
            } elseif ( $post->post_type === 'post' ) {
                $map[ $regex ] = 'index.php?name=' . $post->post_name;
            } else {
                $map[ $regex ] = 'index.php?post_type=' . $post->post_type . '&name=' . $post->post_name;
            }
        }

        return $map;
    }

    /** ---------- Invalidate Rules Cache ---------- */
    private function invalidate_rules_cache() {
        // When LATB_DISABLE_PERMALINK_FLUSH is true, skip cache invalidation so that
        // bulk imports do not trigger hundreds of rebuild cycles. Set this constant in
        // wp-config.php before import; run `wp option delete legal_permalinks_rules_map`
        // and `wp rewrite flush` once after import completes.
        if ( defined( 'LATB_DISABLE_PERMALINK_FLUSH' ) && LATB_DISABLE_PERMALINK_FLUSH ) {
            return;
        }
        delete_option( 'legal_permalinks_rules_map' );
    }

    /** ---------- Redirect Handler ---------- */
    public function handle_redirects() {
        $redirect_id = get_query_var('legal_redirect_to');
        if ($redirect_id) {
            $url = get_permalink($redirect_id);
            if ($url) {
                $query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
                wp_redirect($url . $query, 301);
                exit;
            }
        }
    }

    /** ---------- Tools Page ---------- */
    public function add_tools_page() {
        add_management_page('Permalink Manager', 'Permalink Manager', 'manage_options', 'legal-permalink-manager', [$this, 'render_tools_page']);
    }

    public function enqueue_admin_assets($hook) {
        if ($hook === 'tools_page_legal-permalink-manager') {
            wp_enqueue_script('jquery');
            wp_enqueue_script('tablesorter', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/js/jquery.tablesorter.min.js', ['jquery'], '2.31.3', true);
            wp_enqueue_style('tablesorter-style', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/css/theme.default.min.css');
            wp_enqueue_script('legal-permalinks-admin', plugin_dir_url(__FILE__) . 'legal-permalinks-admin.js', ['jquery', 'tablesorter'], '0.9.3', true);
            wp_localize_script('legal-permalinks-admin', 'LegalPermalinksAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('legal_permalinks_nonce')
            ]);
        }
    }

    public function render_tools_page() {
        $posts = get_posts([
            'post_type'   => get_post_types(['public' => true], 'names'),
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);

        ?>
        <div class="wrap">
            <h1>Permalink Manager</h1>
            
            <?php
            // Handle manual flush
            if (isset($_POST['flush_rules']) && current_user_can('manage_options')) {
                $this->add_rewrite_rules();
                flush_rewrite_rules(true);
                echo '<div class="notice notice-success"><p>Permalink rules flushed successfully!</p></div>';
            }
            ?>
            
            <form method="post" style="margin-bottom: 20px;">
                <input type="submit" name="flush_rules" class="button button-primary" value="Flush Permalink Rules" />
                <p class="description">Click this button if you're experiencing 404 errors</p>
            </form>
            
            <table class="widefat tablesorter" id="legal-table">
                <thead><tr><th>Title</th><th>Type</th><th>Custom Path</th><th>Redirects</th><th>NSMI Category</th></tr></thead>
                <tbody>
                <?php foreach ($posts as $post):
                    $custom = get_post_meta($post->ID, '_legal_custom_path', true);
                    $redirects = (array) get_post_meta($post->ID, '_legal_redirect_paths', true);
                    $terms = get_the_terms($post->ID, 'nsmi_category');
                    $nsmi = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'name') : [];
                    ?>
                    <tr data-post-id="<?php echo $post->ID; ?>">
                        <td><a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html(get_the_title($post)); ?></a></td>
                        <td><?php echo esc_html(get_post_type($post)); ?></td>
                        <td><input type="text" class="legal-custom-path" value="<?php echo esc_attr($custom); ?>" style="width:100%;" /></td>
                        <td><textarea class="legal-redirect-paths" rows="3" style="width:100%;"><?php echo esc_textarea(implode("\n", $redirects)); ?></textarea></td>
                        <td><?php echo esc_html(implode(', ', $nsmi)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /** ---------- Scheduled Flush ---------- */
    public function scheduled_flush() {
        $this->invalidate_rules_cache();
        $this->add_rewrite_rules();
        flush_rewrite_rules(false);
    }

    /** ---------- AJAX Save ---------- */
    public function ajax_save_row() {
        check_ajax_referer('legal_permalinks_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $custom  = sanitize_text_field($_POST['custom']);
        $redirects = array_filter(array_map('trim', explode("\n", $_POST['redirects'])));
        update_post_meta($post_id, '_legal_custom_path', $custom);
        update_post_meta($post_id, '_legal_redirect_paths', $redirects);
        $this->invalidate_rules_cache();
        if (!wp_next_scheduled('legal_permalinks_flush_rules')) {
            wp_schedule_single_event(time() + 3, 'legal_permalinks_flush_rules');
        }
        wp_send_json_success(['message' => 'Saved']);
    }
    

    
    /** ---------- Admin Bar Flush Button ---------- */
    public function add_flush_permalinks_admin_bar($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id'    => 'flush_permalinks_quick',
            'title' => '🔄 Flush Permalinks',
            'href'  => '#',
            'meta'  => array(
                'onclick' => 'flushPermalinksQuick(); return false;',
                'title'   => 'Flush permalink rewrite rules - use when URLs are not working',
            ),
        ));
        
        // Add inline JavaScript for the AJAX call
        add_action('wp_footer', array($this, 'flush_permalinks_script'));
        add_action('admin_footer', array($this, 'flush_permalinks_script'));
    }
    
    public function flush_permalinks_script() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <script type="text/javascript">
        function flushPermalinksQuick() {
            // Show loading state
            var button = document.getElementById('wp-admin-bar-flush_permalinks_quick');
            if (button) {
                var link = button.querySelector('a');
                if (link) {
                    link.innerHTML = '⏳ Flushing...';
                    link.style.backgroundColor = '#0073aa';
                    link.style.color = 'white';
                }
            }
            
            // Make AJAX call
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=flush_permalinks_quick&nonce=<?php echo wp_create_nonce('flush_permalinks_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (button) {
                    var link = button.querySelector('a');
                    if (link) {
                        if (data.success) {
                            link.innerHTML = '✅ Flushed!';
                            link.style.backgroundColor = '#46b450';
                            setTimeout(function() {
                                link.innerHTML = '🔄 Flush Permalinks';
                                link.style.backgroundColor = '';
                                link.style.color = '';
                            }, 2000);
                        } else {
                            link.innerHTML = '❌ Error';
                            link.style.backgroundColor = '#dc3232';
                            setTimeout(function() {
                                link.innerHTML = '🔄 Flush Permalinks';
                                link.style.backgroundColor = '';
                                link.style.color = '';
                            }, 2000);
                        }
                    }
                }
                
                // Show console message
                console.log('Permalinks flushed:', data);
            })
            .catch(error => {
                console.error('Error flushing permalinks:', error);
                if (button) {
                    var link = button.querySelector('a');
                    if (link) {
                        link.innerHTML = '❌ Error';
                        link.style.backgroundColor = '#dc3232';
                        setTimeout(function() {
                            link.innerHTML = '🔄 Flush Permalinks';
                            link.style.backgroundColor = '';
                            link.style.color = '';
                        }, 2000);
                    }
                }
            });
        }
        </script>
        <?php
    }
    
    public function ajax_flush_permalinks() {
        check_ajax_referer('flush_permalinks_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Invalidate cache so add_rewrite_rules() does a fresh rebuild
        $this->invalidate_rules_cache();
        $this->add_rewrite_rules();

        // Flush WordPress rewrite rules
        flush_rewrite_rules(true);
        
        wp_send_json_success(array(
            'message' => 'Permalink rules flushed successfully!',
            'timestamp' => current_time('mysql')
        ));
    }
}

/** Activation/Deactivation Hooks */
function legal_permalinks_activate() {
    delete_option( 'legal_permalinks_rules_map' );
    (new LegalPermalinks())->add_rewrite_rules();
    flush_rewrite_rules();
}
function legal_permalinks_deactivate() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'legal_permalinks_activate');
register_deactivation_hook(__FILE__, 'legal_permalinks_deactivate');

new LegalPermalinks();

// Force flush on admin visits to help with debugging
add_action('admin_init', function() {
    if (current_user_can('manage_options') && isset($_GET['flush_permalinks'])) {
        flush_rewrite_rules(true);
        wp_redirect(remove_query_arg('flush_permalinks'));
        exit;
    }
});

// Admin notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $screen = get_current_screen();
        // Show on post edit screens
        if ($screen && in_array($screen->id, ['post', 'legal_article', 'interactive_guide'])) {
            if (get_transient('legal_permalinks_updated_notice')) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Legal Permalinks:</strong> Spanish language support has been updated. Please <a href="' . admin_url('options-permalink.php') . '">flush your permalinks</a> to apply changes.</p>';
                echo '</div>';
                delete_transient('legal_permalinks_updated_notice');
            }
        }
    }
});

// Set transient to show notice once
if (!get_transient('legal_permalinks_updated_notice')) {
    set_transient('legal_permalinks_updated_notice', true, 3600);
}
add_action('admin_notices', function() {
    global $pagenow;
    if ($pagenow === 'options-permalink.php') {
        echo '<div class="notice notice-warning"><p><strong>Legal Aid Permalinks:</strong> For custom permalinks to work, please set your structure to <em>Post name</em>.</p></div>';
        echo '<div class="notice notice-info"><p><a href="' . add_query_arg('flush_permalinks', '1') . '" class="button">Force Flush Permalinks</a> - Click this if you\'re getting 404 errors</p></div>';
    }
});
