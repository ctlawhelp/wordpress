<?php
/**
 * Registers the NSMI Landing Pages custom post type.
 * Slug: nsmi_landing
 * Admin label: NSMI Landing Pages
 * Appears under the NSMI admin menu.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'lal_register_nsmi_landing_cpt' );

function lal_register_nsmi_landing_cpt() {
	$labels = array(
		'name'               => __( 'NSMI Landing Pages', 'ctlawhelp-nsmi-landing' ),
		'singular_name'      => __( 'NSMI Landing Page', 'ctlawhelp-nsmi-landing' ),
		'add_new'            => __( 'Add New', 'ctlawhelp-nsmi-landing' ),
		'add_new_item'       => __( 'Add New NSMI Landing Page', 'ctlawhelp-nsmi-landing' ),
		'edit_item'          => __( 'Edit NSMI Landing Page', 'ctlawhelp-nsmi-landing' ),
		'new_item'           => __( 'New NSMI Landing Page', 'ctlawhelp-nsmi-landing' ),
		'view_item'          => __( 'View NSMI Landing Page', 'ctlawhelp-nsmi-landing' ),
		'search_items'       => __( 'Search NSMI Landing Pages', 'ctlawhelp-nsmi-landing' ),
		'not_found'          => __( 'No NSMI Landing Pages found', 'ctlawhelp-nsmi-landing' ),
		'not_found_in_trash' => __( 'No NSMI Landing Pages found in Trash', 'ctlawhelp-nsmi-landing' ),
		'menu_name'          => __( 'NSMI Landing Pages', 'ctlawhelp-nsmi-landing' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => 'nsmi-management',
		'menu_icon'          => 'dashicons-category',
		'supports'           => array( 'title' ),
		'has_archive'        => false,
		'show_in_rest'       => false,
		'exclude_from_search'=> false,
		'publicly_queryable' => true,
		'hierarchical'       => false,
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'show_in_admin_bar'  => false,
		'show_in_nav_menus'  => true,
		'rewrite'            => array( 'slug' => 'nsmi-landing', 'with_front' => false ),
	);

	register_post_type( 'nsmi_landing', $args );
}

// Remove block editor support for nsmi_landing CPT
add_filter( 'use_block_editor_for_post_type', 'lal_disable_gutenberg_for_nsmi_landing', 10, 2 );
function lal_disable_gutenberg_for_nsmi_landing( $use_block_editor, $post_type ) {
	if ( $post_type === 'nsmi_landing' ) {
		return false;
	}
	return $use_block_editor;
}

// Remove classic editor content editor for nsmi_landing CPT
add_action( 'admin_init', 'lal_remove_editor_from_nsmi_landing' );
function lal_remove_editor_from_nsmi_landing() {
	remove_post_type_support( 'nsmi_landing', 'editor' );
}

// Add metaboxes for nsmi_landing CPT
add_action('add_meta_boxes', function() {
	global $post;
	$screen = get_current_screen();
	if ($screen && $screen->post_type !== 'nsmi_landing') return;

	// Top-Level NSMI Issue (dropdown)
	add_meta_box(
		'lal_nsmi_issue_mb',
		__('Top-Level NSMI Issue', 'ctlawhelp-nsmi-landing'),
		'lal_render_nsmi_issue_mb',
		'nsmi_landing',
		'side',
		'high'
	);

	// HTML Above Accordion (WYSIWYG)
	add_meta_box(
		'lal_nsmi_html_above_mb',
		__('HTML Above Accordion', 'ctlawhelp-nsmi-landing'),
		'lal_render_nsmi_html_above_mb',
		'nsmi_landing',
		'normal',
		'high'
	);

	// Sidebar Assignment — sidebars plugin hooks in here if active
	do_action( 'nsmi_landing_add_side_meta_boxes', 'nsmi_landing' );
});

// Render Top-Level NSMI Issue dropdown
function lal_render_nsmi_issue_mb($post) {
	wp_nonce_field('lal_nsmi_issue_mb', 'lal_nsmi_issue_mb_n');
	$selected = get_post_meta($post->ID, '_nsmi_issue', true);
	$args = array(
		'taxonomy' => 'nsmi_category',
		'hide_empty' => false,
		'parent' => 0,
		'orderby' => 'name',
		'order' => 'ASC',
	);
	$terms = get_terms($args);
	echo '<select name="lal_nsmi_issue" id="lal_nsmi_issue" required style="width:100%">';
	echo '<option value="">' . esc_html__('Select a top-level NSMI issue', 'ctlawhelp-nsmi-landing') . '</option>';
	foreach ($terms as $term) {
		$is_selected = selected($selected, $term->slug, false);
		echo '<option value="' . esc_attr($term->slug) . '"' . $is_selected . '>' . esc_html($term->name) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__('Required. Choose the main NSMI issue for this landing page.', 'ctlawhelp-nsmi-landing') . '</p>';
}

// Render HTML Above Accordion WYSIWYG
function lal_render_nsmi_html_above_mb($post) {
	wp_nonce_field('lal_nsmi_html_above_mb', 'lal_nsmi_html_above_mb_n');
	$val = get_post_meta($post->ID, '_nsmi_html_above', true);
	wp_editor(
		$val,
		'lal_nsmi_html_above',
		array(
			'textarea_name' => 'lal_nsmi_html_above',
			'textarea_rows' => 8,
			'media_buttons' => true,
			'teeny'         => false,
			'quicktags'     => true,
		)
	);
	echo '<p class="description">' . esc_html__('Required. This content renders above the accordion.', 'ctlawhelp-nsmi-landing') . '</p>';
}

// Save metaboxes for nsmi_landing CPT
add_action('save_post_nsmi_landing', function($post_id) {
	// Nonce and capability checks
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	// Top-Level NSMI Issue
	if (isset($_POST['lal_nsmi_issue_mb_n']) && wp_verify_nonce($_POST['lal_nsmi_issue_mb_n'], 'lal_nsmi_issue_mb')) {
		$issue = isset($_POST['lal_nsmi_issue']) ? sanitize_text_field($_POST['lal_nsmi_issue']) : '';
		if ($issue !== '') {
			update_post_meta($post_id, '_nsmi_issue', $issue);
		} else {
			delete_post_meta($post_id, '_nsmi_issue');
		}
	}

	// HTML Above Accordion
	if (isset($_POST['lal_nsmi_html_above_mb_n']) && wp_verify_nonce($_POST['lal_nsmi_html_above_mb_n'], 'lal_nsmi_html_above_mb')) {
		$val = isset($_POST['lal_nsmi_html_above']) ? (string) $_POST['lal_nsmi_html_above'] : '';
		$val = wp_kses_post($val);
		if ($val !== '') {
			update_post_meta($post_id, '_nsmi_html_above', $val);
		} else {
			delete_post_meta($post_id, '_nsmi_html_above');
		}
	}

	// Sidebar Assignment save is handled by ctlawhelp-sidebars via its generic save_post hook.
});

// Polylang compatibility: register nsmi_landing CPT with Polylang if available
add_action('init', function() {
	if (function_exists('pll_register_post_type')) {
		pll_register_post_type('nsmi_landing');
	}
});
