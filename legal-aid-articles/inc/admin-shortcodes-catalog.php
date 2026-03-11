<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin: Legal Aid Articles ? Shortcodes
 * - Lists all NSMI shortcodes with description, where to use, args and defaults.
 * - Copy-to-clipboard button for a ready-to-paste usage example.
 * - Print-friendly layout (includes a Print button).
 */

add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=legal_article',
    'Shortcodes',
    'Shortcodes',
    'manage_options',
    'laa-shortcodes',
    'laa_render_shortcodes_catalog_page'
  );
});

function laa_sc_exists($tag) {
  return function_exists('shortcode_exists') ? shortcode_exists($tag) : false;
}

function laa_render_shortcodes_catalog_page() {
  // Define the catalog. Keep this in sync with the shortcodes you load.
  $catalog = array(
    array(
      'tag'   => 'laa_nsmi_sidebar',
      'title' => 'NSMI Sidebar',
      'desc'  => 'Icon + NSMI title + term description + global widgets + term-specific widgets.',
      'where' => 'Elementor left column, or PHP template <aside>.',
      'usage' => '[laa_nsmi_sidebar]',
      'args'  => array(), // none
    ),
    array(
      'tag'   => 'laa_nsmi_html_above',
      'title' => 'HTML Above Accordion',
      'desc'  => 'Renders per-page WYSIWYG block saved in _nsmi_html_above.',
      'where' => 'Elementor right column (above accordion) or PHP template.',
      'usage' => '[laa_nsmi_html_above]',
      'args'  => array(),
    ),
    array(
      'tag'   => 'laa_nsmi_featured',
      'title' => 'Featured (Pinned) Articles',
      'desc'  => 'Shows pinned legal_article posts (pins first), optionally with images and excerpts.',
      'where' => 'Right column, above accordion.',
      'usage' => '[laa_nsmi_featured limit="3" show_images="1" show_excerpts="0" heading="Featured"]',
      'args'  => array(
        'term'             => '"" (slug; empty = auto from page _nsmi_issue)',
        'include_children' => '"1"',
        'limit'            => '"3"',
        'show_images'      => '"1"',
        'image_size'       => '"medium"',
        'show_excerpts'    => '"0"',
        'excerpt_words'    => '"24"',
        'heading'          => '"Featured"',
        'class'            => '""',
      ),
    ),
    array(
      'tag'   => 'laa_nsmi_accordion',
      'title' => 'NSMI Accordion',
      'desc'  => 'Accordion of legal_article items grouped by sections; supports pins, counts, excerpts, deep links, "Show all".',
      'where' => 'Right column (Elementor) or PHP template.',
      'usage' => '[laa_nsmi_accordion include_children="1" posts_per_section="8" show_counts="1" show_excerpts="1"]',
      'args'  => array(
        'term'              => '"" (slug; empty = uses page _nsmi_issue if set)',
        'include_children'  => '"1"',
        'posts_per_section' => '"8"',
        'order'             => '"DESC"',
        'orderby'           => '"date"',
        'show_counts'       => '"1"',
        'show_excerpts'     => '"1"',
        'excerpt_words'     => '"24"',
        'class'             => '""',
        'show_all'          => '"1"',
        'show_all_text'     => '"Show all"',
      ),
    ),
    array(
      'tag'   => 'laa_nsmi_accordion_auto',
      'title' => 'NSMI Accordion (Auto-term)',
      'desc'  => 'Same as laa_nsmi_accordion but auto-resolves term from page _nsmi_issue.',
      'where' => 'Right column (Elementor).',
      'usage' => '[laa_nsmi_accordion_auto include_children="1" posts_per_section="8" show_counts="1" show_excerpts="1"]',
      'args'  => array(
        // same args as accordion, minus "term"
        'include_children'  => '"1"',
        'posts_per_section' => '"8"',
        'order'             => '"DESC"',
        'orderby'           => '"date"',
        'show_counts'       => '"1"',
        'show_excerpts'     => '"1"',
        'excerpt_words'     => '"24"',
        'class'             => '""',
        'show_all'          => '"1"',
        'show_all_text'     => '"Show all"',
      ),
    ),
    array(
      'tag'   => 'laa_nsmi_grid',
      'title' => 'NSMI Category Grid',
      'desc'  => 'Card grid of NSMI categories (icon, title, first paragraph of description). Links to landing page or term archive.',
      'where' => 'Any page (e.g., All Topics) or landing intros.',
      'usage' => '[laa_nsmi_grid columns="3" hide_empty="0"]',
      'args'  => array(
        'columns'    => '"3" (1..4)',
        'taxonomy'   => '"nsmi_category"',
        'parent'     => '"" (slug; empty = top-level terms)',
        'hide_empty' => '"0"',
        'class'      => '""',
      ),
    ),
    array(
      'tag'   => 'laa_nsmi_list',
      'title' => 'NSMI Simple List (optional)',
      'desc'  => 'Plain UL of N articles in a single term; handy for special pages.',
      'where' => 'Anywhere you need a simple list.',
      'usage' => '[laa_nsmi_list term="benefits" count="10" show_icons="1"]',
      'args'  => array(
        'term'       => '"benefits" (slug; required)',
        'count'      => '"10"',
        'show_icons' => '"1"',
        'class'      => '""',
      ),
    ),
    // Legacy aliases (only list if you still keep them around)
    array(
      'tag'        => 'nsmi_subcategories',
      'title'      => 'Legacy: NSMI Subcategories',
      'desc'       => 'Old list of child terms. Replaced by [laa_nsmi_grid].',
      'where'      => 'Legacy content only.',
      'usage'      => '[nsmi_subcategories parent="3"]',
      'args'       => array( 'parent' => '"3" (term ID)' ),
      'legacy'     => true,
    ),
    array(
      'tag'        => 'nsmi_articles',
      'title'      => 'Legacy: NSMI Articles',
      'desc'       => 'Old article list for one term. Replaced by accordion/featured.',
      'where'      => 'Legacy content only.',
      'usage'      => '[nsmi_articles term="benefits" count="10"]',
      'args'       => array( 'term' => '"benefits"', 'count' => '"10"' ),
      'legacy'     => true,
    ),
  );

  // Compute availability
  foreach ($catalog as $i => $row) {
    $catalog[$i]['available'] = laa_sc_exists($row['tag']);
  }

  // Render page
  echo '<div class="wrap">';
  echo '<h1>NSMI Shortcodes</h1>';
  echo '<p>Click Copy to copy a ready-to-use example. Use the Print button for a hard copy.</p>';

  // Styles
  echo '<style>
  .laa-sc-toolbar { margin: 10px 0 16px; display:flex; gap:8px; align-items:center; }
  .button.print { background:#2271b1; color:#fff; }
  .laa-sc-list { margin-top: 8px; }
  .laa-sc-item { background:#fff; border:1px solid #dcdcde; border-radius:6px; padding:14px; margin:0 0 12px; }
  .laa-sc-hdr { display:flex; gap:8px; align-items:center; justify-content:space-between; }
  .laa-sc-title { font-size:16px; font-weight:600; margin:0; }
  .laa-sc-badges { display:flex; gap:8px; align-items:center; }
  .badge { display:inline-block; font-size:11px; padding:2px 6px; border-radius:999px; border:1px solid #ccd0d4; background:#f6f7f7; }
  .badge.ok { border-color:#46b450; color:#1e7e34; }
  .badge.legacy { border-color:#d63638; color:#a11; }
  .laa-sc-body { margin-top:8px; }
  .laa-kv { margin:0 0 6px; }
  .laa-kv code { background:#f6f7f7; padding:2px 6px; border-radius:4px; }
  .laa-args { margin:8px 0; }
  .laa-args dt { font-weight:600; }
  .laa-args dd { margin:0 0 4px 0; }
  .laa-usage { display:flex; gap:8px; align-items:center; margin-top:10px; }
  .laa-usage code { background:#f6f7f7; padding:6px 8px; border:1px solid #dcdcde; border-radius:4px; display:inline-block; }
  .laa-copy { cursor:pointer; }
  .laa-search { width:280px; }
  @media print {
    .laa-sc-toolbar, .laa-usage button { display:none !important; }
    .laa-sc-item { page-break-inside:avoid; }
  }
  </style>';

  // Toolbar
  echo '<div class="laa-sc-toolbar">';
  echo '<input type="search" class="laa-search" placeholder="Filter by tag or title..." />';
  echo '<button type="button" class="button print" onclick="window.print()">Print</button>';
  echo '</div>';

  // List
  echo '<div class="laa-sc-list" id="laa-sc-list">';
  foreach ($catalog as $row) {
    $tag   = $row['tag'];
    $avail = !empty($row['available']);
    $legacy= !empty($row['legacy']);

    echo '<section class="laa-sc-item" data-tag="' . esc_attr($tag) . '" data-title="' . esc_attr($row['title']) . '">';
    echo '  <div class="laa-sc-hdr">';
    echo '    <h2 class="laa-sc-title">' . esc_html($row['title']) . ' <small style="font-weight:400;color:#555;">[' . esc_html($tag) . ']</small></h2>';
    echo '    <div class="laa-sc-badges">';
    echo $avail ? '<span class="badge ok">Available</span>' : '<span class="badge">Not loaded</span>';
    if ($legacy) echo '<span class="badge legacy">Legacy</span>';
    echo '    </div>';
    echo '  </div>';

    echo '  <div class="laa-sc-body">';
    echo '    <p class="laa-kv"><strong>Description:</strong> ' . esc_html($row['desc']) . '</p>';
    echo '    <p class="laa-kv"><strong>Where:</strong> ' . esc_html($row['where']) . '</p>';

    if (!empty($row['args'])) {
      echo '    <div class="laa-args"><strong>Arguments (defaults):</strong><dl>';
      foreach ($row['args'] as $arg => $def) {
        echo '      <dt><code>' . esc_html($arg) . '</code></dt>';
        echo '      <dd>Default: ' . esc_html($def) . '</dd>';
      }
      echo '    </dl></div>';
    } else {
      echo '    <p class="laa-kv"><strong>Arguments:</strong> none</p>';
    }

    $usage = isset($row['usage']) ? $row['usage'] : '[' . $tag . ']';
    echo '    <div class="laa-usage">';
    echo '      <code class="laa-usage-code">' . esc_html($usage) . '</code>';
    echo '      <button type="button" class="button laa-copy" data-copy="' . esc_attr($usage) . '">Copy</button>';
    echo '    </div>';

    echo '  </div>';
    echo '</section>';
  }
  echo '</div>'; // .laa-sc-list

  // Script (copy + filter)
  echo '<script>
  (function(){
    var list = document.getElementById("laa-sc-list");
    var search = document.querySelector(".laa-search");

    list.addEventListener("click", function(e){
      var btn = e.target.closest(".laa-copy");
      if(!btn) return;
      var text = btn.getAttribute("data-copy") || "";
      if(!text) {
        var code = btn.parentNode && btn.parentNode.querySelector(".laa-usage-code");
        if(code) text = code.textContent || "";
      }
      if(!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function(){ btn.textContent = "Copied"; setTimeout(function(){ btn.textContent = "Copy"; }, 1200); });
      } else {
        var ta = document.createElement("textarea");
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand("copy"); btn.textContent = "Copied"; setTimeout(function(){ btn.textContent = "Copy"; }, 1200); } catch(e){}
        document.body.removeChild(ta);
      }
    });

    if (search) {
      search.addEventListener("input", function(){
        var q = (this.value || "").toLowerCase();
        var items = list.querySelectorAll(".laa-sc-item");
        for (var i=0;i<items.length;i++){
          var it = items[i];
          var tag = (it.getAttribute("data-tag")||"").toLowerCase();
          var title = (it.getAttribute("data-title")||"").toLowerCase();
          var show = !q || tag.indexOf(q) !== -1 || title.indexOf(q) !== -1;
          it.style.display = show ? "" : "none";
        }
      });
    }
  })();
  </script>';

  echo '</div>'; // .wrap
}
