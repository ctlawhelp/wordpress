<?php
if (!defined('ABSPATH')) exit;

/**
 * Adds a full-width WYSIWYG metabox on Pages:
 * "NSMI Landing: HTML Above Accordion"
 * Saves to post meta key: _nsmi_html_above
 */

add_action('add_meta_boxes', function () {
  add_meta_box(
    'laa_nsmi_html_above_mb',
    __('NSMI Landing: HTML Above Accordion', 'laa'),
    'laa_render_nsmi_html_above_mb',
    'page',
    'normal',   // full-width area under the main editor
    'high'
  );
});

function laa_render_nsmi_html_above_mb($post) {
  wp_nonce_field('laa_nsmi_html_above_mb', 'laa_nsmi_html_above_mb_n');
  $val = get_post_meta($post->ID, '_nsmi_html_above', true);

  // Classic editor field inside a metabox (works fine in Gutenberg)
  wp_editor(
    $val,
    'laa_nsmi_html_above',
    array(
      'textarea_name' => 'laa_nsmi_html_above',
      'textarea_rows' => 8,
      'media_buttons' => true,
      'teeny'         => false,
      'quicktags'     => true,
    )
  );

  echo '<p class="description">'
     . esc_html__('This content renders above the accordion on NSMI Landing pages that use the NSMI template.', 'laa')
     . '</p>';
}

add_action('save_post_page', function ($post_id) {
  if (!isset($_POST['laa_nsmi_html_above_mb_n']) || !wp_verify_nonce($_POST['laa_nsmi_html_above_mb_n'], 'laa_nsmi_html_above_mb')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_page', $post_id)) return;

  $val = isset($_POST['laa_nsmi_html_above']) ? (string) $_POST['laa_nsmi_html_above'] : '';
  // Allow standard post HTML
  $val = wp_kses_post($val);

  if ($val !== '') {
    update_post_meta($post_id, '_nsmi_html_above', $val);
  } else {
    delete_post_meta($post_id, '_nsmi_html_above');
  }
});
