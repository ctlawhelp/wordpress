<?php
// CONSOLIDATE ALL ADMIN STYLES INTO ONE FUNCTION
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['legal_article', 'post', 'interactive_guide']) || !taxonomy_exists('nsmi_category')) return;

    echo '<style>
    /* Wider sidebar for category metabox */
    #poststuff #side-info-column { width: 340px !important; }
    .edit-post-sidebar { width: 340px !important; min-width: 340px !important; }
    .components-panel__body { max-width: 320px; }

    /* NSMI metabox specific styles */
    #latb_nsmi_category_mb .inside { padding: 0 !important; }
    #latb_nsmi_category_mb .nsmi-cat-tree { padding: 12px; }

    /* Primary category highlight */
    .nsmi-primary-indicator {
        color: #d63638;
        font-weight: bold;
        margin-left: 5px;
    }
    </style>';
});

// Hide the default NSMI Categories metabox for legal_article
add_action('add_meta_boxes', function() {
    if (!taxonomy_exists('nsmi_category')) return;
    remove_meta_box('nsmi_categorydiv', 'legal_article', 'side');
    remove_meta_box('tagsdiv-nsmi_category', 'legal_article', 'side');
    remove_meta_box('nsmi_categorydiv', 'post', 'side');
    remove_meta_box('tagsdiv-nsmi_category', 'post', 'side');
    remove_meta_box('nsmi_categorydiv', 'interactive_guide', 'side');
    remove_meta_box('tagsdiv-nsmi_category', 'interactive_guide', 'side');
}, 99);

// Replace default NSMI category metabox
add_action( 'add_meta_boxes', function() {
    if (!taxonomy_exists('nsmi_category')) return;
    // Remove the default hierarchical checkbox UI
    remove_meta_box( 'nsmi_categorydiv', 'legal_article', 'side' );

    // Add your custom box
    foreach (['legal_article', 'post', 'interactive_guide'] as $post_type) {
        add_meta_box(
            'nsmi_category_custom',
            __( 'NSMI Categories', 'ctlawhelp' ),
            'latb_render_nsmi_category_metabox',
            $post_type,
            'side',
            'default'
        );
    }
});

// Remove NSMI Categories panel from Block Editor (Gutenberg)
add_filter('allowed_block_types_all', function($allowed_blocks, $editor_context) {
    if (!taxonomy_exists('nsmi_category')) return $allowed_blocks;
    if (!empty($editor_context->post) && in_array($editor_context->post->post_type, ['legal_article', 'post', 'interactive_guide'])) {
        if (is_array($allowed_blocks)) {
            // Remove taxonomy panel by filtering out the core/categories block
            if (($key = array_search('core/categories', $allowed_blocks)) !== false) {
                unset($allowed_blocks[$key]);
            }
        }
    }
    return $allowed_blocks;
}, 10, 2);

// Remove taxonomy panel from REST API for Block Editor
add_filter('rest_prepare_taxonomy', function($response, $taxonomy, $request) {
    if (!taxonomy_exists('nsmi_category')) return $response;
    if ($taxonomy->name === 'nsmi_category') {
        $data = $response->get_data();
        $data['visibility']['show_ui'] = false;
        $response->set_data($data);
    }
    return $response;
}, 10, 3);

// Enqueue custom JS to force padding override for NSMI metabox
add_action('admin_enqueue_scripts', function($hook) {
    if (!taxonomy_exists('nsmi_category')) return;
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_script(
            'nsmi-metabox-js',
            plugin_dir_url(__FILE__) . '../assets/js/nsmi-metabox.js',
            [],
            null,
            true
        );
    }
});

