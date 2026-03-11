<?php
if (!defined('ABSPATH')) exit;

/**
 * Small JS: open an accordion section from the URL hash (plain slug),
 * keep the hash in sync on click, and support old #nsmi-acc-* hashes.
 */
add_action('wp_enqueue_scripts', function () {
  $handle = 'laa-nsmi-js';

  // Register an empty script handle so we can attach inline JS.
  if ( ! wp_script_is($handle, 'registered') ) {
    wp_register_script($handle, '', array(), null, true);
  }
  wp_enqueue_script($handle);

  // Use NOWDOC to avoid interpolation; the closing label must be on its own line with no spaces.
  $js = <<<'LAANSCRIPT'
(function(){
  function openFromHash(){
    var h = decodeURIComponent((location.hash || "")).replace("#", "");
    if (!h) { return; }

    // New format: id is the plain term slug (e.g., "homes-apartments")
    var el = document.getElementById(h);

    // Back-compat: old format used "nsmi-acc-<slug>"
    if (!el && h.indexOf("nsmi-acc-") === 0) {
      el = document.getElementById(h.substring(9));
    }

    if (el && el.tagName && el.tagName.toLowerCase() === "details") {
      el.setAttribute("open", "open");
      var s = el.querySelector("summary");
      if (s) { try { s.focus(); } catch (e) {} }
    }
  }

  window.addEventListener("hashchange", openFromHash);

  document.addEventListener("DOMContentLoaded", function(){
    openFromHash();

    var items = document.querySelectorAll(".laa-nsmi-accordion details");
    for (var i = 0; i < items.length; i++) {
      (function(d){
        var s = d.querySelector("summary");
        if (!s) { return; }
        s.addEventListener("click", function(){
          try { history.replaceState(null, "", "#" + d.id); } catch (e) {}
        });
      })(items[i]);
    }
  });
})();
LAANSCRIPT;

  wp_add_inline_script($handle, $js);
});
