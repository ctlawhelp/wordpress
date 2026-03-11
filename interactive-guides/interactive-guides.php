<?php
/**
 * Plugin Name: Legal Guides
 * Description: Custom post type for Legal Guides with NSMI categories support and Interactive Guide builder.
 * Version: 1.1
 * Author: CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
define( 'IG_PATH', plugin_dir_path( __FILE__ ) );
define( 'IG_URL',  plugin_dir_url( __FILE__ ) );

// Disable debug logging if causing issues
define( 'DISABLE_IG_DEBUG', true );

// Emergency disable switch - set to true if causing memory issues
define( 'DISABLE_GUIDE_SERIES', true );

// -----------------------------------------------------------------------------
// Includes
// -----------------------------------------------------------------------------
require_once IG_PATH . 'inc/cpt.php';
require_once IG_PATH . 'inc/taxonomy-nsmi.php';
require_once IG_PATH . 'inc/admin-ui.php';
require_once IG_PATH . 'inc/render-shortcode.php';

// Last reviewed date meta box for interactive_guide
require_once IG_PATH . 'inc/last-reviewed-metabox.php';

// Only load Guide Series if not disabled
if ( ! defined( 'DISABLE_GUIDE_SERIES' ) || ! DISABLE_GUIDE_SERIES ) {
	require_once IG_PATH . 'inc/taxonomy-guide-series.php';
}

// -----------------------------------------------------------------------------
// Front-End Assets
// -----------------------------------------------------------------------------
function ig_enqueue_assets() {
	$css_file = IG_PATH . 'assets/css/interactive-guides.css';
	$js_file  = IG_PATH . 'assets/js/interactive-guides.js';

	if ( file_exists( $css_file ) ) {
		wp_enqueue_style( 'ig-css', IG_URL . 'assets/css/interactive-guides.css', array(), '1.2' );
	}

	if ( file_exists( $js_file ) ) {
		wp_enqueue_script( 'ig-js', IG_URL . 'assets/js/interactive-guides.js', array( 'jquery' ), '1.2', true );
	}
}
add_action( 'wp_enqueue_scripts', 'ig_enqueue_assets' );

// -----------------------------------------------------------------------------
// Admin Assets (Meta Box UI)
// -----------------------------------------------------------------------------
function ig_enqueue_admin_assets( $hook ) {
	global $post;

	if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'interactive_guide' ) {
		wp_enqueue_script( 'ig-admin-js', IG_URL . 'assets/js/admin.js', array( 'jquery' ), '1.2', true );
		wp_enqueue_style( 'ig-admin-css', IG_URL . 'assets/css/admin.css', array(), '1.2' );
	}
}
add_action( 'admin_enqueue_scripts', 'ig_enqueue_admin_assets' );

// -----------------------------------------------------------------------------
// Post Type Labels Update
// -----------------------------------------------------------------------------
add_action( 'init', function() {
	$pt = get_post_type_object( 'interactive_guide' );
	if ( ! $pt || empty( $pt->labels ) ) return;

	$pt->labels->name               = 'Guide Pages';
	$pt->labels->menu_name          = 'Legal Guides';
	$pt->labels->singular_name      = 'Guide Page';
	$pt->labels->all_items          = 'All Guide Pages';
	$pt->labels->add_new            = 'Add Guide Page';
	$pt->labels->add_new_item       = 'Add New Guide Page';
	$pt->labels->edit_item          = 'Edit Guide Page';
	$pt->labels->new_item           = 'Guide Page';
	$pt->labels->view_item          = 'View Guide Page';
	$pt->labels->search_items       = 'Search Guide Pages';
	$pt->labels->not_found          = 'No Guide Pages found';
	$pt->labels->not_found_in_trash = 'No Guide Pages found in Trash';
	$pt->label                      = 'Guide Pages';
}, 20 );

// -----------------------------------------------------------------------------
// Activation / Deactivation Hooks
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, 'ig_activation' );
function ig_activation() {
	// Register post type and taxonomies directly
	ig_register_post_type();
	ig_register_nsmi_taxonomy();

	if ( ! defined( 'DISABLE_GUIDE_SERIES' ) || ! DISABLE_GUIDE_SERIES ) {
		ig_register_guide_series_taxonomy();
	}

	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'ig_deactivation' );
function ig_deactivation() {
	flush_rewrite_rules();
}
