<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [laa_nsmi_sidebar]
 * Renders the composed sidebar using laa_nsmi_render_sidebar_for_page().
 */
add_shortcode('laa_nsmi_sidebar', function($atts){
  if (!function_exists('laa_nsmi_render_sidebar_for_page')) {
    // Fallback to global sidebar if helper isn�t loaded
    ob_start();
    if (is_active_sidebar('nsmi-landing-sidebar')) {
      echo '<div class="nsmi-sb">';
      dynamic_sidebar('nsmi-landing-sidebar');
      echo '</div>';
    }
    return ob_get_clean();
  }
  ob_start();
  echo '<div class="nsmi-sb">';
  laa_nsmi_render_sidebar_for_page();
  echo '</div>';
  return ob_get_clean();
});

/**
 * Shortcode: [laa_nsmi_html_above]
 * Outputs the WYSIWYG block saved to _nsmi_html_above (if present).
 */
add_shortcode('laa_nsmi_html_above', function($atts){
  $page_id = get_the_ID() ?: get_queried_object_id();
  if (!$page_id) return '';
  $val = get_post_meta($page_id, '_nsmi_html_above', true);
  if (!$val) return '';
  return '<div class="nsmi-landing-above">' . apply_filters('the_content', $val) . '</div>';
});

/**
 * (Optional) Convenience: [laa_nsmi_accordion_auto]
 * Same as your accordion but auto-resolves the page�s _nsmi_issue.
 * Accepts all the same attributes; "term" is optional.
 */
add_shortcode('laa_nsmi_accordion_auto', function($atts){
  $a = shortcode_atts([
    'term'              => '',
    'include_children'  => '1',
    'posts_per_section' => '8',
    'order'             => 'DESC',
    'orderby'           => 'date',
    'show_counts'       => '1',
    'show_excerpts'     => '1',
    'excerpt_words'     => '24',
    'class'             => '',
  ], $atts, 'laa_nsmi_accordion_auto');

  if (empty($a['term'])) {
    $page_id = get_the_ID() ?: get_queried_object_id();
    $slug = $page_id ? get_post_meta($page_id, '_nsmi_issue', true) : '';
    if ($slug) $a['term'] = sanitize_title($slug);
  }

  // Build the original shortcode string and let it run
  $parts = [];
  foreach ($a as $k=>$v) { $parts[] = $k . '="' . esc_attr($v) . '"'; }
  return do_shortcode('[laa_nsmi_accordion ' . implode(' ', $parts) . ']');
});

/**
 * NEW: Get NSMI term from current page's dropdown selection
 */
function laa_get_page_nsmi_term() {
    static $cached_term = null;
    
    if ($cached_term !== null) {
        return $cached_term;
    }
    
    $page_id = get_the_ID() ?: get_queried_object_id();
    if (!$page_id) {
        return $cached_term = false;
    }
    
    // Get the selected term slug from the dropdown
    $term_slug = get_post_meta($page_id, '_nsmi_issue', true);
    if (!$term_slug) {
        return $cached_term = false;
    }
    
    $term = get_term_by('slug', $term_slug, 'nsmi_category');
    return $cached_term = ($term && !is_wp_error($term)) ? $term : false;
}

/**
 * NEW: Shortcode for NSMI category title: [laa_nsmi_title]
 */
add_shortcode('laa_nsmi_title', function($atts) {
    $a = shortcode_atts([
        'fallback' => get_the_title(), // Use page title if no NSMI term
        'wrapper' => 'h1',
        'class' => 'nsmi-dynamic-title'
    ], $atts);
    
    $term = laa_get_page_nsmi_term();
    $title = $term ? $term->name : $a['fallback'];
    
    $class_attr = $a['class'] ? ' class="' . esc_attr($a['class']) . '"' : '';
    
    return "<{$a['wrapper']}{$class_attr}>" . esc_html($title) . "</{$a['wrapper']}>";
});

/**
 * NEW: Shortcode for NSMI category icon: [laa_nsmi_icon]
 */
add_shortcode('laa_nsmi_icon', function($atts) {
    $a = shortcode_atts([
        'size' => 'medium',
        'class' => 'nsmi-dynamic-icon',
        'wrapper' => 'div'
    ], $atts);
    
    $term = laa_get_page_nsmi_term();
    if (!$term) return '';
    
    if (function_exists('get_nsmi_icon')) {
        $icon_html = get_nsmi_icon($term->term_id, $a['size']);
        if ($icon_html) {
            $class_attr = $a['class'] ? ' class="' . esc_attr($a['class']) . '"' : '';
            return "<{$a['wrapper']}{$class_attr}>{$icon_html}</{$a['wrapper']}>";
        }
    }
    
    return '';
});

/**
 * NEW: Combined header with icon + title: [laa_nsmi_header]
 */
add_shortcode('laa_nsmi_header', function($atts) {
    $a = shortcode_atts([
        'show_icon' => '1',
        'show_title' => '1',
        'icon_size' => 'medium',
        'title_tag' => 'h1',
        'class' => 'nsmi-page-header',
        'layout' => 'horizontal' // 'horizontal' or 'vertical'
    ], $atts);
    
    $term = laa_get_page_nsmi_term();
    if (!$term) {
        // Fallback to page title if no NSMI term selected
        return $a['show_title'] === '1' ? "<{$a['title_tag']}>" . get_the_title() . "</{$a['title_tag']}>" : '';
    }
    
    ob_start();
    $layout_class = $a['layout'] === 'vertical' ? ' nsmi-header-vertical' : ' nsmi-header-horizontal';
    echo '<div class="' . esc_attr($a['class'] . $layout_class) . '">';
    
    // Icon
    if ($a['show_icon'] === '1' && function_exists('get_nsmi_icon')) {
        $icon = get_nsmi_icon($term->term_id, $a['icon_size']);
        if ($icon) {
            echo '<div class="nsmi-header-icon">' . $icon . '</div>';
        }
    }
    
    // Title
    if ($a['show_title'] === '1') {
        echo '<' . $a['title_tag'] . ' class="nsmi-header-title">' . esc_html($term->name) . '</' . $a['title_tag'] . '>';
    }
    
    echo '</div>';
    return ob_get_clean();
});
