<?php
/**
 * Plugin Name: CTLawHelp Sidebars
 * Description: Advanced sidebar management for NSMI landing pages. Requires Legal Aid Articles.
 * Version: 1.0.0
 * Author: CTLawHelp
 * Requires Plugins: ctlawhelp-legal-aid-articles
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check if required plugin is active
function las_check_dependencies() {
    if ( ! is_plugin_active( 'ctlawhelp-legal-aid-articles/ctlawhelp-legal-aid-articles.php' )
         ) {
        add_action( 'admin_notices', 'las_dependency_notice' );
        return false;
    }
    return true;
}

function las_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>Legal Aid Sidebars:</strong> This plugin requires the Legal Aid Articles plugin to be active.</p>
    </div>
    <?php
}

// Only proceed if dependencies are met
if ( ! las_check_dependencies() ) {
    return;
}

// Use plugin-specific constants to avoid collisions with other plugins.
define( 'LAA_SIDEBARS_PATH', plugin_dir_path( __FILE__ ) );
define( 'LAA_SIDEBARS_URL',  plugin_dir_url( __FILE__ ) );

// Include sidebar functionality
require_once LAA_SIDEBARS_PATH . 'inc/sidebars-cpt.php';
require_once LAA_SIDEBARS_PATH . 'inc/nsmi-sidebar.php';

// Enqueue frontend styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'laa-sidebars',
        LAA_SIDEBARS_URL . 'assets/css/sidebars.css',
        [],
        '1.0.0'
    );
});