<?php
if (!defined('ABSPATH')) exit;

/**
 * Render the appropriate sidebar for the current content
 * Priority: 1) Manually assigned content sidebar (pages and legal articles), 2) Global widget sidebar
 * Used for NSMI landing pages and Legal Aid Articles
 */
function laa_render_nsmi_sidebar() {
    // Get the current content's ID
    $content_id = get_the_ID() ?: get_queried_object_id();
    if (!$content_id) {
        // Fallback to global widget sidebar
        if (is_active_sidebar('nsmi-landing-sidebar')) {
            dynamic_sidebar('nsmi-landing-sidebar');
        }
        return;
    }

    // Check for manually assigned content-based sidebar first (for pages and legal articles)
    $post_type = get_post_type($content_id);
    if ($post_type === 'page' || $post_type === 'legal_article') {
        $assigned_sidebar_id = get_post_meta($content_id, '_assigned_sidebar', true);
        if (!empty($assigned_sidebar_id)) {
            $sidebar_post = get_post($assigned_sidebar_id);
            if ($sidebar_post && $sidebar_post->post_type === 'laa_sidebar') {
                echo '<div class="laa-sidebar-content">';
                echo apply_filters('the_content', $sidebar_post->post_content);
                echo '</div>';
                return;
            }
        }
    }

    // Final fallback to global widget sidebar
    if (is_active_sidebar('nsmi-landing-sidebar')) {
        dynamic_sidebar('nsmi-landing-sidebar');
    }
}

/**
 * Universal shortcode to display sidebar for current post
 * Usage: [laa_sidebar]
 */
add_shortcode('laa_sidebar', function($atts) {
    ob_start();

    // Get current post ID
    $post_id = get_the_ID();
    if (!$post_id) {
        return '<p>' . __('No post found.', 'laa') . '</p>';
    }

    // Check for manually assigned content-based sidebar
    $assigned_sidebar_id = get_post_meta($post_id, '_assigned_sidebar', true);
    if (!empty($assigned_sidebar_id)) {
        $sidebar_post = get_post($assigned_sidebar_id);
        if ($sidebar_post && $sidebar_post->post_type === 'laa_sidebar') {
            echo '<div class="laa-sidebar-content">';
            echo apply_filters('the_content', $sidebar_post->post_content);
            echo '</div>';
            return ob_get_clean();
        }
    }

    // Fallback to global widget sidebar
    if (is_active_sidebar('nsmi-landing-sidebar')) {
        echo '<div class="laa-sidebar-widget">';
        dynamic_sidebar('nsmi-landing-sidebar');
        echo '</div>';
    } else {
        echo '<p>' . __('No sidebar content available.', 'laa') . '</p>';
    }

    return ob_get_clean();
});

/**
 * Register the global NSMI sidebar
 */
add_action('widgets_init', function () {
  register_sidebar([
    'name'          => __('NSMI Landing Sidebar', 'laa'),
    'id'            => 'nsmi-landing-sidebar',
    'description'   => __('Widgets placed here appear on the left side of NSMI Landing pages.', 'laa'),
    'before_widget' => '<section id="%1$s" class="widget %2$s">',
    'after_widget'  => '</section>',
    'before_title'  => '<h2 class="widget-title">',
    'after_title'   => '</h2>',
  ]);
});
