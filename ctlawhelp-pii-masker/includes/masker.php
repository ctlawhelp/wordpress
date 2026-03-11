<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cron runner: masks input IDs for the entry's form if configured.
 * Now also updates the "last run" timestamp even when the entry was already masked.
 */
add_action( LGPM_CRON_HOOK, function( $entry_id, $form_id ) {
    if ( ! class_exists( 'GFAPI' ) ) return;

    $settings = lgpm_get_settings();
    $row = lgpm_find_form_row( $settings, $form_id );
    if ( ! $row ) {
        lgpm_log( 'Skip: form not configured', array( 'entry_id' => (int) $entry_id, 'form_id' => (int) $form_id ) );
        return;
    }

    $entry = GFAPI::get_entry( $entry_id );
    if ( is_wp_error( $entry ) ) {
        lgpm_log( 'Error: get_entry failed', array( 'entry_id' => (int) $entry_id, 'form_id' => (int) $form_id ) );
        return;
    }
    if ( (int) rgar( $entry, 'form_id' ) !== (int) $form_id ) {
        lgpm_log( 'Skip: entry form_id mismatch', array( 'entry_id' => (int) $entry_id, 'form_id' => (int) $form_id ) );
        return;
    }

    $input_ids = lgpm_parse_input_ids( $row['input_ids'] );
    if ( empty( $input_ids ) ) {
        lgpm_log( 'No input IDs configured', array( 'entry_id' => (int) $entry_id, 'form_id' => (int) $form_id ) );
        return;
    }

    $changed = false;
    foreach ( $entry as $k => $v ) {
        // numeric keys or sub-input keys like "1.3"
        if ( ! preg_match( '/^\d+(?:\.\d+)?$/', (string) $k ) ) continue;
        if ( in_array( (string) $k, $input_ids, true ) && $v !== '' ) {
            $entry[ $k ] = 'xxxxxx';
            $changed = true;
        }
    }

    if ( $changed ) {
        $res = GFAPI::update_entry( $entry );
        if ( ! is_wp_error( $res ) ) {
            lgpm_set_last_run(); // mark last run on successful update
        }
        lgpm_log( 'Masked entry', array(
            'entry_id' => (int) $entry_id,
            'form_id'  => (int) $form_id,
            'result'   => is_wp_error( $res ) ? 'error' : 'ok',
        ) );
    } else {
        // We touched a valid entry but nothing needed changing (already masked) — still count as a successful run
        lgpm_set_last_run();
        lgpm_log( 'No changes applied (already masked)', array(
            'entry_id' => (int) $entry_id,
            'form_id'  => (int) $form_id,
        ) );
    }
}, 10, 2 );
