<?php
function laa_register_nsmi_taxonomy() {
    register_taxonomy( 'nsmi_category', [ 'legal_article', 'post', 'interactive_guide' ], [
        'hierarchical'      => true,
        'labels'            => [
            'name'          => __( 'NSMI Categories', 'ctlawhelp' ),
            'singular_name' => __( 'NSMI Category', 'ctlawhelp' ),
            'menu_name'     => __( 'Categories', 'ctlawhelp' ),
        ],
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'nsmi' ],
        'show_in_rest'      => true,
        'show_in_menu'      => false, // Don't show in default location
    ]);
}

// Create custom top-level NSMI menu
add_action('admin_menu', function() {
    // Add top-level menu
    add_menu_page(
        'NSMI Management',           // Page title
        'NSMI',                      // Menu title
        'manage_categories',         // Capability
        'nsmi-management',           // Menu slug
        'nsmi_management_dashboard', // Function (dashboard page)
        'dashicons-category',        // Icon
        9                           // Position between Legal Guides and Media
    );
    
    // Add Categories submenu (this will be the main categories page)
    add_submenu_page(
        'nsmi-management',
        'NSMI Categories',
        'Categories',
        'manage_categories',
        'edit-tags.php?taxonomy=nsmi_category&post_type=legal_article'
    );
}, 9); // Early priority to ensure it's available

// Dashboard page for NSMI management
function nsmi_management_dashboard() {
    if (!current_user_can('manage_categories')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Get some basic stats
    $total_categories = wp_count_terms([
        'taxonomy' => 'nsmi_category',
        'hide_empty' => false
    ]);
    
    $en_categories = wp_count_terms([
        'taxonomy' => 'nsmi_category',
        'hide_empty' => false,
        'lang' => 'en'
    ]);
    
    $es_categories = wp_count_terms([
        'taxonomy' => 'nsmi_category',
        'hide_empty' => false,
        'lang' => 'es'
    ]);
    
    ?>
    <div class="wrap">
        <h1>NSMI Management</h1>
        <p>Welcome to the NSMI (National Subject Matter Index) management area.</p>
        
        <div class="dashboard-widgets-wrap">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                
                <!-- Quick Stats -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Category Statistics</h2>
                    </div>
                    <div class="inside">
                        <p><strong>Total Categories:</strong> <?php echo intval($total_categories); ?></p>
                        <p><strong>English Categories:</strong> <?php echo intval($en_categories); ?></p>
                        <p><strong>Spanish Categories:</strong> <?php echo intval($es_categories); ?></p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="inside">
                        <p><a href="<?php echo admin_url('edit-tags.php?taxonomy=nsmi_category&post_type=legal_article'); ?>" class="button button-primary">Manage Categories</a></p>
                        <p><a href="<?php echo admin_url('admin.php?page=nsmi-categories-overview'); ?>" class="button">Categories Overview</a></p>
                        <p><a href="<?php echo admin_url('edit-tags.php?taxonomy=nsmi_category&post_type=legal_article'); ?>" class="button">Add New Category</a></p>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <h2>About NSMI Categories</h2>
            <p>The National Subject Matter Index (NSMI) provides a standardized way to categorize legal aid content. Categories are organized hierarchically and support bilingual English/Spanish translations.</p>
            
            <h3>Management Options:</h3>
            <ul>
                <li><strong>Categories:</strong> Use the standard WordPress interface to add, edit, and organize categories</li>
                <li><strong>Categories Overview:</strong> View English/Spanish category pairs in a bilingual layout</li>
            </ul>
        </div>
    </div>
    <?php
}
add_action( 'init', 'laa_register_nsmi_taxonomy', 1 );