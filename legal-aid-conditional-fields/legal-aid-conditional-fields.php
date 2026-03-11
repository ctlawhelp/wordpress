<?php
/**
 * Plugin Name: Legal Aid Conditional Fields
 * Description: Lightweight conditional fields for Elementor forms - focused and minimal.
 * Version: 1.0.0
 * Author: CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'LACF_VERSION', '1.0.0' );
define( 'LACF_URL', plugin_dir_url( __FILE__ ) );
define( 'LACF_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Legal_Aid_Conditional_Fields {
    
    private static $instance = null;
    
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }
    
    public function init() {
        // Check if Elementor Pro is active
        if ( ! $this->is_elementor_pro_active() ) {
            add_action( 'admin_notices', array( $this, 'elementor_pro_missing_notice' ) );
            return;
        }
        
        // Initialize the conditional fields
        add_action( 'elementor/init', array( $this, 'setup_conditional_fields' ) );
    }
    
    private function is_elementor_pro_active() {
        return class_exists( '\ElementorPro\Plugin' );
    }
    
    public function elementor_pro_missing_notice() {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Legal Aid Conditional Fields</strong> requires Elementor Pro to be active.';
        echo '</p></div>';
    }
    
    public function setup_conditional_fields() {
        // Add conditional fields control to forms
        add_action( 'elementor/element/form/section_form_fields/before_section_end', 
            array( $this, 'add_conditional_fields_control' ), 10, 2 );
        
        // Enqueue frontend scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        
        // Add validation hook
        add_action( 'elementor_pro/forms/validation', array( $this, 'validate_conditional_fields' ), 10, 3 );
        
        // Inject form settings into rendered form markup
        add_filter( 'elementor/widget/render_content', array( $this, 'add_form_data_attributes' ), 10, 2 );
    }
    
    public function add_conditional_fields_control( $element, $args ) {
        $element->add_control(
            'lacf_enable_conditional',
            array(
                'label' => 'Enable Conditional Fields',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => '',
                'separator' => 'before',
            )
        );
        
        $repeater = new \Elementor\Repeater();
        
        $repeater->add_control(
            'lacf_target_field',
            array(
                'label' => 'Target Field ID',
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'The field ID to show/hide (works with input fields and HTML fields)',
            )
        );
        
        $repeater->add_control(
            'lacf_condition_field',
            array(
                'label' => 'Condition Field ID',
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'The field ID that triggers the condition',
            )
        );
        
        $repeater->add_control(
            'lacf_operator',
            array(
                'label' => 'Operator',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'equals',
                'options' => array(
                    'equals' => 'Equals',
                    'not_equals' => 'Not Equals',
                    'contains' => 'Contains',
                    'not_empty' => 'Not Empty',
                    'empty' => 'Empty',
                ),
            )
        );
        
        $repeater->add_control(
            'lacf_value',
            array(
                'label' => 'Value',
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'The value to compare against. Use commas for multiple values (e.g., "1,2,3"). Leave empty for "empty" and "not empty" operators.',
            )
        );
        
        $repeater->add_control(
            'lacf_action',
            array(
                'label' => 'Action',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'show',
                'options' => array(
                    'show' => 'Show Field',
                    'hide' => 'Hide Field',
                ),
            )
        );
        
        $element->add_control(
            'lacf_conditions',
            array(
                'label' => 'Conditional Rules',
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'condition' => array(
                    'lacf_enable_conditional' => 'yes',
                ),
                'title_field' => 'Show/Hide {{{ lacf_target_field }}} when {{{ lacf_condition_field }}} {{{ lacf_operator }}} {{{ lacf_value }}}',
            )
        );
    }
    
    public function enqueue_frontend_scripts() {
        // Only load on frontend pages (not admin)
        if ( ! is_admin() ) {
            wp_enqueue_script(
                'lacf-frontend',
                LACF_URL . 'assets/js/frontend.js',
                array( 'jquery' ),
                LACF_VERSION,
                true
            );
            
            // Pass conditional field settings to JavaScript
            wp_localize_script( 'lacf-frontend', 'lacf_data', array(
                'debug' => true, // Enable debug for now
            ));
            
            wp_enqueue_style(
                'lacf-frontend',
                LACF_URL . 'assets/css/frontend.css',
                array(),
                LACF_VERSION
            );
        }
    }
    
    public function validate_conditional_fields( $record, $ajax_handler ) {
        $form_settings = $record->get( 'form_settings' );
        
        // Check if conditional fields are enabled
        if ( empty( $form_settings['lacf_enable_conditional'] ) || $form_settings['lacf_enable_conditional'] !== 'yes' ) {
            return;
        }
        
        $fields = $record->get( 'fields' );
        $conditions = isset( $form_settings['lacf_conditions'] ) ? $form_settings['lacf_conditions'] : array();
        
        foreach ( $conditions as $condition ) {
            $target_field = $condition['lacf_target_field'];
            $condition_field = $condition['lacf_condition_field'];
            $operator = $condition['lacf_operator'];
            $expected_value = $condition['lacf_value'];
            $action = $condition['lacf_action'];
            
            // Skip if condition field doesn't exist in submitted data
            if ( ! isset( $fields[ $condition_field ] ) ) {
                continue;
            }
            
            $actual_value = $fields[ $condition_field ]['value'];
            $condition_met = $this->evaluate_condition( $actual_value, $operator, $expected_value );
            
            // Determine if target field should be required based on condition
            $should_show = ( $action === 'show' ) ? $condition_met : ! $condition_met;
            
            // If field should be hidden but has a value, clear it
            if ( ! $should_show && isset( $fields[ $target_field ] ) && ! empty( $fields[ $target_field ]['value'] ) ) {
                $record->remove_field( $target_field );
            }
        }
    }
    
    private function evaluate_condition( $actual_value, $operator, $expected_value ) {
        switch ( $operator ) {
            case 'equals':
                // Support comma-separated values
                if ( strpos( $expected_value, ',' ) !== false ) {
                    $values = array_map( 'trim', explode( ',', $expected_value ) );
                    return in_array( $actual_value, $values );
                }
                return $actual_value === $expected_value;
            case 'not_equals':
                // Support comma-separated values  
                if ( strpos( $expected_value, ',' ) !== false ) {
                    $values = array_map( 'trim', explode( ',', $expected_value ) );
                    return ! in_array( $actual_value, $values );
                }
                return $actual_value !== $expected_value;
            case 'contains':
                return strpos( $actual_value, $expected_value ) !== false;
            case 'not_empty':
                return ! empty( $actual_value );
            case 'empty':
                return empty( $actual_value );
            default:
                return false;
        }
    }
    
    public function add_form_data_attributes( $content, $widget ) {
        if ( 'form' !== $widget->get_name() ) {
            return $content;
        }

        $settings = $widget->get_settings_for_display();
        
        // Check if conditional fields are enabled
        if ( ! empty( $settings['lacf_enable_conditional'] ) && $settings['lacf_enable_conditional'] === 'yes' ) {
            $widget_id   = $widget->get_id();
            $field_map   = array();
            $form_fields = isset( $settings['form_fields'] ) && is_array( $settings['form_fields'] ) ? $settings['form_fields'] : array();

            foreach ( $form_fields as $field ) {
                if ( empty( $field['_id'] ) ) {
                    continue;
                }

                $field_map[] = array(
                    'element_id' => $field['_id'],
                    'field_id'   => ! empty( $field['custom_id'] ) ? $field['custom_id'] : '',
                    'label'      => ! empty( $field['field_label'] ) ? $field['field_label'] : '',
                    'type'       => ! empty( $field['field_type'] ) ? $field['field_type'] : '',
                );
            }

            $attributes = array(
                'data-lacf-enabled="true"',
                'data-lacf-widget="' . esc_attr( $widget_id ) . '"',
            );

            if ( ! empty( $field_map ) ) {
                $attributes[] = 'data-lacf-field-map="' . esc_attr( wp_json_encode( $field_map ) ) . '"';
            }

            if ( ! empty( $settings['lacf_conditions'] ) ) {
                $attributes[] = 'data-lacf-conditions="' . esc_attr( wp_json_encode( $settings['lacf_conditions'] ) ) . '"';
            }

            $attributes_string = implode( ' ', $attributes );

            $content = $this->inject_form_attributes( $content, $attributes_string );
        }
        
        return $content;
    }

    private function inject_form_attributes( $content, $attributes_string ) {
        if ( empty( $attributes_string ) ) {
            return $content;
        }

        $patterns = array(
            '/<form(.*?)class="([^"]*elementor-form[^"]*)"/i',
            '/<form(.*?)class=\'([^\']*elementor-form[^\']*)\'/i',
        );

        foreach ( $patterns as $pattern ) {
            $updated = preg_replace(
                $pattern,
                '<form$1class="$2" ' . $attributes_string,
                $content,
                1
            );

            if ( null !== $updated && $updated !== $content ) {
                return $updated;
            }
        }

        return $content;
    }
}

// Initialize the plugin
Legal_Aid_Conditional_Fields::instance();