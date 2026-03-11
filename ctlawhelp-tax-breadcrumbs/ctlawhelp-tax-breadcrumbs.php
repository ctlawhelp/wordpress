<?php
/**
 * Plugin Name: CTLawHelp Tax Bread
 * Description: Minimal NSMI taxonomy and breadcrumb functionality with Elementor protection.
 * Version: 1.4
 * Author: CTLawHelp
 * Requires Plugins: ctlawhelp-legal-aid-articles
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LATB_PATH', plugin_dir_path( __FILE__ ) );
define( 'LATB_URL',  plugin_dir_url( __FILE__ ) );

// Check for required plugin dependency
function latb_check_dependency() {
    if (!is_plugin_active('ctlawhelp-legal-aid-articles/ctlawhelp-legal-aid-articles.php')) {
        add_action('admin_notices', 'latb_dependency_notice');
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}
add_action('admin_init', 'latb_check_dependency');

function latb_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>Legal Aid Tax Bread</strong> requires the <strong>Legal Aid Articles</strong> plugin to be installed and activated. Please install and activate Legal Aid Articles first.</p>
    </div>
    <?php
}

// Only load functionality if dependency is met
if (latb_check_dependency()) {
    // Core includes only
    require_once LATB_PATH . 'inc/admin-metabox.php';
    require_once LATB_PATH . 'inc/breadcrumbs.php';
    require_once LATB_PATH . 'inc/nsmi-page-term-metabox.php';
    
    // Load NSMI protection system
    require_once LATB_PATH . 'inc/nsmi-protection.php';
    
    // Sync primary NSMI category when Polylang creates translations
    require_once LATB_PATH . 'inc/polylang-sync.php';
}


// Optional: Check if taxonomy is registered (for debugging)
add_action( 'init', function() {
    if ( taxonomy_exists('nsmi_category') ) {
        // Taxonomy is ready
    } else {
        // NSMI taxonomy not registered - this is handled by the main plugin
    }
}, 20 );
