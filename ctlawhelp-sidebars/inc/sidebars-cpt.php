<?php
if (!defined('ABSPATH')) exit;

/**
 * CPT: Sidebars (admin-managed content for NSMI landing page sidebars)
 * Post type key: laa_sidebar
 * Appears under: Legal Aid Articles menu
 */
add_action('init', function () {
  $labels = array(
    'name'               => __('Sidebars', 'laa'),
    'singular_name'      => __('Sidebar', 'laa'),
    'menu_name'          => __('Sidebars', 'laa'),
    'name_admin_bar'     => __('Sidebar', 'laa'),
    'add_new'            => __('Add New', 'laa'),
    'add_new_item'       => __('Add New Sidebar', 'laa'),
    'edit_item'          => __('Edit Sidebar', 'laa'),
    'new_item'           => __('New Sidebar', 'laa'),
    'view_item'          => __('View Sidebar', 'laa'),
    'all_items'          => __('All Sidebars', 'laa'),
    'search_items'       => __('Search Sidebars', 'laa'),
    'not_found'          => __('No sidebars found.', 'laa'),
    'not_found_in_trash' => __('No sidebars found in Trash.', 'laa'),
  );

  register_post_type('laa_sidebar', array(
    'labels'             => $labels,
    'public'             => false,                  // not front-end routable
    'publicly_queryable' => false,
    'show_ui'            => true,                   // visible in admin
    'show_in_menu'       => 'edit.php?post_type=legal_article', // under Legal Aid Articles
    'show_in_rest'       => true,                   // block editor
    'hierarchical'       => false,
    'supports'           => array('title','editor','revisions'),
    'has_archive'        => false,
    'rewrite'            => false,
    'menu_position'      => null,
    'capability_type'    => 'post',
  ));
});

/**
 * Admin note on the Sidebars screens
 * Explains what these control and where NSMI Landing pages live.
 */
add_action('admin_notices', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->post_type !== 'laa_sidebar') return;

  echo '<div class="notice notice-info"><p>'
     . '<strong>' . esc_html__('Sidebars', 'laa') . ':</strong> '
     . esc_html__('These sidebars control the left column on the NSMI Landing Pages.', 'laa')
     . ' '
     . esc_html__('You can edit NSMI Landing Pages under', 'laa')
     . ' <strong>' . esc_html__('Pages', 'laa') . '</strong>.'
     . ' '
     . esc_html__('Assign sidebars to pages and legal articles using the "Sidebar" meta box on each post.', 'laa')
     . '</p></div>';
});

/**
 * Add meta box to assign sidebars to pages and legal articles
 */
add_action('add_meta_boxes', function() {
    // Add to pages (top-level landing pages)
    add_meta_box(
        'laa_sidebar_assignment',
        __('Sidebar Assignment', 'laa'),
        'laa_sidebar_assignment_meta_box',
        'page',
        'side',
        'default'
    );

    // Add to legal articles
    add_meta_box(
        'laa_sidebar_assignment',
        __('Sidebar Assignment', 'laa'),
        'laa_sidebar_assignment_meta_box',
        'legal_article',
        'side',
        'default'
    );
});

/**
 * Add sidebar assignment meta box to nsmi_landing CPT.
 * Hooked via nsmi_landing_add_side_meta_boxes so nsmi-landing plugin
 * does not directly reference this function — if this plugin is
 * deactivated, the hook simply fires with no listeners and no error.
 */
add_action( 'nsmi_landing_add_side_meta_boxes', function( $post_type ) {
    add_meta_box(
        'laa_sidebar_assignment',
        __('Sidebar Assignment', 'laa'),
        'laa_sidebar_assignment_meta_box',
        $post_type,
        'side',
        'default'
    );
});

/**
 * Render the sidebar assignment meta box
 */
function laa_sidebar_assignment_meta_box($post) {
    wp_nonce_field('laa_sidebar_assignment_nonce', 'laa_sidebar_assignment_nonce');

    $assigned_sidebar = get_post_meta($post->ID, '_assigned_sidebar', true);

    // Get all available sidebars
    $sidebars = get_posts([
        'post_type' => 'laa_sidebar',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    echo '<p><label for="assigned_sidebar">' . esc_html__('Select Sidebar:', 'laa') . '</label></p>';
    echo '<select name="assigned_sidebar" id="assigned_sidebar" style="width: 100%;">';
    echo '<option value="">' . esc_html__('Use default sidebar logic', 'laa') . '</option>';

    foreach ($sidebars as $sidebar) {
        $selected = ($assigned_sidebar == $sidebar->ID) ? ' selected="selected"' : '';
        echo '<option value="' . esc_attr($sidebar->ID) . '"' . $selected . '>' . esc_html($sidebar->post_title) . '</option>';
    }

    echo '</select>';
    echo '<p class="description">' . esc_html__('Choose a custom sidebar for this page/article, or leave blank to use the global sidebar.', 'laa') . '</p>';
}

/**
 * Save the sidebar assignment
 */
add_action('save_post', function($post_id) {
    if (!isset($_POST['laa_sidebar_assignment_nonce']) ||
        !wp_verify_nonce($_POST['laa_sidebar_assignment_nonce'], 'laa_sidebar_assignment_nonce')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['assigned_sidebar'])) {
        update_post_meta($post_id, '_assigned_sidebar', sanitize_text_field($_POST['assigned_sidebar']));
    } else {
        delete_post_meta($post_id, '_assigned_sidebar');
    }
});
