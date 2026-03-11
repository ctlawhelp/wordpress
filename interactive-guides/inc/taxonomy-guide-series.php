<?php
/**
 * Register Guide Series taxonomy for clustering Legal Guides
 */

function ig_register_guide_series_taxonomy() {
    register_taxonomy( 'guide_series', 'interactive_guide', [
        'hierarchical'      => false, // Like tags, not categories
        'labels'            => [
            'name'              => __( 'Guide Series Admin', 'ctlawhelp' ),
            'singular_name'     => __( 'Guide Series Admin', 'ctlawhelp' ),
            'menu_name'         => __( 'Guide Series Admin', 'ctlawhelp' ),
            'all_items'         => __( 'All Guide Series', 'ctlawhelp' ),
            'edit_item'         => __( 'Edit Guide Series', 'ctlawhelp' ),
            'view_item'         => __( 'View Guide Series', 'ctlawhelp' ),
            'update_item'       => __( 'Update Guide Series', 'ctlawhelp' ),
            'add_new_item'      => __( 'Add New Guide Series', 'ctlawhelp' ),
            'new_item_name'     => __( 'New Guide Series Name', 'ctlawhelp' ),
            'search_items'      => __( 'Search Guide Series', 'ctlawhelp' ),
            'not_found'         => __( 'No guide series found', 'ctlawhelp' ),
            'back_to_items'     => __( 'Back to Guide Series Admin', 'ctlawhelp' ),
        ],
        'show_ui'           => true,
        'show_admin_column' => true, // Shows column in admin list
        'show_in_menu'      => true,
        'show_tagcloud'     => false,
        'rewrite'           => [ 'slug' => 'guide-series' ],
        'show_in_rest'      => true,
        'capabilities'      => [
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories', 
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ],
    ]);
}
add_action( 'init', 'ig_register_guide_series_taxonomy', 0 );

// Add Guide Series metabox to Legal Guides
add_action('add_meta_boxes', function() {
    add_meta_box(
        'ig_guide_series_mb',
        __('Guide Series Admin', 'ctlawhelp'),
        'ig_render_guide_series_metabox',
        'interactive_guide',
        'side',
        'high'
    );
});

function ig_render_guide_series_metabox($post) {
    // Check memory usage before heavy operations
    if (!defined('DISABLE_IG_DEBUG') || !DISABLE_IG_DEBUG) {
        error_log('Guide Series Metabox - Memory usage: ' . memory_get_usage(true) / 1024 / 1024 . 'MB');
    }
    
    $tax_name = 'guide_series';
    $terms = get_terms([ 
        'taxonomy' => $tax_name, 
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'number' => 50 // Limit to prevent memory issues
    ]);
    $selected = wp_get_object_terms($post->ID, $tax_name, ['fields'=>'ids']);
    
    wp_nonce_field('ig_guide_series_save', 'ig_guide_series_nonce');
    
    echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;">';
    
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $checked = in_array($term->term_id, $selected) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="tax_input[guide_series][]" value="' . $term->term_id . '" ' . $checked . '> ';
            echo esc_html($term->name);
            echo '</label>';
        }
    } else {
        echo '<p><em>No guide series created yet. <a href="' . admin_url('edit-tags.php?taxonomy=guide_series&post_type=interactive_guide') . '">Create one</a></em></p>';
    }
    
    echo '</div>';
    
    echo '<p style="margin-top: 10px;"><strong>Create New Series:</strong></p>';
    echo '<input type="text" name="new_guide_series" placeholder="e.g., Bankruptcy Help" style="width: 100%;">';
    echo '<p class="description">Enter a name to create a new guide series and assign this guide to it.</p>';
}

// Save Guide Series assignments
add_action('save_post_interactive_guide', function($post_id) {
    if (!isset($_POST['ig_guide_series_nonce']) || !wp_verify_nonce($_POST['ig_guide_series_nonce'], 'ig_guide_series_save')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Handle new series creation
    if (!empty($_POST['new_guide_series'])) {
        $new_series_name = trim(sanitize_text_field($_POST['new_guide_series']));
        
        // Check if term already exists
        $existing_term = term_exists($new_series_name, 'guide_series');
        
        if ($existing_term) {
            // Use existing term
            $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
        } else {
            // Create new term with custom slug to avoid conflicts
            $slug = sanitize_title($new_series_name);
            $new_term = wp_insert_term($new_series_name, 'guide_series', [
                'slug' => $slug
            ]);
            
            if (is_wp_error($new_term)) {
                // If there's an error, log it but don't break the save
                error_log('Guide Series creation error: ' . $new_term->get_error_message());
                return;
            }
            $term_id = $new_term['term_id'];
        }
        
        // Add the term to the selected terms
        $selected_terms = isset($_POST['tax_input']['guide_series']) ? $_POST['tax_input']['guide_series'] : [];
        $selected_terms[] = $term_id;
        $_POST['tax_input']['guide_series'] = $selected_terms;
    }
    
    // WordPress will handle the tax_input automatically, but we can also do it manually if needed
    if (isset($_POST['tax_input']['guide_series'])) {
        $term_ids = array_map('intval', $_POST['tax_input']['guide_series']);
        wp_set_object_terms($post_id, $term_ids, 'guide_series');
    } else {
        // Remove all series if none selected
        wp_set_object_terms($post_id, [], 'guide_series');
    }
});

// Add custom column to admin list for better organization
add_filter('manage_interactive_guide_posts_columns', function($columns) {
    // Insert Guide Series column after title
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['guide_series'] = __('Guide Series Admin', 'ctlawhelp');
        }
    }
    return $new_columns;
});

add_action('manage_interactive_guide_posts_custom_column', function($column, $post_id) {
    if ($column === 'guide_series') {
        $terms = get_the_terms($post_id, 'guide_series');
        if ($terms && !is_wp_error($terms)) {
            $term_links = [];
            foreach ($terms as $term) {
                $term_links[] = '<a href="' . admin_url('edit.php?post_type=interactive_guide&guide_series=' . $term->slug) . '">' . esc_html($term->name) . '</a>';
            }
            echo implode(', ', $term_links);
        } else {
            echo '<span style="color: #999;">No Series</span>';
        }
    }
}, 10, 2);

// Make Guide Series column sortable
add_filter('manage_edit-interactive_guide_sortable_columns', function($columns) {
    $columns['guide_series'] = 'guide_series';
    return $columns;
});

// Add filter dropdown in admin
add_action('restrict_manage_posts', function() {
    global $typenow;
    
    if ($typenow === 'interactive_guide') {
        $selected = isset($_GET['guide_series']) ? $_GET['guide_series'] : '';
        $terms = get_terms([
            'taxonomy' => 'guide_series',
            'hide_empty' => true,
            'orderby' => 'name'
        ]);
        
        if (!empty($terms) && !is_wp_error($terms)) {
            echo '<select name="guide_series">';
            echo '<option value="">All Guide Series</option>';
            foreach ($terms as $term) {
                printf(
                    '<option value="%s"%s>%s (%d)</option>',
                    $term->slug,
                    selected($selected, $term->slug, false),
                    $term->name,
                    $term->count
                );
            }
            echo '</select>';
        }
    }
});

// Handle the filter
add_filter('parse_query', function($query) {
    global $pagenow;
    
    if ($pagenow === 'edit.php' && 
        isset($_GET['post_type']) && $_GET['post_type'] === 'interactive_guide' &&
        isset($_GET['guide_series']) && !empty($_GET['guide_series'])) {
        
        $query->query_vars['guide_series'] = $_GET['guide_series'];
    }
});