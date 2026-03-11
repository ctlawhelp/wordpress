<?php
/**
 * Plugin Name: CTLawHelp NSMI Landing
 * Description: NSMI landing page functionality for legal articles. Requires Legal Aid Articles and Legal Aid Sidebars.
 * Version: 1.0.0
 * Author: CTLawHelp
 * Requires Plugins: ctlawhelp-legal-aid-articles, ctlawhelp-sidebars
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check if required plugins are active
function latb_nsmi_landing_check_dependencies() {
  $dependencies = [
    'ctlawhelp-legal-aid-articles/ctlawhelp-legal-aid-articles.php' => 'Legal Aid Articles',
    'ctlawhelp-sidebars/ctlawhelp-sidebars.php' => 'Legal Aid Sidebars'
];

    $missing = [];
    foreach ($dependencies as $plugin => $name) {
        if ( ! is_plugin_active( $plugin ) ) {
            $missing[] = $name;
        }
    }

    if ( ! empty( $missing ) ) {
        add_action( 'admin_notices', function() use ( $missing ) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Legal Aid NSMI Landing:</strong> This plugin requires the following plugins to be active:</p>';
            echo '<ul>';
            foreach ( $missing as $plugin_name ) {
                echo '<li>' . esc_html( $plugin_name ) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } );
        return false;
    }
    return true;
}

// Only proceed if dependencies are met
if ( ! latb_nsmi_landing_check_dependencies() ) {
    return;
}

define( 'LAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'LAL_URL',  plugin_dir_url( __FILE__ ) );

// Include NSMI landing page functionality
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-article-pin-metabox.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-elementor-bridge.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-grid-styles.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-landing-scripts.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-landing-styles.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-landing.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/nsmi-page-html-above.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/shortcode-laa-nsmi-accordion.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/shortcode-laa-nsmi-featured.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/shortcode-laa-nsmi-grid.php';

// Enqueue NSMI landing styles
function lal_enqueue_assets() {
    wp_enqueue_style( 'nsmi-grid', LAL_URL . 'assets/css/nsmi-grid.css' );
    wp_enqueue_style( 'nsmi-landing', LAL_URL . 'assets/css/nsmi-landing.css' );
}
add_action( 'wp_enqueue_scripts', 'lal_enqueue_assets' );