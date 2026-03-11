<?php
/**
 * Plugin Name: CTLawHelp Snippets
 * Description: Reusable content snippets for legal aid websites. Create once, use everywhere.
 * Version: 1.0
 * Author: CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
if ( ! defined( 'LAS_PATH' ) ) {
	define( 'LAS_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'LAS_URL' ) ) {
	define( 'LAS_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'LAS_VERSION' ) ) {
	define( 'LAS_VERSION', '1.0' );
}

// -----------------------------------------------------------------------------
// Main Plugin Class
// -----------------------------------------------------------------------------
class LegalAidSnippets {
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}
	
	public function init() {
		$this->load_includes();
		$this->register_post_type();
		$this->register_shortcodes();
		$this->enqueue_assets();
	}
	
	private function enqueue_assets() {
		// Use a higher priority so snippet styles load after most
		// theme and plugin styles, matching how Customizer CSS is
		// typically applied later in the cascade.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ), 100 );
	}
	
	public function enqueue_frontend_styles() {
		wp_enqueue_style(
			'legal-aid-snippets',
			LAS_URL . 'assets/css/snippets.css',
			array(),
			LAS_VERSION
		);
	}
	
	private function load_includes() {
		$files = array(
			LAS_PATH . 'inc/cpt.php',
			LAS_PATH . 'inc/shortcodes.php',
			LAS_PATH . 'inc/admin-ui.php'
		);
		
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			} else {
				error_log( 'Legal Aid Snippets: File not found: ' . $file );
				wp_die( 'Legal Aid Snippets: Required file not found: ' . $file );
			}
		}
	}
	
	private function register_post_type() {
		las_register_snippet_post_type();
	}
	
	private function register_shortcodes() {
		las_register_shortcodes();
	}
	
	public function activate() {
		$this->load_includes();
		$this->register_post_type();
		flush_rewrite_rules();
	}
	
	public function deactivate() {
		flush_rewrite_rules();
	}
}

// Initialize the plugin (only once)
if ( ! isset( $GLOBALS['legal_aid_snippets_instance'] ) ) {
	$GLOBALS['legal_aid_snippets_instance'] = new LegalAidSnippets();
}