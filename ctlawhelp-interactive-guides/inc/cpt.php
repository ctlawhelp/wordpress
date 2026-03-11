<?php
/**
 * Register Legal Guide Custom Post Type
 */

function ig_register_post_type() {
    register_post_type( 'interactive_guide', [
        'labels' => [
            'name'          => 'Guide Pages',
            'singular_name' => 'Guide Page', 
            'menu_name'     => 'Legal Guides',
        ],
        'public'        => true,
        'has_archive'   => false,              // no automatic archive (same as legal_article)
        'rewrite'       => array(
            'slug'       => 'guide',           // Use /guide/ instead of /interactive-guides/
            'with_front' => false,             // Don't prepend blog prefix
            'feeds'      => false,             // No RSS feeds
        ),
        'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
        'show_in_rest'  => true,
        'menu_position' => 8, // Position between Pages and NSMI
        'menu_icon'     => 'dashicons-welcome-learn-more',
    ]);
}
// Register on init when WordPress is ready
add_action( 'init', 'ig_register_post_type', 0 );