<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [laa_nsmi_grid columns="3" taxonomy="nsmi_category" parent="" hide_empty="0" class=""]
 */

/** Find a landing page URL for a term (fallback to the term archive). */
function laa_nsmi_find_landing_url($term) {
  $slug = is_object($term) ? $term->slug : sanitize_title($term);
  $page = get_posts([
    'post_type'      => 'page',
    'posts_per_page' => 1,
    'no_found_rows'  => true,
    'fields'         => 'ids',
    'meta_key'       => '_nsmi_issue',
    'meta_value'     => $slug,
  ]);
  if (!empty($page[0])) return get_permalink($page[0]);
  $link = get_term_link($term);
  return is_wp_error($link) ? '' : $link;
}

/** Helper: extract src="" from an <img> HTML string. */
function laa_nsmi_extract_img_src($html) {
  if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
    return $m[1];
  }
  return '';
}

/** Resolve an icon URL for a term (tries helper, term meta, then <img> HTML). */
function laa_nsmi_icon_url_for_term($term_id, $icon_html = '') {
  if (function_exists('get_nsmi_icon_url')) {
    $u = get_nsmi_icon_url($term_id);
    if ($u) return $u;
  }
  $image_id = (int) get_term_meta($term_id, 'nsmi_icon_id', true);
  if ($image_id) {
    $u = wp_get_attachment_url($image_id);
    if ($u) return $u;
  }
  $u = laa_nsmi_extract_img_src($icon_html);
  return $u ?: '';
}

function laa_nsmi_grid_sc($atts) {
  $a = shortcode_atts([
    'columns'    => '3',
    'taxonomy'   => 'nsmi_category',
    'parent'     => '',
    'hide_empty' => '0',
    'class'      => '',
  ], $atts, 'laa_nsmi_grid');

  $cols       = max(1, min(4, (int)$a['columns']));
  $taxonomy   = sanitize_key($a['taxonomy']);
  $hide_empty = ($a['hide_empty'] === '1');
  $class      = sanitize_html_class($a['class']);
  $parentSlug = sanitize_title($a['parent']);

  $parent_id = 0;
  if ($parentSlug !== '') {
    $p = get_term_by('slug', $parentSlug, $taxonomy);
    if ($p && !is_wp_error($p)) $parent_id = (int)$p->term_id;
  }

  $terms = get_terms([
    'taxonomy'   => $taxonomy,
    'hide_empty' => $hide_empty,
    'parent'     => $parent_id,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);
  if (is_wp_error($terms) || empty($terms)) return '';

  ob_start();
  echo '<section class="nsmi-grid-wrap ' . esc_attr($class) . '">';
  echo '<div class="nsmi-grid nsmi-cols-' . (int)$cols . '">';

  foreach ($terms as $t) {
    $url        = laa_nsmi_find_landing_url($t);
    $icon_html  = function_exists('get_nsmi_icon') ? get_nsmi_icon($t->term_id, 'thumbnail') : '';
    $icon_url   = laa_nsmi_icon_url_for_term($t->term_id, $icon_html);
    $descH      = term_description($t, $taxonomy);
    $desc       = '';

    if ($descH && preg_match('/<p\b[^>]*>(.*?)<\/p>/is', $descH, $m)) {
      $desc = wp_strip_all_tags($m[1]);
    } else {
      $desc = wp_strip_all_tags($descH);
    }

    // Add the CSS variable inline when we have an icon URL.
    $style = $icon_url ? ' style="--icon-url: url(\'' . esc_url($icon_url) . '\')"' : '';

    echo '<a class="nsmi-grid-card" href="' . esc_url($url) . '">';
    echo '  <div class="nsmi-grid-media"' . $style . '>' . $icon_html . '</div>';
    echo '  <div class="nsmi-grid-body">';
    echo '    <div class="nsmi-grid-title">' . esc_html($t->name) . '</div>';
    if ($desc !== '') echo '    <p class="nsmi-grid-desc">' . esc_html($desc) . '</p>';
    echo '  </div>';
    echo '</a>';
  }

  echo '</div></section>';
  return ob_get_clean();
}
add_shortcode('laa_nsmi_grid', 'laa_nsmi_grid_sc');
