<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Disable browser autocomplete on configured forms to discourage back-button/autofill showing PII.
 * Applies to: <form>, <input>, <textarea>, <select> (adds autocomplete="off" if not already present).
 */

function lgpm_is_configured_form( $form_id ) {
    $settings = lgpm_get_settings();
    if ( empty( $settings['forms'] ) ) return false;
    foreach ( $settings['forms'] as $row ) {
        if ( (int) $form_id === (int) $row['form_id'] ) return true;
    }
    return false;
}

/** Add autocomplete="off" to the <form> tag */
add_filter( 'gform_form_tag', function( $form_tag, $form ) {
    if ( empty( $form['id'] ) || ! lgpm_is_configured_form( (int) $form['id'] ) ) {
        return $form_tag;
    }
    // Only add if not already present
    if ( stripos( $form_tag, 'autocomplete=' ) === false ) {
        $form_tag = preg_replace( '/<form\b/i', '<form autocomplete="off"', $form_tag, 1 );
    }
    return $form_tag;
}, 10, 2 );

/** Add autocomplete="off" to field HTML (input/textarea/select) */
add_filter( 'gform_field_content', function( $content, $field, $value, $lead_id, $form_id ) {
    if ( ! lgpm_is_configured_form( (int) $form_id ) ) {
        return $content;
    }

    // Add autocomplete="off" to INPUT/TEXTAREA/SELECT elements that don’t already have it
    $callback = function( $matches ) {
        $tag = $matches[0];
        return ( stripos( $tag, 'autocomplete=' ) === false )
            ? preg_replace( '/^<(\w+)/i', '<$1 autocomplete="off"', $tag, 1 )
            : $tag;
    };

    $content = preg_replace_callback( '/<(input|textarea|select)\b[^>]*>/i', $callback, $content );

    return $content;
}, 10, 5 );
