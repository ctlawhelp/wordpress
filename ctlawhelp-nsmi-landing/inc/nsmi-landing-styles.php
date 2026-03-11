<?php
if (!defined('ABSPATH')) exit;

/**
 * Enqueue NSMI landing CSS (assets/css/nsmi-landing.css)
 */
add_action('wp_enqueue_scripts', function () {
  $css_path = plugin_dir_path( dirname(__FILE__) ) . 'assets/css/nsmi-landing.css';
  $css_url  = plugin_dir_url( dirname(__FILE__) ) . 'assets/css/nsmi-landing.css';

  if ( file_exists( $css_path ) ) {
    wp_enqueue_style( 'laa-nsmi', $css_url, array(), filemtime( $css_path ) );
  }
});
