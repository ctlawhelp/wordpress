<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Shortcode: [laa_nsmi_accordion term="homes-apartments" include_children="1" posts_per_section="8" show_excerpts="1" excerpt_words="24"]
 */
function laa_nsmi_accordion_sc($atts) {
  $a = shortcode_atts(array(
    'term'              => '',
    'include_children'  => '1',
    'posts_per_section' => '8',
    'order'             => 'DESC',
    'orderby'           => 'date',
    'show_excerpts'     => '1',
    'excerpt_words'     => '24',
    'class'             => '',
  ), $atts, 'laa_nsmi_accordion');

  // Resolve parent term slug (fallback to page meta)
  $page_id     = get_the_ID() ?: get_queried_object_id();
  $parent_slug = sanitize_title($a['term']);
  if (!$parent_slug && $page_id) {
    $maybe = get_post_meta($page_id, '_nsmi_issue', true);
    if ($maybe) { $parent_slug = sanitize_title($maybe); }
  }
  if (!$parent_slug) {
    return '<div class="laa-note">NSMI accordion: no term set. Pass term="slug" or set _nsmi_issue on this page.</div>';
  }

  $parent = get_term_by('slug', $parent_slug, 'nsmi_category');
  if (!$parent || is_wp_error($parent)) {
    return '<div class="laa-note">NSMI accordion: invalid term.</div>';
  }

  // Flags
  $include_children = ($a['include_children'] !== '0');
  $pp               = max(1, (int)$a['posts_per_section']);
  $show_excerpts    = ($a['show_excerpts'] !== '0');
  $excerpt_words    = max(5, (int)$a['excerpt_words']);
  $order            = (strtoupper($a['order']) === 'ASC') ? 'ASC' : 'DESC';
  $orderby          = preg_replace('/[^a-z_\s]/i', '', $a['orderby']);
  $wrap_class       = sanitize_html_class($a['class']);

  // Sections = child terms or just parent
  $sections = array();
  if ($include_children) {
    $children = get_terms(array(
      'taxonomy'   => 'nsmi_category',
      'hide_empty' => true,
      'parent'     => $parent->term_id,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ));
    if (!is_wp_error($children) && $children) { $sections = $children; }
  }
  if (empty($sections)) { $sections = array($parent); }

  // Helper: fetch IDs for a term (pins first if present)
  $fetch_ids = function($term_id) use ($pp, $orderby, $order) {
    $featured = new WP_Query(array(
      'post_type'      => 'legal_article',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'no_found_rows'  => true,
      'fields'         => 'ids',
      'meta_query'     => array(array('key' => '_laa_pin', 'value' => '1', 'compare' => '=')),
      'tax_query'      => array(array('taxonomy' => 'nsmi_category', 'field' => 'term_id', 'terms' => $term_id)),
      'orderby'        => array('meta_value_num' => 'ASC', 'date' => 'DESC'),
      'meta_key'       => '_laa_pin_order',
    ));
    $featured_ids = $featured->posts;

    $remain = max(0, $pp - count($featured_ids));
    $regular_ids = array();
    if ($remain > 0) {
      $regular = new WP_Query(array(
        'post_type'      => 'legal_article',
        'post_status'    => 'publish',
        'posts_per_page' => $remain,
        'post__not_in'   => $featured_ids,
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'tax_query'      => array(array('taxonomy' => 'nsmi_category', 'field' => 'term_id', 'terms' => $term_id)),
        'orderby'        => $orderby,
        'order'          => $order,
      ));
      $regular_ids = $regular->posts;
    }
    return array_slice(array_merge($featured_ids, $regular_ids), 0, $pp);
  };

  // Build HTML
  $out  = '<section class="laa-nsmi-accordion ' . esc_attr($wrap_class) . '" data-parent="' . esc_attr($parent->slug) . '">';
  $out .= '<h2 class="screen-reader-text">' . esc_html(sprintf(__('Topics in %s','laa'), $parent->name)) . '</h2>';

  foreach ($sections as $t) {
    $ids   = $fetch_ids($t->term_id);
    $tid   = sanitize_title($t->slug);

    $out  .= '<details id="' . esc_attr($tid) . '" class="laa-acc-item" data-term="' . esc_attr($t->slug) . '">';
    $out  .=   '<summary class="laa-acc-summary">' . esc_html($t->name) . '</summary>';

    if ($ids) {
      $out .= '<ul class="laa-acc-list">';
      foreach ($ids as $pid) {
        $title = get_the_title($pid);
        $url   = get_permalink($pid);
        $out  .= '<li class="laa-acc-item-row"><a class="laa-acc-link" href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
        if ($show_excerpts) {
          $raw = get_post_field('post_excerpt', $pid);
          if (!$raw) { $raw = wp_strip_all_tags(get_post_field('post_content', $pid)); }
          $words = preg_split('/\s+/', trim($raw));
          if (count($words) > $excerpt_words) { $raw = implode(' ', array_slice($words, 0, $excerpt_words)) . '…'; }
          $out .= '<p class="laa-acc-excerpt">' . esc_html($raw) . '</p>';
        }
        $out .= '</li>';
      }
      $out .= '</ul>';
    } else {
      $out .= '<p class="laa-empty">No articles found in this section.</p>';
    }

    $out .= '</details>';
  }

  $out .= '</section>';
  return $out;
}

add_shortcode('laa_nsmi_accordion', 'laa_nsmi_accordion_sc');
