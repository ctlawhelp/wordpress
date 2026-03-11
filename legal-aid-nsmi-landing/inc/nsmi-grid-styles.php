<?php
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  // Adjust versioning as you like for cache-busting
  wp_enqueue_style(
    'laa-nsmi-grid',
    LAA_URL . 'assets/css/nsmi-grid.css',
    array(),
    '1.0'
  );
});