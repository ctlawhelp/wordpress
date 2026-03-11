<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function lgpm_log( $message, $context = array() ) {
    $s = lgpm_get_settings();
    if ( empty( $s['enable_log'] ) ) return;

    $uploads = wp_get_upload_dir();
    if ( empty( $uploads['basedir'] ) ) return;

    $path = trailingslashit( $uploads['basedir'] ) . LGPM_LOG_BASENAME;
    $time = gmdate( 'Y-m-d H:i:s' ) . 'Z';
    $line = '[' . $time . '] ' . $message;
    if ( ! empty( $context ) ) {
        $line .= ' ' . wp_json_encode( $context );
    }
    $line .= PHP_EOL;

    if ( ! file_exists( $path ) ) {
        @file_put_contents( $path, '' );
    }
    @file_put_contents( $path, $line, FILE_APPEND );
}
