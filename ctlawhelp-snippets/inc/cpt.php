<?php
/**
 * Legal Aid Snippets - Custom Post Type Registration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function las_register_snippet_post_type() {
	$labels = array(
		'name'                  => _x( 'Snippets', 'Post Type General Name', 'legal-aid-snippets' ),
		'singular_name'         => _x( 'Snippet', 'Post Type Singular Name', 'legal-aid-snippets' ),
		'menu_name'             => __( 'Snippets', 'legal-aid-snippets' ),
		'name_admin_bar'        => __( 'Snippet', 'legal-aid-snippets' ),
		'archives'              => __( 'Snippet Archives', 'legal-aid-snippets' ),
		'attributes'            => __( 'Snippet Attributes', 'legal-aid-snippets' ),
		'parent_item_colon'     => __( 'Parent Snippet:', 'legal-aid-snippets' ),
		'all_items'             => __( 'All Snippets', 'legal-aid-snippets' ),
		'add_new_item'          => __( 'Add New Snippet', 'legal-aid-snippets' ),
		'add_new'               => __( 'Add New', 'legal-aid-snippets' ),
		'new_item'              => __( 'New Snippet', 'legal-aid-snippets' ),
		'edit_item'             => __( 'Edit Snippet', 'legal-aid-snippets' ),
		'update_item'           => __( 'Update Snippet', 'legal-aid-snippets' ),
		'view_item'             => __( 'View Snippet', 'legal-aid-snippets' ),
		'view_items'            => __( 'View Snippets', 'legal-aid-snippets' ),
		'search_items'          => __( 'Search Snippets', 'legal-aid-snippets' ),
		'not_found'             => __( 'Not found', 'legal-aid-snippets' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'legal-aid-snippets' ),
		'featured_image'        => __( 'Featured Image', 'legal-aid-snippets' ),
		'set_featured_image'    => __( 'Set featured image', 'legal-aid-snippets' ),
		'remove_featured_image' => __( 'Remove featured image', 'legal-aid-snippets' ),
		'use_featured_image'    => __( 'Use as featured image', 'legal-aid-snippets' ),
		'insert_into_item'      => __( 'Insert into snippet', 'legal-aid-snippets' ),
		'uploaded_to_this_item' => __( 'Uploaded to this snippet', 'legal-aid-snippets' ),
		'items_list'            => __( 'Snippets list', 'legal-aid-snippets' ),
		'items_list_navigation' => __( 'Snippets list navigation', 'legal-aid-snippets' ),
		'filter_items_list'     => __( 'Filter snippets list', 'legal-aid-snippets' ),
	);

	$args = array(
		'label'                 => __( 'Snippet', 'legal-aid-snippets' ),
		'description'           => __( 'Reusable content snippets', 'legal-aid-snippets' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'excerpt', 'revisions' ),
		'hierarchical'          => false,
		'public'                => false,  // Not public-facing
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 25,     // After comments
		'menu_icon'             => 'dashicons-editor-code',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,  // No archive page needed
		'exclude_from_search'   => true,   // Don't show in search
		'publicly_queryable'    => false,  // Can't be accessed directly
		'capability_type'       => 'post',
		'show_in_rest'          => true,   // Enable Gutenberg editor
	);

	register_post_type( 'legal_snippet', $args );
}

// Ensure the custom post type is registered on init even if the
// main plugin class fails to call las_register_snippet_post_type().
add_action( 'init', 'las_register_snippet_post_type' );

// Add custom columns to the admin list
add_filter( 'manage_legal_snippet_posts_columns', 'las_add_snippet_columns' );
function las_add_snippet_columns( $columns ) {
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = $columns['title'];
	$new_columns['snippet_shortcode'] = __( 'Shortcode', 'legal-aid-snippets' );
	$new_columns['snippet_usage'] = __( 'Usage Count', 'legal-aid-snippets' );
	$new_columns['date'] = $columns['date'];
	
	return $new_columns;
}

// Populate custom columns
add_action( 'manage_legal_snippet_posts_custom_column', 'las_populate_snippet_columns', 10, 2 );
function las_populate_snippet_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'snippet_shortcode':
			$post = get_post( $post_id );
			echo '<code>[snippet slug="' . esc_html( $post->post_name ) . '"]</code>';
			echo '<br><small>or</small><br>';
			echo '<code>[snippet id="' . esc_html( $post_id ) . '"]</code>';
			break;
			
		case 'snippet_usage':
			// TODO: Implement usage tracking in future version
			echo '<span style="color: #999;">—</span>';
			break;
	}
}