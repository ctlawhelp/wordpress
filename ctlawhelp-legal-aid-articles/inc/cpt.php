<?php
// Register post type immediately when this file is loaded to prevent capability check errors
laa_register_post_type();

function laa_register_post_type() {
    register_post_type( 'legal_article', [
        'labels' => [
            'name'          => 'Legal Aid Articles',
            'singular_name' => 'Legal Aid Article',
            'menu_name'     => 'Legal Aid Articles',
        ],
        'public'        => true,
        'has_archive'   => false,              // no automatic archive
        'rewrite'       => false,              // 🚫 disables /articles/ base
        'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
        'show_in_rest'  => true,
        'menu_position' => 2,
    ]);

    // Gutenberg uses the REST post type object's `supports.slug` flag to decide whether the Slug UI is editable.
    // When `rewrite => false`, WordPress can treat the slug as non-editable in the editor UI.
    // Explicitly declare slug support so the Slug panel is editable.
    add_post_type_support( 'legal_article', 'slug' );
}
// Also register on init for safety
add_action( 'init', 'laa_register_post_type', 0 );