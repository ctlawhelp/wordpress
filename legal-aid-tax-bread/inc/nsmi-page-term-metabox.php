<?php
/**
 * Page Metabox: pick the top-level NSMI term for this landing page.
 * Saves to post meta key: _nsmi_issue (slug)
 */
if (!defined('ABSPATH')) exit;

/** Add metabox on all Pages (always visible) */
add_action('add_meta_boxes', function() {
    if (!taxonomy_exists('nsmi_category')) return;
  add_meta_box(
    'laa_nsmi_page_term',
    __('NSMI Landing: Top-Level Issue', 'laa'),
    'laa_render_nsmi_page_term_metabox',
    'page',
    'side',
    'high'
  );
});

/** Render dropdown of top-level nsmi_category terms */
function laa_render_nsmi_page_term_metabox($post){
    if (!taxonomy_exists('nsmi_category')) return;
  wp_nonce_field('laa_nsmi_page_term_n', 'laa_nsmi_page_term_n');
  $saved_slug = get_post_meta($post->ID, '_nsmi_issue', true);

  $terms = get_terms(array(
    'taxonomy'   => 'nsmi_category',
    'hide_empty' => false,
    'parent'     => 0,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ));

  echo '<p><label for="laa_nsmi_issue"><strong>' . esc_html__('Top-Level NSMI Issue', 'laa') . '</strong></label></p>';
  echo '<select id="laa_nsmi_issue" name="laa_nsmi_issue" style="width:100%;">';
  echo '<option value="">' . esc_html__('� Select an issue �', 'laa') . '</option>';

  if (!is_wp_error($terms) && $terms) {
    foreach ($terms as $t) {
      printf(
        '<option value="%s"%s>%s</option>',
        esc_attr($t->slug),
        selected($saved_slug, $t->slug, false),
        esc_html($t->name)
      );
    }
  } else {
    echo '<option value="">' . esc_html__('No NSMI terms found (taxonomy: nsmi_category)', 'laa') . '</option>';
  }

  echo '</select>';
  echo '<p class="description" style="margin-top:.5rem;">' .
       esc_html__('Pick the top-level NSMI term this page should display. The accordion shortcode will use this automatically.', 'laa') .
       '</p>';
}

/** Save selected slug to _nsmi_issue */
add_action('save_post_page', function($post_id){
    if (!taxonomy_exists('nsmi_category')) return;
  if (!isset($_POST['laa_nsmi_page_term_n']) || !wp_verify_nonce($_POST['laa_nsmi_page_term_n'], 'laa_nsmi_page_term_n')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $slug = isset($_POST['laa_nsmi_issue']) ? sanitize_title($_POST['laa_nsmi_issue']) : '';
  if ($slug) {
    update_post_meta($post_id, '_nsmi_issue', $slug);
  } else {
    delete_post_meta($post_id, '_nsmi_issue');
  }
});