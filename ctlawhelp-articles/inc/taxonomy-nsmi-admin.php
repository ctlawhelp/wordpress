<?php
/**
 * NSMI Taxonomy Admin: custom list ordering for /wp-admin/edit-tags.php?taxonomy=nsmi_category
 *
 * Clusters:
 *   EN parent
 *   ES parent (if exists)
 *     EN children (A?Z)
 *     ES child immediately after each EN child (if exists)
 *
 * Notes:
 * - Works only in admin on the NSMI taxonomy page.
 * - Requires Polylang (uses 'en' and 'es' slugs; change if yours differ).
 */

if ( ! is_admin() ) { return; }

/** 
 * DISABLE CUSTOM NSMI ADMIN FUNCTIONALITY
 * Set to true to use WordPress default taxonomy display
 * Set to false to use custom bilingual clustering
 */
define('DISABLE_NSMI_CUSTOM_ADMIN', true);

/** Increase memory limit for taxonomy admin to prevent memory exhaustion */
add_action('admin_init', function() {
    if (defined('DISABLE_MEMORY_INCREASE') && DISABLE_MEMORY_INCREASE) {
        return;
    }

    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'nsmi_category') {
        // Only increase if current limit is less than 256M
        $current_limit = ini_get('memory_limit');
        if ($current_limit && wp_convert_hr_to_bytes($current_limit) < 134217728) { // 128M
            ini_set('memory_limit', '128M');
        }
    }
});

/** Small visual tweaks (optional) */
add_action('admin_head', function () {
    if ( isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'nsmi_category' ) {
        echo '<style>
            .column-slug { width: 160px !important; }
            .level-1 a { padding-left: 20px !important; font-weight: 400; }
            .level-0 a { font-weight: 700 !important; }
        </style>';
    }
});

/** Debug info display */
add_action('admin_notices', function() {
    if (defined('DISABLE_NSMI_DEBUG') && DISABLE_NSMI_DEBUG) {
        return;
    }

    if ( isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'nsmi_category' ) {
        // Get term counts by language (LIMITED QUERY to avoid memory issues)
        $sample_terms = get_terms([
            'taxonomy' => 'nsmi_category',
            'hide_empty' => false,
            'lang' => '',
            'number' => 200, // Limit sample size
            'orderby' => 'count',
            'order' => 'DESC',
        ]);

        if (!is_wp_error($sample_terms)) {
            $lang_counts = [];
            $translation_issues = 0;

            foreach ($sample_terms as $term) {
                $lang = function_exists('pll_get_term_language') ? pll_get_term_language($term->term_id) : 'unknown';
                $lang_counts[$lang] = ($lang_counts[$lang] ?? 0) + 1;

                // Check for translation issues (only for sample)
                if (function_exists('pll_get_term_translations')) {
                    $translations = pll_get_term_translations($term->term_id);
                    if (empty($translations) || count($translations) < 2) {
                        $translation_issues++;
                    }
                }
            }

            // Get total count separately
            $total_count = wp_count_terms('nsmi_category', ['hide_empty' => false]);

            $current_url = add_query_arg([]);
            $show_all_url = add_query_arg('show_all_terms', '1');
            $simple_url = add_query_arg('simple_mode', '1');
            $normal_url = remove_query_arg(['show_all_terms', 'simple_mode']);

            echo '<div class="notice notice-info">';
            echo '<p><strong>NSMI Taxonomy Debug Info:</strong></p>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            echo '<li>Total terms: <strong>' . intval($total_count) . '</strong></li>';
            echo '<li>Sample analysis (' . count($sample_terms) . ' terms): ' . implode(', ', array_map(function($lang, $count) {
                return "$lang: $count";
            }, array_keys($lang_counts), $lang_counts)) . '</li>';
            echo '<li>Sample translation issues: <strong>' . $translation_issues . '</strong></li>';
            echo '</ul>';

            // Add view toggle buttons
            echo '<p style="margin-top: 10px;">';
            if (isset($_GET['show_all_terms'])) {
                echo '<a href="' . esc_url($normal_url) . '" class="button button-primary">Show Bilingual View</a> ';
                echo '<span style="color: #666;">(Currently showing ALL terms alphabetically)</span>';
            } elseif (isset($_GET['simple_mode'])) {
                echo '<a href="' . esc_url($normal_url) . '" class="button button-primary">Show Bilingual View</a> ';
                echo '<span style="color: #666;">(Currently showing simple alphabetical view)</span>';
            } else {
                echo '<a href="' . esc_url($show_all_url) . '" class="button button-secondary">Show ALL Terms</a> ';
                echo '<a href="' . esc_url($simple_url) . '" class="button button-secondary">Simple View</a> ';
                echo '<span style="color: #666;">(Currently showing bilingual clustered view)</span>';
            }
            echo '</p>';

            echo '<p><small>Check the debug logs for detailed translation relationship information.</small></p>';
            echo '</div>';
        }
    }
});