// Example callback for your custom metabox
function latb_render_nsmi_category_metabox( $post ) {
    $tax_name = 'nsmi_category';
    
    // Get post language to filter terms
    $post_lang = function_exists('pll_get_post_language') ? pll_get_post_language($post->ID) : '';
    
    // Get terms filtered by language if Polylang is active
    $term_args = [ 'taxonomy' => $tax_name, 'hide_empty' => false, 'parent' => 0 ];
    if ($post_lang && function_exists('pll_get_term')) {
        $term_args['lang'] = $post_lang;
    }
    
    $terms = get_terms($term_args);
    $selected = wp_get_object_terms($post->ID, $tax_name, ['fields'=>'ids']);
    $primary = get_post_meta($post->ID, '_primary_nsmi_category', true);
    echo '<div id="nsmi-category-tree" style="padding:5px;">';
    latb_render_category_tree($terms, $selected, true, $primary, $post_lang);
    echo '</div>';
    // Basic CSS for tree (single column)
    echo '<style>
    #nsmi-category-tree ul.nsmi-tree { list-style:none; margin:0; padding:0; }
    #nsmi-category-tree li { margin:2px 0; display:block; align-items:center; }
    #nsmi-category-tree label { display:inline-block; width:auto; font-weight:inherit; }
    .nsmi-tree-children { margin-left:18px; }
    .primary-radio { margin-left:8px; }
    .primary-highlight { background-color: #0073aa; color: white; }
    </style>';
    // JS to allow only one radio selection, and deselect by clicking again
    echo "<script>
    document.addEventListener('DOMContentLoaded',function(){
        // Highlight logic for primary category
        function updatePrimaryHighlight() {
            document.querySelectorAll('#nsmi-category-tree label').forEach(function(label){
                label.classList.remove('primary-highlight');
            });
            var primaryId = document.getElementById('primary_nsmi_category').value;
            if (primaryId) {
                var label = document.querySelector('input[type=\"checkbox\"][value=\"'+primaryId+'\"]')?.closest('label');
                if (label) label.classList.add('primary-highlight');
            }
        }
        // Enable label text click to select/deselect primary
        document.querySelectorAll('#nsmi-category-tree label .nsmi-label-text').forEach(function(span){
            span.style.cursor = 'pointer';
            span.addEventListener('click', function(e){
                var label = span.closest('label');
                var checkbox = label.querySelector('input[type=\"checkbox\"]');
                var hidden = document.getElementById('primary_nsmi_category');
                if (hidden && checkbox) {
                    if (hidden.value == checkbox.value) {
                        hidden.value = '';
                    } else {
                        hidden.value = checkbox.value;
                    }
                    updatePrimaryHighlight();
                }
            });
        });
        updatePrimaryHighlight();
    });
    </script>";
}

function latb_render_category_tree($terms, $selected, $is_top_level = false, $primary = '', $post_lang = '') {
    if(empty($terms)) return;
    echo '<ul class="nsmi-tree">';
    foreach($terms as $term){
        // Get children with same language filter
        $children_args = [ 'taxonomy'=>'nsmi_category', 'hide_empty'=>false, 'parent'=>$term->term_id ];
        if ($post_lang && function_exists('pll_get_term')) {
            $children_args['lang'] = $post_lang;
        }
        $children = get_terms($children_args);
        
        $checked = in_array($term->term_id, $selected)?'checked':'';
        $is_primary = ($primary == $term->term_id) ? 'primary-highlight' : '';
        
        // Get English translation hint if this is a Spanish term
        $hint = '';
        if ($post_lang === 'es' && function_exists('pll_get_term_translations')) {
            $translations = pll_get_term_translations($term->term_id);
            if (isset($translations['en'])) {
                $en_term = get_term($translations['en'], 'nsmi_category');
                if ($en_term && !is_wp_error($en_term)) {
                    $hint = ' <span style="color:#666;font-weight:normal;">(' . esc_html($en_term->name) . ')</span>';
                }
            }
        }
        
        echo '<li>';
        if($is_top_level) {
            echo '<label style="font-weight:bold;"><input type="checkbox" name="tax_input[nsmi_category][]" value="'.$term->term_id.'" '.$checked.'> <span class="nsmi-label-text">'.esc_html($term->name).$hint.'</span></label>';
        } else {
            echo '<label class="'.$is_primary.'"><input type="checkbox" name="tax_input[nsmi_category][]" value="'.$term->term_id.'" '.$checked.'> <span class="nsmi-label-text">'.esc_html($term->name).$hint.'</span></label>';
        }
        if($children && !empty($children)){
            echo '<ul class="nsmi-tree-children">';
            latb_render_category_tree($children, $selected, false, $primary, $post_lang);
            echo '</ul>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Add hidden field for primary category
add_action('add_meta_boxes', function() {
    if (!taxonomy_exists('nsmi_category')) return;
    foreach (['legal_article', 'post', 'interactive_guide'] as $post_type) {
        add_meta_box(
            'nsmi_category_custom',
            __( 'NSMI Categories', 'ctlawhelp' ),
            function($post) {
                echo '<div class="inside" style="padding:0 !important;">';
                latb_render_nsmi_category_metabox($post);
                $primary = get_post_meta($post->ID, '_primary_nsmi_category', true);
                echo '<input type="hidden" id="primary_nsmi_category" name="primary_nsmi_category" value="'.esc_attr($primary).'">';
                echo '</div>';
            },
            $post_type,
            'side',
            'default'
        );
    }
});

// Save the primary category selection
add_action('save_post', function($post_id){
    if (!taxonomy_exists('nsmi_category')) return;
    if(isset($_POST['primary_nsmi_category']) && $_POST['primary_nsmi_category'] !== ''){
        update_post_meta($post_id, '_primary_nsmi_category', intval($_POST['primary_nsmi_category']));
    } else {
        delete_post_meta($post_id, '_primary_nsmi_category');
    }
});
