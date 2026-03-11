<?php
if (!defined('ABSPATH')) exit;

/**
 * Legal Aid Articles � Diagnostics
 * Admin page under Legal Aid Articles that reports active features
 * so you can safely decide which files to keep/remove.
 */

add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=legal_article',
    'LAA Diagnostics',
    'Diagnostics',
    'manage_options',
    'laa-diagnostics',
    'laa_render_diagnostics_page'
  );
});

function laa_diag_bool($b) {
  return $b
    ? '<span style="color:#0a0;font-weight:600;">Yes</span>'
    : '<span style="color:#a00;font-weight:600;">No</span>';
}

function laa_diag_count($n) {
  return '<span style="font-weight:600;">' . intval($n) . '</span>';
}

function laa_starts_with($haystack, $needle) {
  return substr($haystack, 0, strlen($needle)) === $needle;
}

function laa_export_diagnostics() {
  global $wp_registered_sidebars, $shortcode_tags, $wpdb;

  // Set headers for file download
  header('Content-Type: text/plain');
  header('Content-Disposition: attachment; filename="laa-diagnostics-' . date('Y-m-d-H-i-s') . '.txt"');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');

  // Generate report content
  $report = "LEGAL AID ARTICLES - DIAGNOSTICS REPORT\n";
  $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
  $report .= "WordPress Version: " . get_bloginfo('version') . "\n";
  $report .= "Site URL: " . get_site_url() . "\n\n";

  $report .= "========================================\n";
  $report .= "PLUGIN FEATURES STATUS\n";
  $report .= "========================================\n\n";

  // Core objects
  $has_cpt = post_type_exists('legal_article');
  $has_tax = taxonomy_exists('nsmi_category');

  $report .= "Custom Post Type 'legal_article': " . ($has_cpt ? "ACTIVE" : "INACTIVE") . "\n";
  $report .= "Taxonomy 'nsmi_category': " . ($has_tax ? "ACTIVE" : "INACTIVE") . "\n\n";

  // Shortcodes
  $has_acc_sc = shortcode_exists('laa_nsmi_accordion');
  $report .= "Accordion shortcode [laa_nsmi_accordion]: " . ($has_acc_sc ? "ACTIVE" : "INACTIVE") . "\n\n";

  // Sidebars
  $has_global_sb = isset($wp_registered_sidebars['nsmi-landing-sidebar']);
  $dynamic_count = 0;
  foreach ((array)$wp_registered_sidebars as $id => $sb) {
    if (laa_starts_with($id, 'nsmi-landing-sidebar-')) { $dynamic_count++; }
  }

  $report .= "Global sidebar 'nsmi-landing-sidebar': " . ($has_global_sb ? "ACTIVE" : "INACTIVE") . "\n";
  $report .= "Per-category sidebars: " . $dynamic_count . " registered\n\n";

  // Helper function for per-category sidebars
  $has_sidebar_helper = function_exists('laa_nsmi_render_sidebar_for_page');
  $report .= "Sidebar render helper function: " . ($has_sidebar_helper ? "ACTIVE" : "INACTIVE") . "\n\n";

  // Styles
  $has_styles = wp_style_is('laa-nsmi', 'registered') || wp_style_is('laa-nsmi', 'enqueued');
  $report .= "Landing styles handle 'laa-nsmi': " . ($has_styles ? "ACTIVE" : "INACTIVE") . "\n\n";

  // Meta keys present in DB
  $has_issue_meta = (bool) $wpdb->get_var( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_nsmi_issue' LIMIT 1" );
  $has_pin_meta   = (bool) $wpdb->get_var( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_laa_pin' LIMIT 1" );

  $report .= "Page meta '_nsmi_issue' in database: " . ($has_issue_meta ? "PRESENT" : "NOT FOUND") . "\n";
  $report .= "Article pin meta '_laa_pin' in database: " . ($has_pin_meta ? "PRESENT" : "NOT FOUND") . "\n\n";

  // Content counts
  $article_count = wp_count_posts('legal_article');
  $term_count = wp_count_terms('nsmi_category', ['hide_empty' => false]);

  $report .= "========================================\n";
  $report .= "CONTENT COUNTS\n";
  $report .= "========================================\n\n";

  $report .= "Legal Articles:\n";
  $report .= "  Published: " . ($article_count->publish ?? 0) . "\n";
  $report .= "  Draft: " . ($article_count->draft ?? 0) . "\n";
  $report .= "  Total: " . array_sum((array)$article_count) . "\n\n";

  $report .= "NSMI Categories: " . $term_count . " terms\n\n";

  // Recommendations
  $report .= "========================================\n";
  $report .= "RECOMMENDATIONS\n";
  $report .= "========================================\n\n";

  if ($dynamic_count > 0 || $has_sidebar_helper) {
    $report .= "- You appear to be using per-category sidebars. Keep inc/nsmi-dynamic-sidebars.php\n";
    $report .= "- Remove inc/nsmi-sidebar.php to avoid duplicate sidebars\n";
  } else {
    $report .= "- You are not using per-category sidebars. Keep inc/nsmi-sidebar.php for a single global area\n";
    $report .= "- Remove inc/nsmi-dynamic-sidebars.php\n";
  }

  if ($has_acc_sc) {
    $report .= "- The accordion shortcode is active. Keep inc/shortcode-laa-nsmi-accordion.php\n";
  }

  if ($has_styles) {
    $report .= "- Landing styles are active. Keep inc/nsmi-landing-styles.php\n";
  }

  $report .= "- Always keep inc/cpt.php and inc/taxonomy-nsmi.php\n";
  $report .= "- inc/nsmi-landing.php (old all-in-one) should remain removed unless you know you still need parts of it\n";
  $report .= "- inc/shortcodes.php, inc/admin.php, inc/admin-metabox.php, and inc/taxonomy-nsmi-icons.php are optional depending on your usage\n";

  echo $report;
  exit;
}

function laa_export_articles() {
  global $wpdb;

  // Set headers for file download
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="laa-articles-' . date('Y-m-d-H-i-s') . '.csv"');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');

  // Open output stream
  $output = fopen('php://output', 'w');

  // CSV headers
  fputcsv($output, [
    'ID',
    'Title',
    'Status',
    'Author',
    'Date Created',
    'Last Modified',
    'NSMI Categories',
    'NSMI Categories (IDs)',
    'Excerpt',
    'Content Length',
    'Has Featured Image',
    'Permalink'
  ]);

  // Get all legal articles
  $articles = get_posts([
    'post_type' => 'legal_article',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'ID',
    'order' => 'ASC'
  ]);

  foreach ($articles as $article) {
    // Get NSMI categories
    $categories = wp_get_post_terms($article->ID, 'nsmi_category', ['fields' => 'names']);
    $category_ids = wp_get_post_terms($article->ID, 'nsmi_category', ['fields' => 'ids']);

    // Get author info
    $author = get_userdata($article->post_author);
    $author_name = $author ? $author->display_name : 'Unknown';

    // Check for featured image
    $has_featured = has_post_thumbnail($article->ID) ? 'Yes' : 'No';

    // Content length
    $content_length = strlen(strip_tags($article->post_content));

    // Prepare row data
    $row = [
      $article->ID,
      $article->post_title,
      $article->post_status,
      $author_name,
      $article->post_date,
      $article->post_modified,
      implode('; ', $categories),
      implode('; ', $category_ids),
      wp_strip_all_tags($article->post_excerpt),
      $content_length,
      $has_featured,
      get_permalink($article->ID)
    ];

    fputcsv($output, $row);
  }

  fclose($output);
  exit;
}

function laa_export_taxonomy() {
  // Set headers for file download
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="laa-taxonomy-' . date('Y-m-d-H-i-s') . '.csv"');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');

  // Open output stream
  $output = fopen('php://output', 'w');

  // CSV headers
  fputcsv($output, [
    'Term ID',
    'Name',
    'Slug',
    'Parent ID',
    'Parent Name',
    'Level',
    'Language',
    'Translation Group',
    'Article Count',
    'Description'
  ]);

  // Get all NSMI terms
  $terms = get_terms([
    'taxonomy' => 'nsmi_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
  ]);

  if (!is_wp_error($terms)) {
    foreach ($terms as $term) {
      // Get parent info
      $parent_name = '';
      if ($term->parent) {
        $parent_term = get_term($term->parent, 'nsmi_category');
        $parent_name = $parent_term ? $parent_term->name : '';
      }

      // Determine level (0 = top level, 1 = child)
      $level = $term->parent ? 1 : 0;

      // Get language if Polylang is active
      $language = '';
      if (function_exists('pll_get_term_language')) {
        $language = pll_get_term_language($term->term_id) ?: '';
      }

      // Get translation group
      $translation_group = '';
      if (function_exists('pll_get_term_translations')) {
        $translations = pll_get_term_translations($term->term_id);
        if (!empty($translations)) {
          $translation_group = implode('; ', array_keys($translations));
        }
      }

      // Get article count for this term
      $article_count = $term->count;

      // Prepare row data
      $row = [
        $term->term_id,
        $term->name,
        $term->slug,
        $term->parent,
        $parent_name,
        $level,
        $language,
        $translation_group,
        $article_count,
        $term->description
      ];

      fputcsv($output, $row);
    }
  }

  fclose($output);
  exit;
}

