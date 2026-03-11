<?php
/**
 * NSMI Category Protection System
 * Protects NSMI category data from being wiped by page builders like Elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class LATB_NSMI_Protection {
    
    public function __construct() {
        // Hook early to set up protection
        add_action( 'init', [ $this, 'init_protection' ], 1 );
    }
    
    public function init_protection() {
        if ( ! taxonomy_exists( 'nsmi_category' ) ) {
            return;
        }
        
        // Backup NSMI data before any saves
        add_action( 'pre_post_update', [ $this, 'backup_nsmi_data' ], 1 );
        add_action( 'wp_insert_post', [ $this, 'backup_nsmi_data_on_insert' ], 1 );
        
        // Monitor for NSMI data loss and restore
        add_action( 'save_post', [ $this, 'protect_nsmi_on_save' ], 999 );
        add_action( 'wp_after_insert_post', [ $this, 'restore_nsmi_after_save' ], 999 );
        
        // Hook into meta operations to prevent primary category clearing
        add_action( 'updated_post_meta', [ $this, 'monitor_primary_meta_changes' ], 10, 4 );
        add_filter( 'update_post_metadata', [ $this, 'prevent_primary_meta_clearing' ], 10, 5 );
        
        // Hook into taxonomy operations
        add_action( 'set_object_terms', [ $this, 'monitor_taxonomy_changes' ], 10, 6 );
        
        // Add debug logging
        add_action( 'save_post', [ $this, 'debug_nsmi_state' ], 1000 );
        
        // For local development - trigger immediate checks instead of relying on cron
        add_action( 'wp_loaded', [ $this, 'maybe_trigger_immediate_check' ] );
        add_action( 'nsmi_periodic_restore_check', [ $this, 'periodic_restore_check' ] );
        
        // Also run check on every admin page load for local development
        add_action( 'admin_init', [ $this, 'admin_restore_check' ] );
        
        // Show admin notices when NSMI data is restored
        add_action( 'admin_notices', [ $this, 'show_restore_notices' ] );
        
        // Add manual trigger for testing (local development)
        add_action( 'wp_ajax_nsmi_manual_restore', [ $this, 'manual_restore_trigger' ] );
        add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_restore_button' ], 999 );
    }
    
    /**
     * Backup NSMI data before post updates
     */
    public function backup_nsmi_data( $post_id ) {
        if ( ! $this->should_protect_post( $post_id ) ) {
            return;
        }
        
        $current_terms = wp_get_object_terms( $post_id, 'nsmi_category', array( 'fields' => 'ids' ) );
        $primary_nsmi = get_post_meta( $post_id, '_primary_nsmi_category', true );
        
        // Store backup in transient
        $backup_data = array(
            'terms' => is_array( $current_terms ) ? $current_terms : array(),
            'primary' => $primary_nsmi ? intval( $primary_nsmi ) : 0,
            'timestamp' => time(),
            'post_type' => get_post_type( $post_id )
        );
        
        set_transient( "nsmi_backup_{$post_id}", $backup_data, 300 ); // 5 minute expiry
        
        // Backup logged for debugging if needed
        // error_log( "NSMI Protection: Backed up data for post {$post_id} - Terms: " . json_encode( $current_terms ) . ", Primary: {$primary_nsmi}" );
    }
    
    /**
     * Backup NSMI data on post insert
     */
    public function backup_nsmi_data_on_insert( $post_id ) {
        // Only backup if this is an update, not a new post
        if ( ! wp_is_post_revision( $post_id ) && get_post_status( $post_id ) !== 'auto-draft' ) {
            $this->backup_nsmi_data( $post_id );
        }
    }
    
    /**
     * Protect NSMI data during save_post
     */
    public function protect_nsmi_on_save( $post_id ) {
        if ( ! $this->should_protect_post( $post_id ) ) {
            return;
        }
        
        $is_elementor = $this->is_elementor_save();
        error_log( "NSMI Protection: Save detected for post {$post_id} - " . ( $is_elementor ? 'Elementor' : 'WordPress' ) );
        
        // Get backup data
        $backup = get_transient( "nsmi_backup_{$post_id}" );
        if ( ! $backup ) {
            error_log( "NSMI Protection: No backup found for post {$post_id}" );
            return;
        }
        
        // Check current NSMI state
        $current_terms = wp_get_object_terms( $post_id, 'nsmi_category', array( 'fields' => 'ids' ) );
        $current_primary = get_post_meta( $post_id, '_primary_nsmi_category', true );
        
        // State comparison for debugging if needed
        // error_log( "NSMI Protection: Current state - Terms: " . json_encode( $current_terms ) . ", Primary: {$current_primary}" );
        // error_log( "NSMI Protection: Backup state - Terms: " . json_encode( $backup['terms'] ) . ", Primary: {$backup['primary']}" );
        
        $needs_restore = false;
        
        // Always restore if data was wiped, regardless of save type
        // Check if terms were wiped
        if ( ! empty( $backup['terms'] ) && empty( $current_terms ) ) {
            wp_set_object_terms( $post_id, $backup['terms'], 'nsmi_category' );
            error_log( "NSMI Protection: Restored taxonomy terms for post {$post_id}" );
            $needs_restore = true;
        }
        
        // Check if primary category was wiped or set to 0
        if ( ! empty( $backup['primary'] ) && $backup['primary'] != '0' && ( empty( $current_primary ) || $current_primary == '0' ) ) {
            update_post_meta( $post_id, '_primary_nsmi_category', $backup['primary'] );
            error_log( "NSMI Protection: Restored primary category {$backup['primary']} for post {$post_id}" );
            $needs_restore = true;
        }
        
        if ( $needs_restore ) {
            error_log( "NSMI Protection: Successfully restored NSMI data for post {$post_id}" );
        } else {
            error_log( "NSMI Protection: No restoration needed for post {$post_id}" );
        }
    }
    
    /**
     * Restore NSMI data after post save completes
     */
    public function restore_nsmi_after_save( $post_id ) {
        if ( ! $this->should_protect_post( $post_id ) ) {
            return;
        }
        
        // Final check and restore if needed
        $backup = get_transient( "nsmi_backup_{$post_id}" );
        if ( ! $backup ) {
            return;
        }
        
        $current_terms = wp_get_object_terms( $post_id, 'nsmi_category', array( 'fields' => 'ids' ) );
        $current_primary = get_post_meta( $post_id, '_primary_nsmi_category', true );
        
        // Final restoration if data is still missing
        if ( ! empty( $backup['terms'] ) && empty( $current_terms ) ) {
            wp_set_object_terms( $post_id, $backup['terms'], 'nsmi_category' );
            error_log( "NSMI Protection: Final restore of taxonomy terms for post {$post_id}" );
        }
        
        if ( ! empty( $backup['primary'] ) && ( empty( $current_primary ) || $current_primary == '0' ) ) {
            update_post_meta( $post_id, '_primary_nsmi_category', $backup['primary'] );
            error_log( "NSMI Protection: Final restore of primary category for post {$post_id}" );
        }
        
        // Clean up transient after successful restore
        delete_transient( "nsmi_backup_{$post_id}" );
    }
    
    /**
     * Monitor primary meta changes
     */
    public function monitor_primary_meta_changes( $meta_id, $post_id, $meta_key, $meta_value ) {
        if ( $meta_key !== '_primary_nsmi_category' || ! $this->should_protect_post( $post_id ) ) {
            return;
        }
        
        error_log( "NSMI Protection: Primary meta changed for post {$post_id} to value: {$meta_value}" );
        
        // If being set to 0 during Elementor save, restore from backup
        if ( ( $meta_value == '0' || empty( $meta_value ) ) && $this->is_elementor_save() ) {
            $backup = get_transient( "nsmi_backup_{$post_id}" );
            if ( $backup && ! empty( $backup['primary'] ) ) {
                update_post_meta( $post_id, '_primary_nsmi_category', $backup['primary'] );
                error_log( "NSMI Protection: Prevented primary meta clearing, restored to {$backup['primary']}" );
            }
        }
    }
    
    /**
     * Prevent primary meta from being cleared
     */
    public function prevent_primary_meta_clearing( $check, $post_id, $meta_key, $meta_value, $prev_value ) {
        if ( $meta_key !== '_primary_nsmi_category' || ! $this->should_protect_post( $post_id ) ) {
            return $check;
        }
        
        // If Elementor is trying to clear a valid primary category, prevent it
        if ( ( $meta_value == '0' || empty( $meta_value ) ) && ! empty( $prev_value ) && $prev_value != '0' && $this->is_elementor_save() ) {
            $backup = get_transient( "nsmi_backup_{$post_id}" );
            if ( $backup && ! empty( $backup['primary'] ) ) {
                error_log( "NSMI Protection: Blocked primary meta clearing attempt for post {$post_id}" );
                // Update with backup value instead
                update_post_meta( $post_id, '_primary_nsmi_category', $backup['primary'] );
                return true; // Skip the original update
            }
        }
        
        return $check;
    }
    
    /**
     * Monitor taxonomy changes
     */
    public function monitor_taxonomy_changes( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        if ( $taxonomy !== 'nsmi_category' || ! $this->should_protect_post( $object_id ) ) {
            return;
        }
        
        error_log( "NSMI Protection: Taxonomy terms changed for post {$object_id} - New: " . json_encode( $terms ) . ", Old: " . json_encode( $old_tt_ids ) );
        
        // If terms were cleared during Elementor save, restore from backup
        if ( empty( $terms ) && ! empty( $old_tt_ids ) && $this->is_elementor_save() ) {
            $backup = get_transient( "nsmi_backup_{$object_id}" );
            if ( $backup && ! empty( $backup['terms'] ) ) {
                wp_set_object_terms( $object_id, $backup['terms'], 'nsmi_category' );
                error_log( "NSMI Protection: Restored taxonomy terms from backup for post {$object_id}" );
            }
        }
    }
    
    /**
     * Debug NSMI state after all saves
     */
    public function debug_nsmi_state( $post_id ) {
        if ( ! $this->should_protect_post( $post_id ) ) {
            return;
        }
        
        $terms = wp_get_object_terms( $post_id, 'nsmi_category', array( 'fields' => 'ids' ) );
        $primary = get_post_meta( $post_id, '_primary_nsmi_category', true );
        $post_type = get_post_type( $post_id );
        $save_type = $this->is_elementor_save() ? 'Elementor' : 'WordPress';
        
        // Final state logging for debugging if needed
        // error_log( "NSMI Protection: Final state for {$post_type} {$post_id} ({$save_type} save) - Terms: " . json_encode( $terms ) . ", Primary: {$primary}" );
    }
    
    /**
     * Check if this post should be protected
     */
    private function should_protect_post( $post_id ) {
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return false;
        }
        
        $post_type = get_post_type( $post_id );
        return in_array( $post_type, [ 'interactive_guide', 'legal_article', 'post' ] );
    }
    
    /**
     * Detect if this is an Elementor save
     */
    private function is_elementor_save() {
        $is_elementor = false;
        $detection_method = '';
        
        // Check for Elementor AJAX actions
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            if ( isset( $_POST['action'] ) && strpos( $_POST['action'], 'elementor' ) !== false ) {
                $is_elementor = true;
                $detection_method = 'AJAX action: ' . $_POST['action'];
            }
            if ( isset( $_POST['actions'] ) ) {
                $actions = json_decode( stripslashes( $_POST['actions'] ), true );
                if ( is_array( $actions ) ) {
                    foreach ( $actions as $action ) {
                        if ( isset( $action['action'] ) && strpos( $action['action'], 'save' ) !== false ) {
                            $is_elementor = true;
                            $detection_method = 'AJAX actions array: ' . $action['action'];
                        }
                    }
                }
            }
        }
        
        // Check for Elementor in referrer
        if ( isset( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'elementor' ) !== false ) {
            $is_elementor = true;
            $detection_method = 'HTTP_REFERER: ' . $_SERVER['HTTP_REFERER'];
        }
        
        // Check for Elementor editor context
        if ( isset( $_POST['editor_post_id'] ) || isset( $_GET['elementor-preview'] ) ) {
            $is_elementor = true;
            $detection_method = 'Editor context';
        }
        
        // Log all save attempts with detailed context
        $context = array(
            'DOING_AJAX' => defined( 'DOING_AJAX' ) ? DOING_AJAX : false,
            'POST_action' => isset( $_POST['action'] ) ? $_POST['action'] : 'none',
            'HTTP_REFERER' => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : 'none',
            'REQUEST_URI' => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : 'none',
            'editor_post_id' => isset( $_POST['editor_post_id'] ) ? $_POST['editor_post_id'] : 'none',
            'elementor_preview' => isset( $_GET['elementor-preview'] ) ? $_GET['elementor-preview'] : 'none',
        );
        
        // Context logging for debugging if needed  
        // error_log( "NSMI Protection: Save context - " . json_encode( $context ) . " | Elementor detected: " . ( $is_elementor ? "YES ({$detection_method})" : "NO" ) );
        
        return $is_elementor;
    }
    
    /**
     * Maybe trigger immediate check for local development
     */
    public function maybe_trigger_immediate_check() {
        // For local development, manually trigger checks more frequently
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Check if we have any recent backups that need attention
            $this->periodic_restore_check();
        }
        
        // Also schedule regular cron as backup
        if ( ! wp_next_scheduled( 'nsmi_periodic_restore_check' ) ) {
            wp_schedule_event( time(), 'every_minute', 'nsmi_periodic_restore_check' );
        }
    }
    
    /**
     * Run restore check on admin pages for local development
     */
    public function admin_restore_check() {
        // Only run on admin pages related to posts
        $screen = get_current_screen();
        if ( $screen && in_array( $screen->post_type, [ 'interactive_guide', 'legal_article', 'post' ] ) ) {
            $this->periodic_restore_check();
        }
    }
    
    /**
     * Periodic check to restore any NSMI data that might have been wiped
     */
    public function periodic_restore_check() {
        // Get all active NSMI backups
        global $wpdb;
        
        $transients = $wpdb->get_results( "
            SELECT option_name, option_value 
            FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_nsmi_backup_%'
            AND option_value IS NOT NULL
        " );
        
        foreach ( $transients as $transient ) {
            $post_id = str_replace( '_transient_nsmi_backup_', '', $transient->option_name );
            $backup = maybe_unserialize( $transient->option_value );
            
            if ( ! $backup || ! is_numeric( $post_id ) || ! $this->should_protect_post( $post_id ) ) {
                continue;
            }
            
            // Check if this backup is recent (within last 5 minutes)
            if ( time() - $backup['timestamp'] > 300 ) {
                continue;
            }
            
            // Check current state
            $current_terms = wp_get_object_terms( $post_id, 'nsmi_category', array( 'fields' => 'ids' ) );
            $current_primary = get_post_meta( $post_id, '_primary_nsmi_category', true );
            
            $restored = false;
            
            // Restore if data is missing
            if ( ! empty( $backup['terms'] ) && empty( $current_terms ) ) {
                wp_set_object_terms( $post_id, $backup['terms'], 'nsmi_category' );
                error_log( "NSMI Protection: Periodic restore of taxonomy terms for post {$post_id}" );
                $restored = true;
            }
            
            if ( ! empty( $backup['primary'] ) && $backup['primary'] != '0' && ( empty( $current_primary ) || $current_primary == '0' ) ) {
                update_post_meta( $post_id, '_primary_nsmi_category', $backup['primary'] );
                error_log( "NSMI Protection: Periodic restore of primary category for post {$post_id}" );
                $restored = true;
            }
            
            if ( $restored ) {
                error_log( "NSMI Protection: Periodic restoration completed for post {$post_id}" );
                // Set a transient to show admin notice
                set_transient( "nsmi_restored_notice_{$post_id}", true, 30 );
            }
        }
    }
    
    /**
     * Show admin notices when NSMI data is restored
     */
    public function show_restore_notices() {
        global $wpdb;
        
        // Check for any restoration notices
        $notices = $wpdb->get_results( "
            SELECT option_name 
            FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_nsmi_restored_notice_%'
        " );
        
        foreach ( $notices as $notice ) {
            $post_id = str_replace( '_transient_nsmi_restored_notice_', '', $notice->option_name );
            if ( is_numeric( $post_id ) ) {
                $post_title = get_the_title( $post_id );
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>NSMI Protection:</strong> Restored missing NSMI category data for "' . esc_html( $post_title ) . '" (ID: ' . $post_id . ')</p>';
                echo '</div>';
                
                // Clean up the notice
                delete_transient( "nsmi_restored_notice_{$post_id}" );
            }
        }
    }
    
    /**
     * Manual restore trigger for testing
     */
    public function manual_restore_trigger() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        $this->periodic_restore_check();
        
        wp_die( 'NSMI restore check completed. Check the error log for details.' );
    }
    
    /**
     * Add restore button to admin bar for local testing
     */
    public function add_admin_bar_restore_button( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) || ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }
        
        $wp_admin_bar->add_node( array(
            'id'    => 'nsmi_manual_restore',
            'title' => 'Check NSMI Restore',
            'href'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=nsmi_manual_restore' ), 'nsmi_restore' ),
            'meta'  => array(
                'title' => 'Manually trigger NSMI restore check for testing',
            ),
        ) );
    }
}

// Add custom cron schedule for every minute
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => 'Every Minute'
    );
    return $schedules;
});

// Initialize the protection system
new LATB_NSMI_Protection();