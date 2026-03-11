<?php
/**
 * Admin UI for Interactive Guide Steps
 */

if (!defined('ABSPATH')) exit;

// Add Meta Box
add_action('add_meta_boxes', function() {
    add_meta_box(
        'ig_steps_box',
        __('Interactive Guide Steps', 'ctlawhelp'),
        'ig_render_steps_box',
        'interactive_guide',
        'normal',
        'high'
    );
});

// Render Meta Box
function ig_render_steps_box($post) {
    wp_nonce_field('ig_save_steps', 'ig_steps_nonce');

    $json = get_post_meta($post->ID, '_clh_guide_steps', true);
    $json = $json ? esc_textarea($json) : '[]';

    echo '<div id="ig-step-builder"></div>';
    echo '<input type="hidden" id="ig-steps-json" name="ig_steps_json" value="' . $json . '" />';
    echo '<p class="description">Add or edit steps below. Each step can be an info, question, or form type.</p>';
}

// Save Meta Box
add_action('save_post_interactive_guide', function($post_id) {
    if (!isset($_POST['ig_steps_nonce']) || !wp_verify_nonce($_POST['ig_steps_nonce'], 'ig_save_steps')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['ig_steps_json'])) {
        update_post_meta($post_id, '_clh_guide_steps', wp_unslash($_POST['ig_steps_json']));
    }
});
