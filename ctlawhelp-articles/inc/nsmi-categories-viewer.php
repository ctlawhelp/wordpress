<?php
/**
 * NSMI Categories Viewer - Lightweight admin page for viewing bilingual categories
 * Shows English/Spanish terms grouped together without heavy database queries
 */

if (!defined('ABSPATH')) exit;

// Add admin menu item under the NSMI top-level menu
add_action('admin_menu', function() {
    add_submenu_page(
        'nsmi-management',           // Parent menu slug
        'NSMI Categories Overview',
        'Categories Overview',
        'manage_categories',
        'nsmi-categories-overview',
        'nsmi_categories_overview_page'
    );
}, 11); // Higher priority to ensure it loads after the main menu

function nsmi_categories_overview_page() {
    if (!current_user_can('manage_categories')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Simple pagination
    $per_page = 20;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    // Get only English parent terms (much more efficient)
    $en_parents = get_terms([
        'taxonomy' => 'nsmi_category',
        'hide_empty' => false,
        'parent' => 0, // Only top-level
        'lang' => 'en', // Only English
        'number' => $per_page,
        'offset' => $offset,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);

    // Get total count for pagination
    $total_en_parents = wp_count_terms([
        'taxonomy' => 'nsmi_category',
        'parent' => 0,
        'lang' => 'en',
        'hide_empty' => false
    ]);

    $total_pages = ceil($total_en_parents / $per_page);

    ?>
    <div class="wrap">
        <h1>NSMI Categories Overview</h1>
        <p>Bilingual view of your NSMI categories. Click "Edit" to modify terms using WordPress standard interface.</p>
        
        <?php if (is_wp_error($en_parents) || empty($en_parents)): ?>
            <div class="notice notice-info">
                <p>No English parent categories found. Make sure your categories are properly set up with Polylang.</p>
            </div>
        <?php else: ?>
            
            <div class="tablenav top">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged,
                        'show_all' => false,
                        'type' => 'plain',
                    ]);
                    ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;">English Term</th>
                        <th style="width: 40%;">Spanish Translation</th>
                        <th style="width: 10%;">Children</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($en_parents as $en_parent): ?>
                        <?php
                        // Get Spanish translation (single query per parent)
                        $translations = function_exists('pll_get_term_translations') ? 
                            pll_get_term_translations($en_parent->term_id) : [];
                        $es_parent = null;
                        if (!empty($translations['es'])) {
                            $es_parent = get_term($translations['es'], 'nsmi_category');
                        }

                        // Count children efficiently
                        $child_count = get_terms([
                            'taxonomy' => 'nsmi_category',
                            'parent' => $en_parent->term_id,
                            'lang' => 'en',
                            'hide_empty' => false,
                            'fields' => 'count'
                        ]);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($en_parent->name); ?></strong>
                                <?php if ($en_parent->description): ?>
                                    <br><small class="description"><?php echo esc_html(wp_trim_words($en_parent->description, 15)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($es_parent && !is_wp_error($es_parent)): ?>
                                    <strong><?php echo esc_html($es_parent->name); ?></strong>
                                    <?php if ($es_parent->description): ?>
                                        <br><small class="description"><?php echo esc_html(wp_trim_words($es_parent->description, 15)); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <em style="color: #999;">No Spanish translation</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo intval($child_count); ?> child terms
                                <?php if ($child_count > 0): ?>
                                    <br><a href="#" onclick="toggleChildren(<?php echo $en_parent->term_id; ?>); return false;" style="font-size: 11px;">Show children</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('term.php?taxonomy=nsmi_category&tag_ID=' . $en_parent->term_id . '&post_type=legal_article'); ?>" class="button button-small">Edit EN</a>
                                <?php if ($es_parent && !is_wp_error($es_parent)): ?>
                                    <br><a href="<?php echo admin_url('term.php?taxonomy=nsmi_category&tag_ID=' . $es_parent->term_id . '&post_type=legal_article'); ?>" class="button button-small">Edit ES</a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Hidden children row -->
                        <?php if ($child_count > 0): ?>
                            <tr id="children-<?php echo $en_parent->term_id; ?>" style="display: none;">
                                <td colspan="4" style="padding-left: 40px; background-color: #f9f9f9;">
                                    <div id="children-content-<?php echo $en_parent->term_id; ?>">
                                        <em>Loading children...</em>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged,
                        'show_all' => false,
                        'type' => 'plain',
                    ]);
                    ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script>
    function toggleChildren(parentId) {
        const row = document.getElementById('children-' + parentId);
        const content = document.getElementById('children-content-' + parentId);
        
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            
            // Load children via AJAX if not already loaded
            if (content.innerHTML === '<em>Loading children...</em>') {
                loadChildren(parentId);
            }
        } else {
            row.style.display = 'none';
        }
    }

    function loadChildren(parentId) {
        const content = document.getElementById('children-content-' + parentId);
        
        // Simple AJAX call
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=nsmi_load_children&parent_id=' + parentId + '&_wpnonce=' + '<?php echo wp_create_nonce("nsmi_load_children"); ?>'
        })
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<em>Error loading children</em>';
        });
    }
    </script>

    <style>
    .wp-list-table th, .wp-list-table td {
        vertical-align: top;
    }
    .description {
        color: #666;
        font-style: italic;
    }
    </style>
    <?php
}

// AJAX handler for loading children
add_action('wp_ajax_nsmi_load_children', function() {
    if (!current_user_can('manage_categories') || !wp_verify_nonce($_POST['_wpnonce'], 'nsmi_load_children')) {
        wp_die('Unauthorized');
    }

    $parent_id = intval($_POST['parent_id']);
    
    // Get English children
    $en_children = get_terms([
        'taxonomy' => 'nsmi_category',
        'parent' => $parent_id,
        'lang' => 'en',
        'hide_empty' => false,
        'orderby' => 'name',
        'number' => 50 // Reasonable limit
    ]);

    if (empty($en_children) || is_wp_error($en_children)) {
        echo '<em>No child terms found</em>';
        wp_die();
    }

    echo '<div style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 10px; align-items: center;">';
    
    foreach ($en_children as $en_child) {
        // Get Spanish translation
        $translations = function_exists('pll_get_term_translations') ? 
            pll_get_term_translations($en_child->term_id) : [];
        $es_child = null;
        if (!empty($translations['es'])) {
            $es_child = get_term($translations['es'], 'nsmi_category');
        }

        echo '<div><strong>' . esc_html($en_child->name) . '</strong></div>';
        
        echo '<div>';
        if ($es_child && !is_wp_error($es_child)) {
            echo '<strong>' . esc_html($es_child->name) . '</strong>';
        } else {
            echo '<em style="color: #999;">No Spanish translation</em>';
        }
        echo '</div>';
        
        echo '<div>';
        echo '<a href="' . admin_url('term.php?taxonomy=nsmi_category&tag_ID=' . $en_child->term_id . '&post_type=legal_article') . '" class="button button-small">Edit</a>';
        echo '</div>';
    }
    
    echo '</div>';
    wp_die();
});