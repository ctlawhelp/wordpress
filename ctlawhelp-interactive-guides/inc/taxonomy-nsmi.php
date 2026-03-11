<?php
/**
 * Register or extend NSMI taxonomy for Legal Guides
 */

function ig_register_nsmi_taxonomy() {
    // Check if the taxonomy already exists
    if ( taxonomy_exists( 'nsmi_category' ) ) {
        // If it exists, just register it for our post type
        register_taxonomy_for_object_type( 'nsmi_category', 'interactive_guide' );
    } else {
        // If it doesn't exist, create it (but ctlawhelp-legal-aid-articles should handle this)
        register_taxonomy( 'nsmi_category', [ 'interactive_guide', 'legal_article', 'post' ], [
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
            'show_in_menu'      => false, // Don't show in default location (same as legal-aid-articles)
        ]);
    }
}
add_action( 'init', 'ig_register_nsmi_taxonomy', 1 );
