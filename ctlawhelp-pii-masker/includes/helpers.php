<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function lgpm_default_settings() {
    return array(
        'delay_minutes'       => 60,
        'enable_log'          => 0,
        'idle_notice_minutes' => 180,  // show notice if idle this long
        'lookback_minutes'    => 1440, // look for entries in this window
        'forms'               => array(),
    );
}

function lgpm_get_settings() {
    $opt = get_option( LGPM_OPTION_KEY, array() );
    return wp_parse_args( $opt, lgpm_default_settings() );
}

function lgpm_sanitize_settings( $in ) {
    $out = lgpm_default_settings();

    $out['delay_minutes']       = isset( $in['delay_minutes'] ) ? max( 1, (int) $in['delay_minutes'] ) : 60;
    $out['enable_log']          = ! empty( $in['enable_log'] ) ? 1 : 0;
    $out['idle_notice_minutes'] = isset( $in['idle_notice_minutes'] ) ? max( 1, (int) $in['idle_notice_minutes'] ) : 180;
    $out['lookback_minutes']    = isset( $in['lookback_minutes'] ) ? max( 1, (int) $in['lookback_minutes'] ) : 1440;

    $out['forms'] = array();
    if ( ! empty( $in['forms'] ) && is_array( $in['forms'] ) ) {
        foreach ( $in['forms'] as $row ) {
            $form_id   = isset( $row['form_id'] ) ? absint( $row['form_id'] ) : 0;
            $input_ids = isset( $row['input_ids'] ) ? sanitize_text_field( $row['input_ids'] ) : '';
            if ( $form_id && $input_ids !== '' ) {
                $out['forms'][] = array(
                    'form_id'   => $form_id,
                    'input_ids' => $input_ids,
                );
            }
        }
    }

    lgpm_log( 'Settings saved', $out );
    return $out;
}

function lgpm_parse_input_ids( $csv ) {
    $ids = array_filter( array_map( 'trim', preg_split( '/\s*,\s*/', (string) $csv ) ) );
    return array_map( 'strval', $ids );
}

function lgpm_find_form_row( $settings, $form_id ) {
    if ( empty( $settings['forms'] ) ) return null;
    foreach ( $settings['forms'] as $row ) {
        if ( (int) $row['form_id'] === (int) $form_id ) return $row;
    }
    return null;
}

// Track last successful mask run timestamp
function lgpm_set_last_run( $ts = null ) {
    if ( $ts === null ) $ts = time();
    update_option( 'lgpm_last_run', (int) $ts, false );
}
function lgpm_get_last_run() {
    $v = get_option( 'lgpm_last_run', 0 );
    return (int) $v;
}
