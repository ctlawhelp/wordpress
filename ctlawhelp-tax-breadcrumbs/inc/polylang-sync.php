<?php
/**
 * Polylang Integration: Sync primary NSMI category between translations
 * 
 * When a post translation is created/saved, automatically sync the primary
 * category from the source language to the target language using the
 * translated term equivalent.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sync primary NSMI category when Polylang saves a translation.
 * 
 * This runs after Polylang has synced the taxonomies, so the translated
 * post already has the correct language-specific NSMI terms assigned.
 * We just need to translate the primary category meta.
 */
add_action('pll_save_post', 'latb_sync_primary_category_on_translation', 10, 3);

function latb_sync_primary_category_on_translation($post_id, $post, $translations) {
    // Only process supported post types
    if (!in_array($post->post_type, ['legal_article', 'interactive_guide', 'post'], true)) {
        return;
    }
    
    // Check if Polylang functions exist
    if (!function_exists('pll_get_post_language') || !function_exists('pll_get_term_translations')) {
        return;
    }
    
    // Get the current post's language
    $current_lang = pll_get_post_language($post_id);
    if (!$current_lang) {
        return;
    }
    
    // Check if this post already has a primary category set
    $existing_primary = get_post_meta($post_id, '_primary_nsmi_category', true);
    if ($existing_primary) {
        // Already has primary set, don't override
        return;
    }
    
    // Find the source translation (usually English)
    $source_post_id = null;
    foreach ($translations as $lang => $trans_id) {
        if ($lang !== $current_lang && $trans_id) {
            // Get primary category from this translation
            $source_primary = get_post_meta($trans_id, '_primary_nsmi_category', true);
            if ($source_primary) {
                $source_post_id = $trans_id;
                break;
            }
        }
    }
    
    // If no source found with primary category, nothing to sync
    if (!$source_post_id) {
        return;
    }
    
    // Get the source primary category term ID
    $source_primary_id = (int) get_post_meta($source_post_id, '_primary_nsmi_category', true);
    if (!$source_primary_id) {
        return;
    }
    
    // Get all translations of this term
    $term_translations = pll_get_term_translations($source_primary_id);
    if (empty($term_translations) || !is_array($term_translations)) {
        return;
    }
    
    // Get the term ID for the current post's language
    $target_term_id = isset($term_translations[$current_lang]) ? (int) $term_translations[$current_lang] : 0;
    
    if ($target_term_id) {
        // Verify the term exists and is assigned to this post
        $assigned_terms = wp_get_object_terms($post_id, 'nsmi_category', ['fields' => 'ids']);
        if (!is_wp_error($assigned_terms) && in_array($target_term_id, $assigned_terms, true)) {
            // Set the translated term as primary
            update_post_meta($post_id, '_primary_nsmi_category', $target_term_id);
        }
    }
}

/**
 * Also sync when primary category is updated on the source post.
 * This ensures changes propagate to translations.
 */
add_action('updated_post_meta', 'latb_sync_primary_to_translations', 10, 4);

function latb_sync_primary_to_translations($meta_id, $post_id, $meta_key, $meta_value) {
    // Only handle primary category meta
    if ($meta_key !== '_primary_nsmi_category') {
        return;
    }
    
    // Check if Polylang is active
    if (!function_exists('pll_get_post_translations') || !function_exists('pll_get_term_translations')) {
        return;
    }
    
    // Get all translations of this post
    $translations = pll_get_post_translations($post_id);
    if (empty($translations) || !is_array($translations)) {
        return;
    }
    
    // Get term translations
    $source_term_id = (int) $meta_value;
    if (!$source_term_id) {
        return;
    }
    
    $term_translations = pll_get_term_translations($source_term_id);
    if (empty($term_translations) || !is_array($term_translations)) {
        return;
    }
    
    // Update each translation with its language-specific term
    foreach ($translations as $lang => $trans_post_id) {
        // Skip the current post
        if ($trans_post_id == $post_id) {
            continue;
        }
        
        // Get the term ID for this language
        $target_term_id = isset($term_translations[$lang]) ? (int) $term_translations[$lang] : 0;
        
        if ($target_term_id) {
            // Verify the term is assigned to the translation
            $assigned_terms = wp_get_object_terms($trans_post_id, 'nsmi_category', ['fields' => 'ids']);
            if (!is_wp_error($assigned_terms) && in_array($target_term_id, $assigned_terms, true)) {
                // Update the primary category for this translation
                update_post_meta($trans_post_id, '_primary_nsmi_category', $target_term_id);
            }
        }
    }
}
