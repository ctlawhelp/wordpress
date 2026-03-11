<?php
/**
 * NSMI Category Icons
 */

// Add icon field to NSMI taxonomy add/edit forms
if ( ! function_exists( 'nsmi_add_icon_field' ) ) {
    function nsmi_add_icon_field( $term ) {
        $image_id  = is_object( $term ) ? get_term_meta( $term->term_id, 'nsmi_icon_id', true ) : '';
        $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
        ?>
        <tr class="form-field">
            <th><label for="nsmi_icon_id"><?php _e( 'Icon', 'ctlawhelp' ); ?></label></th>
            <td>
                <div class="nsmi-icon-wrapper">
                    <input type="hidden" id="nsmi_icon_id" name="nsmi_icon_id"
                           value="<?php echo esc_attr( $image_id ); ?>" />
                    <div class="nsmi-icon-preview" style="margin-bottom:10px;">
                        <?php if ( $image_url ) : ?>
                            <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:80px;height:auto;" />
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button nsmi-icon-upload"><?php _e( 'Select Icon', 'ctlawhelp' ); ?></button>
                    <button type="button" class="button nsmi-icon-remove" <?php if ( ! $image_url ) echo 'disabled'; ?>>
                        <?php _e( 'Remove Icon', 'ctlawhelp' ); ?>
                    </button>
                </div>
            </td>
        </tr>
        <?php
    }
    add_action( 'nsmi_category_add_form_fields', 'nsmi_add_icon_field' );
    add_action( 'nsmi_category_edit_form_fields', 'nsmi_add_icon_field' );
}

// Save term icon meta
if ( ! function_exists( 'nsmi_save_icon_field' ) ) {
    function nsmi_save_icon_field( $term_id ) {
        if ( isset( $_POST['nsmi_icon_id'] ) ) {
            update_term_meta( $term_id, 'nsmi_icon_id', absint( $_POST['nsmi_icon_id'] ) );
        }
    }
    add_action( 'created_nsmi_category', 'nsmi_save_icon_field' );
    add_action( 'edited_nsmi_category', 'nsmi_save_icon_field' );
}

// Enqueue media uploader script
if ( ! function_exists( 'nsmi_icon_admin_scripts' ) ) {
    function nsmi_icon_admin_scripts( $hook ) {
        // Load on taxonomy pages for NSMI categories
        $load_script = false;

        if ( in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
            if ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === 'nsmi_category' ) {
                $load_script = true;
            }
        }

        if ( $load_script ) {
            // Ensure jQuery and media scripts are loaded
            wp_enqueue_script( 'jquery' );
            wp_enqueue_media();

            // Load admin CSS for icon styling
            wp_enqueue_style( 'nsmi-admin-css', LAA_URL . 'assets/css/legal-aid-articles.css' );

            wp_enqueue_script(
                'nsmi-icon-js',
                LAA_URL . 'assets/js/nsmi-icons.js',
                array( 'jquery', 'media-upload', 'media-views' ),
                filemtime( LAA_PATH . 'assets/js/nsmi-icons.js' ), // Cache busting
                true
            );
        }
    }
    add_action( 'admin_enqueue_scripts', 'nsmi_icon_admin_scripts' );
}

/**
 * 🔑 Helpers
 */
if ( ! function_exists( 'get_nsmi_icon' ) ) {
    function get_nsmi_icon( $term_id, $size = 'thumbnail', $attr = [] ) {
        $image_id = get_term_meta( $term_id, 'nsmi_icon_id', true );
        return $image_id ? wp_get_attachment_image( $image_id, $size, false, $attr ) : '';
    }
}

if ( ! function_exists( 'get_nsmi_icon_url' ) ) {
    function get_nsmi_icon_url( $term_id ) {
        $image_id = get_term_meta( $term_id, 'nsmi_icon_id', true );
        return $image_id ? wp_get_attachment_url( $image_id ) : '';
    }
}

/**
 * Admin Columns: Add Icon
 */
if ( ! function_exists( 'nsmi_add_icon_column' ) ) {
    function nsmi_add_icon_column( $columns ) {
        $columns['nsmi_icon'] = __( 'Icon', 'ctlawhelp' );
        return $columns;
    }
    add_filter( 'manage_edit-nsmi_category_columns', 'nsmi_add_icon_column' );
}

if ( ! function_exists( 'nsmi_show_icon_column' ) ) {
    function nsmi_show_icon_column( $content, $column_name, $term_id ) {
        if ( $column_name === 'nsmi_icon' ) {
            $url = get_nsmi_icon_url( $term_id );
            if ( $url ) {
                $content = '<img src="' . esc_url( $url ) . '" style="max-width:40px;height:auto;" />';
            }
        }
        return $content;
    }
    add_filter( 'manage_nsmi_category_custom_column', 'nsmi_show_icon_column', 10, 3 );
}
