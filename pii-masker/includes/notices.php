<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin notice if masking hasn't run recently AND there are new entries in the lookback window.
 * Now suppresses the notice if any future lgpm_mask_pii_run is scheduled,
 * and uses local site time for the lookback window to avoid TZ mismatches.
 */
add_action( 'admin_notices', function() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( ! class_exists( 'GFAPI' ) ) return;

    $s = lgpm_get_settings();
    if ( empty( $s['forms'] ) ) return;

    $last_run   = lgpm_get_last_run();
    $idle_for   = time() - (int) $last_run;
    $idle_limit = max( 1, (int) $s['idle_notice_minutes'] ) * MINUTE_IN_SECONDS;

    // If any lgpm_mask_pii_run is scheduled in the future (any args), suppress warning
    if ( wp_next_scheduled( LGPM_CRON_HOOK ) ) {
        return;
    }

    if ( $idle_for < $idle_limit ) return;

    // Use local site time for lookback start
    $lookback = max( 1, (int) $s['lookback_minutes'] ) * MINUTE_IN_SECONDS;
    $start_ts = current_time( 'timestamp' ) - $lookback; // local tz timestamp
    $start_dt = date_i18n( 'Y-m-d H:i:s', $start_ts );

    // Count new entries for configured forms
    $total_new = 0;
    foreach ( $s['forms'] as $row ) {
        $fid = (int) $row['form_id'];
        if ( ! $fid ) continue;

        $search_criteria = array(
            'status'     => 'active',
            'start_date' => $start_dt, // local time string
        );
        $count = GFAPI::count_entries( $fid, $search_criteria );
        if ( is_wp_error( $count ) ) continue;
        $total_new += (int) $count;
    }

    if ( $total_new > 0 ) {
        $last_label = $last_run ? esc_html( gmdate( 'Y-m-d H:i:s', $last_run ) . 'Z' ) : 'never';
        $idle_mins  = floor( $idle_for / 60 );
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>PII Masker:</strong> Masking hasn\'t run in about ' . esc_html( $idle_mins ) . ' minutes (last run: ' . $last_label . '). ';
        echo 'There are approximately <strong>' . esc_html( $total_new ) . '</strong> recent entries. ';
        echo 'On low‑traffic sites, WP‑Cron may lag. Visit any page or trigger <code>wp-cron.php</code> to run scheduled tasks.';
        echo '</p></div>';
    }
});
