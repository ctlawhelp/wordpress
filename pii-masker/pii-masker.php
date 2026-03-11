<?php
/**
 * Plugin Name: PII Masker
 * Description: Masks selected PII fields in Gravity Forms entries after a delay. Supports multiple forms, each with its own input IDs. Logs (optional) to uploads/PII-mask-debug.log.
 * Version: 2.3.0
 * Author: Kate Frank
 * License: GPLv2 or later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LGPM_PATH', plugin_dir_path( __FILE__ ) );
define( 'LGPM_URL',  plugin_dir_url( __FILE__ ) );
define( 'LGPM_OPTION_KEY', 'lgpm_settings' );
define( 'LGPM_CRON_HOOK',  'lgpm_mask_pii_run' );
define( 'LGPM_LOG_BASENAME', 'PII-mask-debug.log' );

require_once LGPM_PATH . 'includes/helpers.php';
require_once LGPM_PATH . 'includes/log.php';
require_once LGPM_PATH . 'includes/settings.php';
require_once LGPM_PATH . 'includes/scheduler.php';
require_once LGPM_PATH . 'includes/masker.php';
require_once LGPM_PATH . 'includes/notices.php';
require_once LGPM_PATH . 'includes/inspector.php';
require_once LGPM_PATH . 'includes/privacy.php'; // NEW: disable autocomplete on configured forms