function laa_check_translations() {
  // Increase memory limit for this operation (conservative increase)
  if (!defined('DISABLE_MEMORY_INCREASE') || !DISABLE_MEMORY_INCREASE) {
    $current_limit = ini_get('memory_limit');
    if ($current_limit && wp_convert_hr_to_bytes($current_limit) < 134217728) { // 128M
      ini_set('memory_limit', '128M');
    }
  }

  if (!function_exists('pll_get_term_translations') || !function_exists('pll_get_term_language')) {
    echo '<div class="notice notice-error"><p>Polylang is not active or not properly configured.</p></div>';
    return;
  }

  // Get all NSMI terms (with pagination to avoid memory issues)
  $terms = get_terms([
    'taxonomy' => 'nsmi_category',
    'hide_empty' => false,
    'lang' => '',
    'number' => 500, // Limit to prevent memory exhaustion
    'orderby' => 'name',
    'order' => 'ASC'
  ]);

  if (is_wp_error($terms)) {
    echo '<div class="notice notice-error"><p>Error getting terms: ' . $terms->get_error_message() . '</p></div>';
    return;
  }

  $issues = [];
  $stats = [
    'total_terms' => count($terms),
    'en_terms' => 0,
    'es_terms' => 0,
    'untranslated_terms' => 0,
    'orphaned_translations' => 0,
  ];

  foreach ($terms as $term) {
    $lang = pll_get_term_language($term->term_id);
    $translations = pll_get_term_translations($term->term_id);

    if ($lang === 'en') {
      $stats['en_terms']++;
    } elseif ($lang === 'es') {
      $stats['es_terms']++;
    }

    if (empty($translations) || count($translations) < 2) {
      $stats['untranslated_terms']++;
      $issues[] = [
        'type' => 'no_translation',
        'term' => $term,
        'lang' => $lang,
        'translations' => $translations
      ];
    } elseif (count($translations) > 2) {
      $issues[] = [
        'type' => 'too_many_translations',
        'term' => $term,
        'lang' => $lang,
        'translations' => $translations
      ];
    }

    // Check for orphaned translations (terms that reference non-existent terms)
    foreach ($translations as $trans_lang => $trans_id) {
      if (!get_term($trans_id, 'nsmi_category')) {
        $stats['orphaned_translations']++;
        $issues[] = [
          'type' => 'orphaned_translation',
          'term' => $term,
          'lang' => $lang,
          'broken_lang' => $trans_lang,
          'broken_id' => $trans_id
        ];
      }
    }
  }

  // Display results
  echo '<div class="wrap">';
  echo '<h2>Translation Check Results</h2>';

  echo '<div class="notice notice-info">';
  echo '<h3>Statistics (Sample of ' . count($terms) . ' terms)</h3>';
  echo '<ul>';
  echo '<li><strong>Analyzed terms:</strong> ' . $stats['total_terms'] . '</li>';
  echo '<li><strong>English terms:</strong> ' . $stats['en_terms'] . '</li>';
  echo '<li><strong>Spanish terms:</strong> ' . $stats['es_terms'] . '</li>';
  echo '<li><strong>Untranslated terms:</strong> ' . $stats['untranslated_terms'] . '</li>';
  echo '<li><strong>Orphaned translations:</strong> ' . $stats['orphaned_translations'] . '</li>';
  echo '</ul>';
  echo '<p><small>Note: This analysis is limited to the first 500 terms to prevent memory issues.</small></p>';
  echo '</div>';

  if (!empty($issues)) {
    echo '<div class="notice notice-warning">';
    echo '<h3>Issues Found (' . count($issues) . ')</h3>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>Type</th><th>Term</th><th>Language</th><th>Details</th></tr></thead><tbody>';

    foreach ($issues as $issue) {
      $term = $issue['term'];
      echo '<tr>';
      echo '<td>' . esc_html($issue['type']) . '</td>';
      echo '<td>' . esc_html($term->name) . ' (ID: ' . $term->term_id . ')</td>';
      echo '<td>' . esc_html($issue['lang']) . '</td>';
      echo '<td>';

      switch ($issue['type']) {
        case 'no_translation':
          echo 'No translations found';
          break;
        case 'too_many_translations':
          echo 'Too many translations: ' . json_encode($issue['translations']);
          break;
        case 'orphaned_translation':
          echo 'Broken translation to ' . $issue['broken_lang'] . ' (ID: ' . $issue['broken_id'] . ')';
          break;
      }

      echo '</td>';
      echo '</tr>';
    }

    echo '</tbody></table>';

    // Add repair form for untranslated terms
    $has_untranslated = array_filter($issues, function($issue) { return $issue['type'] === 'no_translation'; });
    if (!empty($has_untranslated)) {
      echo '<form method="post" style="margin-top: 15px;">';
      wp_nonce_field('repair_translations', 'repair_translations_nonce');
      echo '<input type="hidden" name="repair_translations" value="1">';
      echo '<p><input type="submit" value="Attempt to Repair Translation Relationships" class="button button-primary" onclick="return confirm(\'This will attempt to automatically link terms with the same slug but different languages. Continue?\')"></p>';
      echo '<p><small>This will try to match terms with identical slugs that have different language assignments and create translation relationships between them.</small></p>';
      echo '</form>';
    }

    echo '</div>';
  } else {
    echo '<div class="notice notice-success">';
    echo '<p><strong>All translations look good!</strong> No issues found.</p>';
    echo '</div>';
  }

  echo '<p><a href="' . admin_url('edit.php?post_type=legal_article&page=laa-diagnostics') . '" class="button">Back to Diagnostics</a></p>';
  echo '</div>';
  exit;
}

