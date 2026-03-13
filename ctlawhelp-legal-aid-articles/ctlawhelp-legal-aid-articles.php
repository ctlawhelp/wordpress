<?php
/**
 * Plugin Name: CTLawHelp Legal Aid Articles
 * Description: Custom post type, taxonomy, icons, shortcodes, and admin UI for Legal Aid Articles.
 * Version: 1.4
 * Author: CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LAA_PATH', plugin_dir_path( __FILE__ ) );
define( 'LAA_URL',  plugin_dir_url( __FILE__ ) );

// Disable NSMI debug logging if memory issues occur
define( 'DISABLE_NSMI_DEBUG', true );

// Disable automatic memory limit increases if causing issues
define( 'DISABLE_MEMORY_INCREASE', true );

// Disable SimplePie/RSS operations that may cause memory issues
if (defined('DISABLE_FEED_FETCHING') && DISABLE_FEED_FETCHING) {
    // Disable all automatic feed fetching
    add_filter('wp_feed_cache_transient_lifetime', function() { return 0; });
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    add_filter('pre_option_rss_use_excerpt', '__return_true');
}

require_once LAA_PATH . 'inc/cpt.php';
require_once LAA_PATH . 'inc/taxonomy-nsmi.php';
require_once LAA_PATH . 'inc/taxonomy-nsmi-icons.php';
require_once LAA_PATH . 'inc/taxonomy-nsmi-admin.php';
require_once LAA_PATH . 'inc/admin-shortcodes-catalog.php';

// Last reviewed date meta box + shortcode
require_once LAA_PATH . 'inc/last-reviewed.php';

require_once LAA_PATH . 'inc/admin-diagnostics.php';

add_action( 'init', function() {
    if ( taxonomy_exists('nsmi_category') ) {
    } else {
        // NSMI taxonomy not registered - this is handled by taxonomy registration
    }
}, 20 );

function laa_enqueue_assets() {
    wp_enqueue_style( 'laa-css', LAA_URL . 'assets/css/legal-aid-articles.css' );
}
add_action( 'wp_enqueue_scripts', 'laa_enqueue_assets' );

add_action('init', function(){
    $pt = get_post_type_object('legal_article');
    if(!$pt || empty($pt->labels)) return;
    $pt->labels->name               = 'Legal Aid Articles';
    $pt->labels->menu_name          = 'Legal Aid Articles';
    $pt->labels->singular_name      = 'Legal Aid Article';
    $pt->labels->all_items          = 'All Legal Aid Articles';
    $pt->labels->add_new            = 'Add Legal Aid Article';
    $pt->labels->add_new_item       = 'Add New Legal Aid Article';
    $pt->labels->edit_item          = 'Edit Legal Aid Article';
    $pt->labels->new_item           = 'Legal Aid Article';
    $pt->labels->view_item          = 'View Legal Aid Article';
    $pt->labels->search_items       = 'Search Legal Aid Articles';
    $pt->labels->not_found          = 'No Legal Aid Articles found';
    $pt->labels->not_found_in_trash = 'No Legal Aid Articles found in Trash';
    $pt->label = 'Legal Aid Articles';
}, 20);

/**
 * Gutenberg enables the Slug UI based on REST post responses that include a non-empty sample permalink.
 * Because `legal_article` is registered with `rewrite => false`, WordPress may return an empty sample permalink,
 * which makes the Slug control render read-only (as a link).
 *
 * This filter provides a minimal sample permalink only when WP fails to generate one.
 */
add_filter('get_sample_permalink', function($permalink, $post_id, $title, $name, $post) {
    // Needed for the block editor (REST) and classic admin; avoid affecting front-end.
    $in_editor_context = is_admin() || (defined('REST_REQUEST') && REST_REQUEST);
    if (!$in_editor_context) {
        return $permalink;
    }

    if (empty($post) || !($post instanceof WP_Post)) {
        $post = get_post($post_id);
    }
    if (empty($post) || $post->post_type !== 'legal_article') {
        return $permalink;
    }

    // If WP already has a sample permalink, keep it unless it's a query-var style URL.
    // Gutenberg treats query-var permalinks as non-editable for the Slug UI.
    $has_sample = is_array($permalink) && !empty($permalink[0]);
    if ($has_sample) {
        $url = (string) $permalink[0];
        $query = wp_parse_url($url, PHP_URL_QUERY);
        $is_query_var_sample = false;
        if (!empty($query)) {
            parse_str($query, $params);
            if (
                isset($params['legal_article']) ||
                (isset($params['post_type']) && $params['post_type'] === 'legal_article') ||
                isset($params['p'])
            ) {
                $is_query_var_sample = true;
            }
        }

        if (!$is_query_var_sample) {
            return $permalink;
        }
    }

    $slug = $name;
    if (empty($slug)) {
        $slug = sanitize_title($title);
    }
    if (empty($slug)) {
        $slug = $post->post_name;
    }
    if (empty($slug)) {
        return $permalink;
    }

    // Keep preview simple; it only needs to exist for the editor UI.
    $lang = 'en';
    if (function_exists('pll_get_post_language')) {
        $maybe_lang = pll_get_post_language($post_id);
        if (!empty($maybe_lang)) {
            $lang = $maybe_lang;
        }
    } elseif (function_exists('pll_default_language')) {
        $maybe_lang = pll_default_language();
        if (!empty($maybe_lang)) {
            $lang = $maybe_lang;
        }
    }

    $preview = home_url('/' . trim($lang, '/') . '/' . $slug . '/');
    return [$preview, $slug];
}, 10, 5);

/**
 * Some setups (notably CPTs registered with `rewrite => false`) cause the REST API to return a query-var
 * `permalink_template` like `...?legal_article=slug` with no `%postname%` token.
 * Gutenberg treats that as non-editable and renders the Slug panel read-only.
 *
 * Force an editable template for the block editor by providing a `%postname%`-based template.
 */
add_filter('rest_prepare_legal_article', function($response, $post, $request) {
    if (empty($response) || !method_exists($response, 'get_data')) {
        return $response;
    }

    $data = $response->get_data();
    $template = isset($data['permalink_template']) ? (string) $data['permalink_template'] : '';

    // If the template already contains a token, don't touch it.
    if ($template && strpos($template, '%') !== false) {
        return $response;
    }

    // Build a simple editor-only permalink template.
    $lang = 'en';
    if (function_exists('pll_get_post_language')) {
        $maybe_lang = pll_get_post_language($post->ID);
        if (!empty($maybe_lang)) {
            $lang = $maybe_lang;
        }
    } elseif (function_exists('pll_default_language')) {
        $maybe_lang = pll_default_language();
        if (!empty($maybe_lang)) {
            $lang = $maybe_lang;
        }
    }

    $data['permalink_template'] = home_url('/' . trim($lang, '/') . '/%postname%/');

    if (empty($data['generated_slug'])) {
        $data['generated_slug'] = !empty($post->post_name) ? $post->post_name : sanitize_title($post->post_title);
    }

    $response->set_data($data);
    return $response;
}, 10, 3);