/**
 * Replace the list table class ONLY for nsmi_category.
 * Important: our class must exist BEFORE WP builds the list table.
 */
add_action('admin_init', function () {
    // Load the core class so we can extend it.
    require_once ABSPATH . 'wp-admin/includes/class-wp-terms-list-table.php';

    if ( ! class_exists('NSMI_Terms_List_Table') && class_exists('WP_Terms_List_Table') ) {
        class NSMI_Terms_List_Table extends WP_Terms_List_Table {
            private array $clustered_rows = []; // each row: ['term' => WP_Term, 'level' => int]

            public function prepare_items() {
                // If custom admin is disabled, use WordPress default behavior
                if (defined('DISABLE_NSMI_CUSTOM_ADMIN') && DISABLE_NSMI_CUSTOM_ADMIN) {
                    parent::prepare_items();
                    return;
                }

                $taxonomy = $this->screen->taxonomy; // e.g., 'nsmi_category'
                $lang_en  = 'en'; // adjust if your English slug differs
                $lang_es  = 'es'; // adjust if your Spanish slug differs

                // Check if user wants to see ALL terms (bypass bilingual clustering)
                $show_all = isset($_GET['show_all_terms']) && $_GET['show_all_terms'] === '1';
                $simple_mode = isset($_GET['simple_mode']) && $_GET['simple_mode'] === '1';

                if ($show_all || $simple_mode) {
                    // SIMPLE MODE: Just show all terms alphabetically
                    $all_terms = get_terms([
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => false,
                        'lang'       => '',     // all languages
                        'number'     => 1000,   // Reasonable limit to prevent memory issues
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                    ]);

                    if (is_wp_error($all_terms)) {
                        $this->items = [];
                        $this->set_pagination_args(['total_items' => 0, 'per_page' => 20]);
                        return;
                    }

                    $this->items = $all_terms;
                    $this->set_pagination_args([
                        'total_items' => count($all_terms),
                        'per_page'    => $this->get_items_per_page( "{$taxonomy}_per_page", 20 ),
                        'total_pages' => ceil(count($all_terms) / $this->get_items_per_page( "{$taxonomy}_per_page", 20 )),
                    ]);
                    return;
                }

                // Check actual language slugs used in Polylang
                if (function_exists('pll_languages_list')) {
                    $pll_languages = pll_languages_list(['fields' => 'slug']);
                    // Adjust language codes if they don't match expected 'en'/'es'
                    if (in_array('en', $pll_languages)) {
                        $lang_en = 'en';
                    } elseif (in_array('english', $pll_languages)) {
                        $lang_en = 'english';
                    }

                    if (in_array('es', $pll_languages)) {
                        $lang_es = 'es';
                    } elseif (in_array('spanish', $pll_languages)) {
                        $lang_es = 'spanish';
                    }
                }

                // Fetch all terms in all languages, no limit.
                $all_terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'lang'       => '',     // all languages
                    'number'     => 0,
                ]);

                if ( is_wp_error($all_terms) ) {
                    $this->items = [];
                    $this->set_pagination_args(['total_items' => 0, 'per_page' => 20]);
                    return;
                }

                // DEBUG: Show term counts and language breakdown (LIMITED QUERY)
                $debug_info = [];
                if (function_exists('pll_get_term_language') && (!defined('DISABLE_NSMI_DEBUG') || !DISABLE_NSMI_DEBUG)) {
                    // Only get a sample of terms for debugging to avoid memory issues
                    $sample_terms = get_terms([
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => false,
                        'lang'       => '',
                        'number'     => 100, // Limit to 100 terms for debugging
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                    ]);

                    if (!is_wp_error($sample_terms)) {
                        foreach ($sample_terms as $t) {
                            $lang = pll_get_term_language($t->term_id) ?: 'no_lang';
                            $debug_info[$lang] = ($debug_info[$lang] ?? 0) + 1;
                        }
                    }
                }

                // If no Polylang or no terms, fall back to default behavior
                if (!function_exists('pll_languages_list') || empty($all_terms)) {
                    parent::prepare_items();
                    return;
                }

                // Index by ID for quick lookups.
                $by_id = [];
                foreach ( $all_terms as $t ) { $by_id[$t->term_id] = $t; }

                // 1) Find English top-level parents.
                $en_parents = [];
                foreach ( $all_terms as $t ) {
                    if ( (int) $t->parent !== 0 ) { continue; }
                    $tr = function_exists('pll_get_term_translations') ? pll_get_term_translations($t->term_id) : [];
                    $en_id = $tr[$lang_en] ?? null;
                    if ( $en_id && $t->term_id === (int) $en_id ) {
                        $en_parents[$en_id] = $by_id[$en_id]->name;
                    }
                }

                // If no English parents found, fall back to all top-level terms
                if (empty($en_parents)) {
                    foreach ( $all_terms as $t ) {
                        if ( (int) $t->parent === 0 ) {
                            $en_parents[$t->term_id] = $t->name;
                        }
                    }
                }

                // Sort parents by name (natural, case-insensitive).
                uasort($en_parents, 'strnatcasecmp');

                // Optional: term search (matches Name like core does).
                $search = isset($_REQUEST['s']) ? trim(wp_unslash((string) $_REQUEST['s'])) : '';
                $search_lc = mb_strtolower($search);

                // Build clustered rows.
                $rows = [];
                foreach ( array_keys($en_parents) as $en_parent_id ) {
                    $parent_tr = function_exists('pll_get_term_translations') ? pll_get_term_translations($en_parent_id) : [];

                    // EN parent (level 0)
                    if ( $this->matches_search($by_id[$en_parent_id], $search_lc) ) {
                        $rows[] = ['term' => $by_id[$en_parent_id], 'level' => 0];
                    } else if ( $search !== '' ) {
                        // If searching and parent doesn't match, we still include children if they match.
                        // We won't add the non-matching parent row itself.
                    } else {
                        $rows[] = ['term' => $by_id[$en_parent_id], 'level' => 0];
                    }

                    // ES parent (level 0)
                    if ( ! empty($parent_tr[$lang_es]) && isset($by_id[$parent_tr[$lang_es]]) ) {
                        $es_parent = $by_id[$parent_tr[$lang_es]];
                        if ( $search === '' || $this->matches_search($es_parent, $search_lc) ) {
                            $rows[] = ['term' => $es_parent, 'level' => 0];
                        }
                    }

                    // EN children of this EN parent (A?Z by name)
                    $en_children = get_terms([
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => false,
                        'parent'     => $en_parent_id,
                        'lang'       => $lang_en,
                        'orderby'    => 'name',
                        'number'     => 0,
                    ]);
                    if ( ! is_wp_error($en_children) ) {
                        foreach ( $en_children as $en_child ) {
                            // EN child (level 1)
                            if ( $search === '' || $this->matches_search($en_child, $search_lc) ) {
                                $rows[] = ['term' => $en_child, 'level' => 1];
                            }
                            // ES translation of child (level 1) right after its EN sibling
                            $child_tr = function_exists('pll_get_term_translations') ? pll_get_term_translations($en_child->term_id) : [];
                            if ( ! empty($child_tr[$lang_es]) && isset($by_id[$child_tr[$lang_es]]) ) {
                                $es_child = $by_id[$child_tr[$lang_es]];
                                if ( $search === '' || $this->matches_search($es_child, $search_lc) ) {
                                    $rows[] = ['term' => $es_child, 'level' => 1];
                                }
                            }
                        }
                    }
                }

                // If searching and nothing matched yet, fall back to core alphabetical (rare).
                if ( $search !== '' && empty($rows) ) {
                    foreach ( $all_terms as $t ) {
                        if ( $this->matches_search($t, $search_lc) ) {
                            $rows[] = ['term' => $t, 'level' => (int) ($t->parent ? 1 : 0)];
                        }
                    }
                }

                // ABSOLUTE FALLBACK: If no rows at all, show all terms alphabetically
                if (empty($rows) && !empty($all_terms)) {
                    // Sort all terms by name
                    usort($all_terms, function($a, $b) {
                        return strnatcasecmp($a->name, $b->name);
                    });
                    foreach ($all_terms as $t) {
                        $rows[] = ['term' => $t, 'level' => (int) ($t->parent ? 1 : 0)];
                    }
                }

                // If we still have very few rows compared to total terms, show a warning
                if (count($rows) < count($all_terms) * 0.5 && count($all_terms) > 10) {
                    // FORCE SHOW ALL TERMS: If bilingual clustering is broken, just show everything
                    $rows = [];
                    usort($all_terms, function($a, $b) {
                        return strnatcasecmp($a->name, $b->name);
                    });
                    foreach ($all_terms as $t) {
                        $rows[] = ['term' => $t, 'level' => 0]; // Show all as top level for simplicity
                    }
                }

                // Pagination
                $per_page = $this->get_items_per_page( "{$taxonomy}_per_page", 20 );
                $total    = count($rows);
                $paged    = $this->get_pagenum();
                $start    = max( 0, ( $paged - 1 ) * $per_page );
                $this->clustered_rows = array_slice($rows, $start, $per_page);

                // Items are just terms; we carry level separately.
                $this->items = array_map( fn($r) => $r['term'], $this->clustered_rows );

                $this->set_pagination_args([
                    'total_items' => $total,
                    'per_page'    => $per_page,
                    'total_pages' => $per_page ? ceil($total / $per_page) : 1,
                ]);
            }

            /** Simple name search helper */
            private function matches_search( WP_Term $t, string $needle_lc ): bool {
                if ( $needle_lc === '' ) return true;
                return mb_stripos( $t->name, $needle_lc ) !== false;
            }

            /**
             * Output rows in our precomputed order with correct levels.
             * We bypass the parent walker and feed levels ourselves.
             */
            public function display_rows_or_placeholder() {
                if ( ! $this->has_items() ) {
                    echo '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">';
                    $this->no_items();
                    echo '</td></tr>';
                    return;
                }

                foreach ( $this->clustered_rows as $row ) {
                    $this->single_row( $row['term'], $row['level'] );
                }
            }
        }
    }

    // Now tell WP to use our class on the NSMI screen (only if custom admin is enabled).
    add_filter( 'wp_list_table_class_name', function( $class_name, $args ) {
        // If custom admin is disabled, use WordPress default
        if (defined('DISABLE_NSMI_CUSTOM_ADMIN') && DISABLE_NSMI_CUSTOM_ADMIN) {
            return $class_name;
        }

        $is_nsmi_page = $class_name === 'WP_Terms_List_Table'
          && isset($args['screen'])
          && $args['screen'] instanceof WP_Screen
          && $args['screen']->taxonomy === 'nsmi_category';

        if ( $is_nsmi_page ) {
            return 'NSMI_Terms_List_Table';
        }
        return $class_name;
    }, 10, 2 );
});