function laa_repair_translations() {
  if (!function_exists('pll_save_term_translations') || !function_exists('pll_get_term_language')) {
    echo '<div class="notice notice-error"><p>Polylang functions not available for repairing translations.</p></div>';
    return;
  }

  if (!isset($_POST['repair_translations_nonce']) || !wp_verify_nonce($_POST['repair_translations_nonce'], 'repair_translations')) {
    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
    return;
  }

  // Get all NSMI terms
  $terms = get_terms([
    'taxonomy' => 'nsmi_category',
    'hide_empty' => false,
    'lang' => '',
    'number' => 0,
  ]);

  if (is_wp_error($terms)) {
    echo '<div class="notice notice-error"><p>Error getting terms: ' . $terms->get_error_message() . '</p></div>';
    return;
  }

  $repaired = 0;
  $errors = [];

  // Group terms by slug to find potential translation pairs
  $terms_by_slug = [];
  foreach ($terms as $term) {
    $terms_by_slug[$term->slug][] = $term;
  }

  foreach ($terms_by_slug as $slug => $term_group) {
    if (count($term_group) === 2) {
      // Potential translation pair
      $term1 = $term_group[0];
      $term2 = $term_group[1];

      $lang1 = pll_get_term_language($term1->term_id);
      $lang2 = pll_get_term_language($term2->term_id);

      // If they have different languages and no existing translations, link them
      if ($lang1 && $lang2 && $lang1 !== $lang2) {
        $translations1 = pll_get_term_translations($term1->term_id);
        $translations2 = pll_get_term_translations($term2->term_id);

        if (empty($translations1) && empty($translations2)) {
          // Create translation relationship
          $new_translations = [
            $lang1 => $term1->term_id,
            $lang2 => $term2->term_id
          ];

          pll_save_term_translations($new_translations);
          $repaired++;
        }
      }
    }
  }

  echo '<div class="wrap">';
  echo '<h2>Translation Repair Results</h2>';

  if ($repaired > 0) {
    echo '<div class="notice notice-success">';
    echo '<p><strong>Success!</strong> Repaired translation relationships for ' . $repaired . ' term pairs.</p>';
    echo '<p>You may need to refresh the taxonomy admin page to see the changes.</p>';
    echo '</div>';
  } else {
    echo '<div class="notice notice-info">';
    echo '<p>No translation relationships were repaired. This could mean:</p>';
    echo '<ul>';
    echo '<li>All terms already have proper translation relationships</li>';
    echo '<li>Terms with the same slug have incompatible language assignments</li>';
    echo '<li>Some terms may need manual translation setup</li>';
    echo '</ul>';
    echo '</div>';
  }

  if (!empty($errors)) {
    echo '<div class="notice notice-warning">';
    echo '<h3>Errors</h3>';
    echo '<ul>';
    foreach ($errors as $error) {
      echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
  }

  echo '<p><a href="' . admin_url('edit.php?post_type=legal_article&page=laa-diagnostics') . '" class="button">Back to Diagnostics</a></p>';
  echo '</div>';
  exit;
}

function laa_render_diagnostics_page() {
  global $wp_registered_sidebars, $shortcode_tags, $wpdb;

  // Handle export request
  if (isset($_GET['export']) && $_GET['export'] === 'diagnostics') {
    laa_export_diagnostics();
    return;
  }

  if (isset($_GET['export']) && $_GET['export'] === 'articles') {
    laa_export_articles();
    return;
  }

  if (isset($_GET['export']) && $_GET['export'] === 'taxonomy') {
    laa_export_taxonomy();
    return;
  }

  if (isset($_GET['check']) && $_GET['check'] === 'translations') {
    laa_check_translations();
    return;
  }

  if (isset($_POST['repair_translations']) && $_POST['repair_translations'] === '1') {
    laa_repair_translations();
    return;
  }

  // Core objects
  $has_cpt        = post_type_exists('legal_article');
  $has_tax        = taxonomy_exists('nsmi_category');

  // Shortcodes
  $has_acc_sc     = shortcode_exists('laa_nsmi_accordion');

  // Sidebars
  $has_global_sb  = isset($wp_registered_sidebars['nsmi-landing-sidebar']);
  $dynamic_count  = 0;
  foreach ((array)$wp_registered_sidebars as $id => $sb) {
    if (laa_starts_with($id, 'nsmi-landing-sidebar-')) { $dynamic_count++; }
  }

  // Helper function for per-category sidebars
  $has_sidebar_helper = function_exists('laa_nsmi_render_sidebar_for_page');

  // Styles
  $has_styles = wp_style_is('laa-nsmi', 'registered') || wp_style_is('laa-nsmi', 'enqueued');

  // Meta keys present in DB (any row)
  $has_issue_meta = (bool) $wpdb->get_var( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_nsmi_issue' LIMIT 1" );
  $has_pin_meta   = (bool) $wpdb->get_var( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_laa_pin' LIMIT 1" );

  // Build table
  echo '<div class="wrap"><h1>LAA Diagnostics</h1>';
  echo '<p>This page reports what is currently registered/active so you can clean up includes safely.</p>';

  // Memory warning
  echo '<div class="notice notice-warning">';
  echo '<p><strong>Memory Usage Notice:</strong> If you experience memory exhaustion errors, you can disable various features by adding these lines to your wp-config.php:</p>';
  echo '<code>define( \'DISABLE_NSMI_DEBUG\', true );</code><br>';
  echo '<code>define( \'DISABLE_MEMORY_INCREASE\', true );</code><br>';
  echo '<code>define( \'DISABLE_FEED_FETCHING\', true );</code>';
  echo '<p>This will reduce memory usage by disabling debug logging, memory increases, and RSS feed fetching.</p>';
  echo '</div>';

  // Export buttons
  echo '<div style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">';
  echo '<h3 style="margin-top: 0;">Export Options</h3>';
  echo '<p>';
  echo '<a href="' . esc_url(add_query_arg('export', 'diagnostics')) . '" class="button button-primary">Export Diagnostics Report</a> ';
  echo '<a href="' . esc_url(add_query_arg('export', 'articles')) . '" class="button button-secondary">Export Articles Data (CSV)</a> ';
  echo '<a href="' . esc_url(add_query_arg('export', 'taxonomy')) . '" class="button button-secondary">Export Taxonomy Terms (CSV)</a>';
  echo '</p>';
  echo '<p><small>Export diagnostics as a text file for troubleshooting, or export all articles and taxonomy data as CSV files for backup and analysis.</small></p>';
  echo '</div>';

  // Translation check
  echo '<div style="margin: 1rem 0; padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
  echo '<h3 style="margin-top: 0;">Translation Diagnostics</h3>';
  echo '<p>';
  echo '<a href="' . esc_url(add_query_arg('check', 'translations')) . '" class="button button-secondary">Check Translation Relationships</a>';
  echo '</p>';
  echo '<p><small>Analyze NSMI taxonomy terms for translation issues that might prevent Spanish terms from displaying properly.</small></p>';
  echo '</div>';

  echo '<table class="widefat striped" style="max-width:1000px;margin-top:1rem;">';
  echo '<thead><tr><th>Feature</th><th>Status</th><th>Notes</th></tr></thead><tbody>';

  echo '<tr><td>Custom Post Type: <code>legal_article</code></td><td>' . laa_diag_bool($has_cpt) . '</td><td>Provided by <code>inc/cpt.php</code>.</td></tr>';
  echo '<tr><td>Taxonomy: <code>nsmi_category</code></td><td>' . laa_diag_bool($has_tax) . '</td><td>Provided by <code>inc/taxonomy-nsmi.php</code>.</td></tr>';

  echo '<tr><td>Accordion shortcode: <code>[laa_nsmi_accordion]</code></td><td>' . laa_diag_bool($has_acc_sc) . '</td><td>Provided by <code>inc/shortcode-laa-nsmi-accordion.php</code>.</td></tr>';

  echo '<tr><td>Global sidebar: <code>nsmi-landing-sidebar</code></td><td>' . laa_diag_bool($has_global_sb) . '</td><td>Provided by <code>inc/nsmi-sidebar.php</code> or <code>inc/nsmi-dynamic-sidebars.php</code>.</td></tr>';

  echo '<tr><td>Per-category sidebars</td><td>' . laa_diag_bool($dynamic_count > 0) . ' (' . laa_diag_count($dynamic_count) . ')</td><td>Provided by <code>inc/nsmi-dynamic-sidebars.php</code>.</td></tr>';

  echo '<tr><td>Sidebar render helper: <code>laa_nsmi_render_sidebar_for_page()</code></td><td>' . laa_diag_bool($has_sidebar_helper) . '</td><td>Used by your template to pick the right sidebar.</td></tr>';

  echo '<tr><td>Landing styles handle: <code>laa-nsmi</code></td><td>' . laa_diag_bool($has_styles) . '</td><td>Inline CSS from <code>inc/nsmi-landing-styles.php</code>.</td></tr>';

  echo '<tr><td>Page meta in DB: <code>_nsmi_issue</code></td><td>' . laa_diag_bool($has_issue_meta) . '</td><td>Saved by <code>inc/nsmi-page-term-metabox.php</code>.</td></tr>';
  echo '<tr><td>Article pin meta in DB: <code>_laa_pin</code></td><td>' . laa_diag_bool($has_pin_meta) . '</td><td>Saved by <code>inc/nsmi-article-pin-metabox.php</code>.</td></tr>';

  echo '</tbody></table>';

  // Simple guidance based on what we detect
  echo '<h2 style="margin-top:1.5rem;">Recommendations</h2><ul>';
  if ($dynamic_count > 0 || $has_sidebar_helper) {
    echo '<li>You appear to be using per-category sidebars. Keep <code>inc/nsmi-dynamic-sidebars.php</code>. Remove <code>inc/nsmi-sidebar.php</code> to avoid duplicate sidebars.</li>';
  } else {
    echo '<li>You are not using per-category sidebars. Keep <code>inc/nsmi-sidebar.php</code> for a single global area. Remove <code>inc/nsmi-dynamic-sidebars.php</code>.</li>';
  }
  if ($has_acc_sc) {
    echo '<li>The accordion shortcode is active. Keep <code>inc/shortcode-laa-nsmi-accordion.php</code>.</li>';
  }
  if ($has_styles) {
    echo '<li>Landing styles are active. Keep <code>inc/nsmi-landing-styles.php</code>.</li>';
  }
  echo '<li>Always keep <code>inc/cpt.php</code> and <code>inc/taxonomy-nsmi.php</code>.</li>';
  echo '<li><code>inc/nsmi-landing.php</code> (old all-in-one) should remain removed unless you know you still need parts of it.</li>';
  echo '<li><code>inc/shortcodes.php</code>, <code>inc/admin.php</code>, <code>inc/admin-metabox.php</code>, and <code>inc/taxonomy-nsmi-icons.php</code> are optional depending on your usage.</li>';
  echo '</ul>';

  echo '</div>';
}
