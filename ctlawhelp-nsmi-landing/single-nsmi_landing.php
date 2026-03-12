<?php
/**
 * Template: Single NSMI Landing Page
 * Minimal starter template for nsmi_landing CPT
 * Ensures global $post is set and Hello Elementor layout is preserved
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<main id="primary" class="site-main">
<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            <div class="entry-content">
                <?php
                // DEBUG: Show post ID and meta
                echo '<div style="background:#ffe;border:1px solid #cc0;padding:8px;margin-bottom:12px;font-size:0.95em;">';
                echo 'Debug: Post ID = ' . get_the_ID() . '<br>';
                $nsmi_issue_slug = get_post_meta(get_the_ID(), '_nsmi_issue', true);
                echo 'Meta _nsmi_issue = ' . esc_html($nsmi_issue_slug) . '<br>';
                $html_above = get_post_meta(get_the_ID(), '_nsmi_html_above', true);
                echo 'Meta _nsmi_html_above = ' . (empty($html_above) ? '<em>empty</em>' : '<em>set</em>') . '<br>';
                echo '</div>';

                // Get the selected NSMI issue (term slug) from meta
                $nsmi_term = $nsmi_issue_slug ? get_term_by('slug', $nsmi_issue_slug, 'nsmi_category') : false;
                if ($nsmi_term && !is_wp_error($nsmi_term)) {
                    // Show term description if present
                    if (!empty($nsmi_term->description)) {
                        echo '<div class="nsmi-term-description">' . wp_kses_post(wpautop($nsmi_term->description)) . '</div>';
                    } else {
                        echo '<div style="color:#c00;">No term description found for this NSMI issue.</div>';
                    }
                } else {
                    echo '<div style="color:#c00;">No valid NSMI issue term found. Please check the "Top-Level NSMI Issue" metabox.</div>';
                }

                // Render HTML above accordion (from meta)
                if (!empty($html_above)) {
                    echo '<div class="nsmi-html-above">' . apply_filters('the_content', $html_above) . '</div>';
                } else {
                    echo '<div style="color:#c00;">No HTML Above Accordion content set.</div>';
                }

                // Render accordion directly (reuse logic from laa_nsmi_accordion_sc)
                if (!function_exists('laa_nsmi_accordion_sc')) {
                    require_once __DIR__ . '/inc/shortcode-laa-nsmi-accordion.php';
                }
                if (function_exists('laa_nsmi_accordion_sc')) {
                    $accordion_html = laa_nsmi_accordion_sc([
                        'term' => $nsmi_issue_slug,
                        'include_children' => '1',
                        'show_excerpts' => '1',
                        'excerpt_words' => '24',
                    ]);
                    echo $accordion_html;
                } else {
                    echo '<div style="color:#c00;">Accordion function not found.</div>';
                }

                // Render sidebar (from CTLawHelp Sidebars plugin)
                if (!function_exists('laa_render_nsmi_sidebar')) {
                    // Try to include sidebar logic if not loaded
                    if (defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/ctlawhelp-sidebars/inc/nsmi-sidebar.php')) {
                        require_once WP_PLUGIN_DIR . '/ctlawhelp-sidebars/inc/nsmi-sidebar.php';
                    }
                }
                if (function_exists('laa_render_nsmi_sidebar')) {
                    echo '<aside class="nsmi-sidebar">';
                    laa_render_nsmi_sidebar();
                    echo '</aside>';
                } else {
                    echo '<div style="color:#c00;">Sidebar function not found.</div>';
                }
                ?>
            </div>
        </article>
        <?php
    endwhile;
endif;
?>
</main>
<?php
get_footer();
