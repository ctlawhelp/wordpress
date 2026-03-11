<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'gform_after_submission', function( $entry, $form ) {
    $settings = lgpm_get_settings();
    $forms = isset( $settings['forms'] ) ? $settings['forms'] : array();
    if ( empty( $forms ) ) return;

    $form_id = isset( $form['id'] ) ? (int) $form['id'] : 0;
    $row = lgpm_find_form_row( $settings, $form_id );
    if ( ! $row ) return;

    $entry_id = (int) rgar( $entry, 'id' );
    if ( ! $entry_id ) return;

    $delay_minutes = max( 1, (int) $settings['delay_minutes'] );
    $run_at = time() + ( $delay_minutes * MINUTE_IN_SECONDS );
    $args = array( $entry_id, $form_id );

    if ( ! wp_next_scheduled( LGPM_CRON_HOOK, $args ) ) {
        $ok = wp_schedule_single_event( $run_at, LGPM_CRON_HOOK, $args );
        lgpm_log( 'Scheduled mask', array(
            'entry_id' => $entry_id,
            'form_id'  => $form_id,
            'run_at'   => gmdate( 'c', $run_at ),
            'ok'       => (bool) $ok,
        ) );
    }
}, 999, 2 );
