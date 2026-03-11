<?php
/**
 * Yoast breadcrumbs customization for legal_article:
 * - Rename archive crumb to "Self-Help".
 * - Insert ONLY primary nsmi_category lineage (mode configurable).
 * - Hide the final article title crumb (keep previous category linked).
 *   (Done via wpseo_breadcrumb_single_link filter + trailing separator cleanup.)
 */

// Enqueue front-end breadcrumb styles
add_action('wp_enqueue_scripts', function() {
    $css_url = plugin_dir_url( dirname(__FILE__) ) . 'assets/css/breadcrumbs.css';
    $css_file = plugin_dir_path( dirname(__FILE__) ) . 'assets/css/breadcrumbs.css';
    $ver = file_exists($css_file) ? filemtime($css_file) : null;

    wp_enqueue_style(
        'laa-breadcrumbs',
        $css_url,
        [],
        $ver
    );
}, 20);

// Main filter to adjust Yoast breadcrumb links
add_filter('wpseo_breadcrumb_links', function( $links ){
    if (!taxonomy_exists('nsmi_category')) return $links;
    $rename_archive = function(array $links): array {
        // Prefer a page with slug "self-help", fallback to /self-help
        $page = get_page_by_path('self-help');
        $self_help_url = $page ? get_permalink($page->ID) : home_url('/self-help/');

        foreach ($links as &$l) {
            if (!empty($l['ptarchive']) && ($l['ptarchive'] === 'legal_article' || $l['ptarchive'] === 'interactive_guide')) {
                $l['text'] = 'Self-Help';
                $l['url']  = $self_help_url;
            }
        }
        return $links;
    };

    if ( ! is_singular(['post', 'legal_article', 'interactive_guide']) ) {
        return $rename_archive($links);
    }

    $post_id    = get_the_ID();
    $primary_id = (int) get_post_meta($post_id, '_primary_nsmi_category', true);
    $mode       = 'top_and_primary'; // full_chain | top_and_primary | top_only

    if ( $primary_id ) {
        $primary = get_term($primary_id, 'nsmi_category');
        if ( $primary && ! is_wp_error($primary) ) {

            // Build lineage
            $ancestor_ids = array_reverse( (array) get_ancestors( $primary->term_id, 'nsmi_category' ) );
            $chain_terms  = [];

            if ($mode === 'full_chain') {
                foreach ($ancestor_ids as $aid) {
                    $t = get_term($aid, 'nsmi_category');
                    if ($t && ! is_wp_error($t)) $chain_terms[] = $t;
                }
                $chain_terms[] = $primary;
            } elseif ($mode === 'top_and_primary') {
                if (!empty($ancestor_ids)) {
                    $top_id = reset($ancestor_ids);
                    $top    = get_term($top_id, 'nsmi_category');
                    if ($top && ! is_wp_error($top)) $chain_terms[] = $top;
                    if ($primary->term_id !== $top_id) $chain_terms[] = $primary;
                } else {
                    $chain_terms[] = $primary;
                }
            } else { // top_only
                if (!empty($ancestor_ids)) {
                    $top_id = reset($ancestor_ids);
                    $top    = get_term($top_id, 'nsmi_category');
                    if ($top && ! is_wp_error($top)) $chain_terms[] = $top;
                } else {
                    $chain_terms[] = $primary;
                }
            }

            if ($chain_terms) {
                // Remove any existing nsmi_category crumbs Yoast added
                foreach ($links as $i => $link) {
                    if (!empty($link['taxonomy']) && $link['taxonomy'] === 'nsmi_category') {
                        unset($links[$i]);
                    }
                }
                $links = array_values($links);

                // Insert our chain just before the final (post) crumb
                $insert = [];

                // Determine top landing permalink (if any)
                $top_landing_url = '';
                if (!empty($chain_terms)) {
                    $top_term = reset($chain_terms);
                    if (!empty($top_term->slug)) {
                        $top_landing = get_posts([
                            'post_type'   => 'page',
                            'meta_key'    => '_nsmi_issue',
                            'meta_value'  => $top_term->slug,
                            'numberposts' => 1,
                            'fields'      => 'ids',
                        ]);
                        if ($top_landing && !is_wp_error($top_landing)) {
                            $top_landing_url = get_permalink($top_landing[0]) ?: '';
                        }
                    }
                }

                foreach ($chain_terms as $i => $term) {
                    $term_link = '';

                    // If we have a top landing URL, use it for all chain links;
                    // append fragment for non-top items so the landing accordion opens.
                    if ($top_landing_url) {
                        if ($i === 0) {
                            $term_link = $top_landing_url;
                        } else {
                            $term_link = untrailingslashit($top_landing_url) . '#' . rawurlencode($term->slug);
                        }
                    } else {
                        // No top landing; try per-term landing by slug
                        if (!empty($term->slug)) {
                            $landing = get_posts([
                                'post_type'   => 'page',
                                'meta_key'    => '_nsmi_issue',
                                'meta_value'  => $term->slug,
                                'numberposts' => 1,
                                'fields'      => 'ids',
                            ]);
                            if ($landing && !is_wp_error($landing)) {
                                $permalink = get_permalink($landing[0]);
                                if ($i === 0) {
                                    $term_link = $permalink;
                                } else {
                                    $term_link = untrailingslashit($permalink) . '#' . rawurlencode($term->slug);
                                }
                            }
                        }
                    }

                    // Fallback to term archive if still empty
                    if (empty($term_link)) {
                        $term_link = get_term_link($term);
                    }

                    if (is_wp_error($term_link)) {
                        continue;
                    }

                    $insert[] = [
                        'url'      => $term_link,
                        'text'     => $term->name,
                        'term_id'  => $term->term_id,
                        'taxonomy' => 'nsmi_category',
                    ];
                }
                if ($insert) {
                    $last_index = count($links) - 1;
                    if ($last_index >= 0) {
                        array_splice($links, $last_index, 0, $insert);
                    } else {
                        $links = $insert;
                    }
                }
            }
        }
    }

    return $rename_archive($links);
}, 15);

/**
 * Remove (suppress) the final article crumb output, leaving previous category crumb linked.
 * Yoast still thinks the article is the last crumb (so prior stays linked).
 */
add_filter('wpseo_breadcrumb_single_link', function( $link_output, $link ){
    if ( is_singular(['post', 'legal_article', 'interactive_guide']) && strpos($link_output, 'breadcrumb_last') !== false ) {
        // Suppress the article crumb entirely
        return '';
    }
    return $link_output;
}, 10, 2);

/**
 * Clean any trailing separator left after suppressing last crumb.
 */
add_filter('wpseo_breadcrumb_output', function( $output ){
    if ( ! is_singular(['post', 'legal_article', 'interactive_guide']) ) return $output;

    // Remove trailing separators and whitespace
    $output = preg_replace('~(?:\s*(?:&lt;|<|&raquo;|»|›|&gt;|>|\|))*\s*$~u', '', $output);

    return $output;
}, 30);
