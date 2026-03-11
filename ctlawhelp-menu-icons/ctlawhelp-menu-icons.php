<?php
/**
 * Plugin Name: CTLawHelp Menu Icons
 * Plugin URI: https://ctlawhelp.org
 * Description: Add image icons to WordPress navigation menu items with CSS variable support. Includes optional NSMI taxonomy integration.
 * Version: 2.0.0
 * Author: CTLawHelp
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Plugin constants
define( 'LAMI_VERSION', '2.0.0' );
define( 'LAMI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LAMI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! class_exists( 'CTLawHelp_Menu_Icons' ) ) :

// CSS is now handled entirely through the admin interface and dynamic generation
// No static CSS file needed!

class CTLawHelp_Menu_Icons {
    const META_KEY = '_menu_item_ctlh_icon_id';

    public function __construct() {
        add_action( 'wp_nav_menu_item_custom_fields', [ $this, 'field' ], 10, 4 );
        add_action( 'wp_update_nav_menu_item', [ $this, 'save' ], 10, 3 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );

        // Front-end
        add_filter( 'nav_menu_link_attributes', [ $this, 'link_style_var' ], 10, 4 );          // classic
        add_filter( 'walker_nav_menu_start_el', [ $this, 'inject_in_walker_output' ], 10, 4 ); // Elementor/custom walkers
        add_filter( 'render_block', [ $this, 'render_block_nav_icons' ], 10, 2 );              // block navigation

        add_filter( 'upload_mimes', [ $this, 'allow_svg_mime' ] );
        
        // Admin interface
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action( 'admin_init', [ $this, 'admin_init' ] );
        add_action( 'wp_head', [ $this, 'output_dynamic_css' ] );
    }

    /** Admin field */
    public function field( $item_id, $item, $depth, $args ) {
        $attachment_id = (int) get_post_meta( $item_id, self::META_KEY, true );
        $url   = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
        $thumb = $attachment_id ? wp_get_attachment_image(
            $attachment_id, [24,24], false,
            [ 'style' => 'width:24px;height:24px;object-fit:contain;display:inline-block;vertical-align:middle;border:1px solid #ddd;border-radius:4px;background:#fff' ]
        ) : '';
        ?>
        <div class="field-ctlh-menu-icon description-wide" style="margin-top:10px;">
            <label for="edit-menu-item-ctlh-icon-<?php echo esc_attr( $item_id ); ?>" style="display:block;font-weight:600;margin-bottom:4px;">
                <?php esc_html_e( 'Menu Icon', 'lami' ); ?>
            </label>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <input type="hidden"
                    class="widefat code edit-menu-item-ctlh-icon-id"
                    id="edit-menu-item-ctlh-icon-<?php echo esc_attr( $item_id ); ?>"
                    name="menu-item-ctlh_icon_id[<?php echo esc_attr( $item_id ); ?>]"
                    value="<?php echo esc_attr( $attachment_id ); ?>" />

                <button type="button" class="button button-secondary ctlh-icon-select" data-item="<?php echo esc_attr( $item_id ); ?>">
                    <?php echo $attachment_id ? esc_html__( 'Change Icon', 'lami' ) : esc_html__( 'Select Icon', 'lami' ); ?>
                </button>

                <button type="button" class="button ctlh-icon-clear" data-item="<?php echo esc_attr( $item_id ); ?>" <?php disabled( ! $attachment_id ); ?>>
                    <?php esc_html_e( 'Clear', 'lami' ); ?>
                </button>

                <span class="ctlh-icon-preview" id="ctlh-icon-preview-<?php echo esc_attr( $item_id ); ?>">
                    <?php echo $thumb ? $thumb : '<em style="color:#666;">' . esc_html__( 'No icon selected', 'lami' ) . '</em>'; ?>
                </span>

                <?php if ( $url ) : ?>
                    <code style="opacity:0.7;"><?php echo esc_html( $url ); ?></code>
                <?php endif; ?>
            </div>

            <p class="description" style="margin-top:6px;">
                <?php esc_html_e( 'Recommended: SVG silhouettes (single-color). PNG also works.', 'lami' ); ?>
            </p>
        </div>
        <?php
    }

    /** Save menu item meta */
    public function save( $menu_id, $menu_item_db_id, $args ) {
        if ( isset( $_POST['menu-item-ctlh_icon_id'][ $menu_item_db_id ] ) ) {
            $val = absint( $_POST['menu-item-ctlh_icon_id'][ $menu_item_db_id ] );
            if ( $val ) {
                update_post_meta( $menu_item_db_id, self::META_KEY, $val );
            } else {
                delete_post_meta( $menu_item_db_id, self::META_KEY );
            }
        }
    }

    /** Admin JS */
    public function admin_assets( $hook ) {
        if ( $hook !== 'nav-menus.php' ) return;

        // Ensure jQuery and media scripts are loaded
        wp_enqueue_script( 'jquery' );
        wp_enqueue_media();

        // Register our script with proper dependencies
        wp_register_script( 'lami-menu-icons-admin', false, ['jquery', 'media-upload', 'media-views'], LAMI_VERSION, true );
        wp_enqueue_script( 'lami-menu-icons-admin' );

        $js = <<<'JS'
        (function($){
            'use strict';

            console.log('Legal Aid Menu Icons: Script loaded');

            function wire($wrap){
                var $id = $wrap.find('.edit-menu-item-ctlh-icon-id');
                var $preview = $wrap.find('.ctlh-icon-preview');

                $wrap.on('click', '.ctlh-icon-select', function(e){
                    console.log('Legal Aid Menu Icons: Select button clicked');
                    e.preventDefault();
                    e.stopPropagation();

                    if (typeof wp.media === 'undefined') {
                        alert('WordPress Media Library is not available. Please refresh the page.');
                        return;
                    }

                    var frame = wp.media({
                        title: 'Select Menu Icon',
                        button: { text: 'Use this icon' },
                        library: { type: ['image','image/svg+xml'] },
                        multiple: false
                    });

                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        $id.val(att.id || '');
                        var html = '<img src="'+att.url+'" style="width:24px;height:24px;object-fit:contain;display:inline-block;vertical-align:middle;border:1px solid #ddd;border-radius:4px;background:#fff" />';
                        $preview.html(html);
                        $wrap.find('.ctlh-icon-clear').prop('disabled', false);
                    });

                    frame.open();
                    return false;
                });

                $wrap.on('click', '.ctlh-icon-clear', function(e){
                    console.log('Legal Aid Menu Icons: Clear button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    $id.val('');
                    $preview.html('<em style="color:#666;">No icon selected</em>');
                    $(this).prop('disabled', true);
                    return false;
                });
            }

            $(document).ready(function(){
                console.log('Legal Aid Menu Icons: Document ready, wiring ' + $('.field-ctlh-menu-icon').length + ' icon fields');
                $('.field-ctlh-menu-icon').each(function(){ wire($(this)); });
                $(document).on('menu-item-added', function(e, newItem){
                    $(newItem).find('.field-ctlh-menu-icon').each(function(){ wire($(this)); });
                });
            });
        })(jQuery);
        JS;

        wp_add_inline_script( 'lami-menu-icons-admin', $js );
    }

    /**
     * FRONTEND (classic menus): add CSS var + helper class on the anchor.
     */
    public function link_style_var( $atts, $item, $args, $depth ) {
        $url = $this->resolve_icon_url_for_item( $item );
        if ( ! $url ) return $atts;

        // If already present, don't add again.
        if ( isset( $atts['style'] ) && stripos( $atts['style'], '--icon-url' ) !== false ) {
            $atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' has-menu-icon' : 'has-menu-icon';
            return $atts;
        }

        $style  = isset( $atts['style'] ) ? rtrim( $atts['style'], '; ' ) . '; ' : '';
        $style .= '--icon-url: url(\'' . esc_url( $url ) . '\')';
        $atts['style'] = $style;
        $atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' has-menu-icon' : 'has-menu-icon';
        return $atts;
    }

    /**
     * FRONTEND (custom walkers like Elementor): inject var/class into final <a> HTML, once.
     */
    public function inject_in_walker_output( $item_output, $item, $depth, $args ) {
        $url = $this->resolve_icon_url_for_item( $item );
        if ( ! $url ) return $item_output;

        // Skip if already present.
        if ( preg_match( '/<a\b[^>]*style=("|\')[^"\']*--icon-url[^"\']*\1/i', $item_output ) ) {
            return $item_output;
        }

        return $this->inject_style_on_first_anchor( $item_output, $url );
    }

    /**
     * FRONTEND (block Navigation): add CSS var + class to the first <a> inside the block HTML, once.
     */
    public function render_block_nav_icons( $content, $block ) {
        if ( empty( $block['blockName'] ) ) return $content;

        if ( in_array( $block['blockName'], array( 'core/navigation-link', 'core/navigation-submenu' ), true ) ) {
            // Skip if already present.
            if ( preg_match( '/<a\b[^>]*style=("|\')[^"\']*--icon-url[^"\']*\1/i', $content ) ) {
                return $content;
            }
            $menu_item_id = isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0;
            if ( $menu_item_id ) {
                $wp_item = wp_setup_nav_menu_item( get_post( $menu_item_id ) );
                if ( $wp_item ) {
                    $url = $this->resolve_icon_url_for_item( $wp_item );
                    if ( $url ) {
                        return $this->inject_style_on_first_anchor( $content, $url );
                    }
                }
            }
        }
        return $content;
    }

    /** Resolve icon URL for a menu item */
    private function resolve_icon_url_for_item( $item ) {
        // Debug logging (temporarily disabled)
        // error_log( 'Legal Aid Menu Icons: Resolving icon for item ID ' . $item->ID );
        
        // 1) Manual icon (always highest priority)
        $attachment_id = (int) get_post_meta( $item->ID, self::META_KEY, true );
        if ( $attachment_id ) {
            $url = wp_get_attachment_url( $attachment_id );
            if ( $url ) {
                // error_log( 'Legal Aid Menu Icons: Found manual icon - ' . $url );
                return $url;
            }
        }

        // 2) NSMI integration: Page mapped to NSMI term (optional)
        if ( isset( $item->object, $item->object_id ) && $item->object === 'page' ) {
            $term_slug = get_post_meta( (int) $item->object_id, '_nsmi_issue', true );
            if ( $term_slug ) {
                $term = get_term_by( 'slug', $term_slug, 'nsmi_category' );
                if ( $term && ! is_wp_error( $term ) && function_exists( 'get_nsmi_icon_url' ) ) {
                    $url = get_nsmi_icon_url( $term->term_id );
                    if ( $url ) return $url;
                }
            }
        }

        // 3) NSMI integration: Direct NSMI category (optional)
        if ( isset( $item->object, $item->object_id ) && $item->object === 'nsmi_category' ) {
            $image_id = (int) get_term_meta( (int) $item->object_id, 'nsmi_icon_id', true );
            if ( $image_id ) {
                $url = wp_get_attachment_url( $image_id );
                if ( $url ) return $url;
            }
        }

        return '';
    }

    /** Inject --icon-url + class=has-menu-icon on the first <a> in given HTML */
    private function inject_style_on_first_anchor( $html, $icon_url ) {
        if ( ! preg_match( '/<a\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE ) ) return $html;
        $tag    = $m[0][0];
        $pos    = $m[0][1];
        $before = substr( $html, 0, $pos );
        $after  = substr( $html, $pos + strlen( $tag ) );

        // style (append or create)
        if ( preg_match( '/\sstyle=("|\')(.*?)\1/i', $tag, $sm ) ) {
            $full  = $sm[0];
            $quote = $sm[1];
            $val   = $sm[2];
            $new   = ' style=' . $quote . rtrim( $val, '; ' ) . '; --icon-url: url(\'' . esc_url( $icon_url ) . '\')' . $quote;
            $tag   = str_replace( $full, $new, $tag );
        } else {
            $tag = rtrim( substr( $tag, 0, -1 ) ) . ' style="--icon-url: url(\'' . esc_url( $icon_url ) . '\')" >';
        }

        // class (append or create)
        if ( preg_match( '/\sclass=("|\')(.*?)\1/i', $tag, $cm ) ) {
            $full  = $cm[0];
            $quote = $cm[1];
            $val   = $cm[2];
            if ( strpos( $val, 'has-menu-icon' ) === false ) {
                $new = ' class=' . $quote . trim( $val . ' has-menu-icon' ) . $quote;
                $tag = str_replace( $full, $new, $tag );
            }
        } else {
            $tag = rtrim( substr( $tag, 0, -1 ) ) . ' class="has-menu-icon">';
        }

        return $before . $tag . $after;
    }

    /** Allow SVG uploads */
    public function allow_svg_mime( $mimes ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    /** Add admin menu */
    public function admin_menu() {
        add_theme_page(
            'Menu Icon Styles',
            'Menu Icon Styles', 
            'manage_options',
            'menu-icon-styles',
            [ $this, 'admin_page' ]
        );
    }

    /** Initialize admin settings */
    public function admin_init() {
        register_setting( 'menu_icon_styles', 'lami_icon_styles' );
        
        // Handle form submissions
        if ( isset( $_POST['lami_add_style'] ) && wp_verify_nonce( $_POST['lami_nonce'], 'lami_admin' ) ) {
            $this->add_style_set();
            wp_redirect( admin_url( 'themes.php?page=menu-icon-styles&added=1' ) );
            exit;
        }
        
        if ( isset( $_POST['lami_edit_style'] ) && wp_verify_nonce( $_POST['lami_nonce'], 'lami_admin' ) ) {
            $this->edit_style_set( $_POST['edit_id'], $_POST );
            wp_redirect( admin_url( 'themes.php?page=menu-icon-styles&updated=1' ) );
            exit;
        }
        
        if ( isset( $_POST['lami_delete_style'] ) && wp_verify_nonce( $_POST['lami_nonce'], 'lami_admin' ) ) {
            $this->delete_style_set( $_POST['delete_id'] );
            wp_redirect( admin_url( 'themes.php?page=menu-icon-styles&deleted=1' ) );
            exit;
        }
    }

    /** Admin page HTML */
    public function admin_page() {
        $styles = get_option( 'lami_icon_styles', [] );
        $editing_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : null;
        $editing_style = ( $editing_id !== null && isset( $styles[ $editing_id ] ) ) ? $styles[ $editing_id ] : null;
        
        // Display admin notices based on URL parameters
        if ( isset( $_GET['added'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Style set added successfully!</p></div>';
        }
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Style set updated successfully!</p></div>';
        }
        if ( isset( $_GET['deleted'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Style set deleted successfully!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Menu Icon Styles</h1>
            <p>Create style sets to control how menu icons appear in different locations.</p>

            <!-- Existing Style Sets -->
            <?php if ( ! empty( $styles ) ) : ?>
                <h2>Current Style Sets</h2>
                <?php foreach ( $styles as $id => $style ) : ?>
                    <div class="postbox" style="margin-bottom: 20px;">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php echo esc_html( $style['name'] ); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th>CSS Selector:</th>
                                    <td><code><?php echo esc_html( $style['selector'] ); ?></code></td>
                                </tr>
                                <tr>
                                    <th>Icon Color:</th>
                                    <td>
                                        <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo esc_attr( $style['color'] ); ?>; border: 1px solid #ccc; vertical-align: middle;"></span>
                                        <?php echo esc_html( $style['color'] ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Icon Size:</th>
                                    <td><?php echo esc_html( $style['size'] ); ?>px</td>
                                </tr>
                                <tr>
                                    <th>Text Padding:</th>
                                    <td><?php echo esc_html( $style['padding'] ); ?>px</td>
                                </tr>
                            </table>
                            <div style="margin-top: 10px;">
                                <a href="<?php echo esc_url( add_query_arg( 'edit', $id ) ); ?>" class="button button-primary">Edit Style Set</a>
                                <form method="post" style="display: inline-block; margin-left: 10px;">
                                    <?php wp_nonce_field( 'lami_admin', 'lami_nonce' ); ?>
                                    <input type="hidden" name="delete_id" value="<?php echo esc_attr( $id ); ?>">
                                    <button type="submit" name="lami_delete_style" class="button button-secondary" onclick="return confirm('Delete this style set?')">Delete Style Set</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- NSMI Integration -->
            <?php if ( taxonomy_exists( 'nsmi_category' ) && function_exists( 'get_nsmi_icon_url' ) ) : ?>
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">NSMI Integration</h2>
                    </div>
                    <div class="inside">
                        <p>Menu icons automatically use NSMI category icons when no manual icon is set.</p>
                        <p>
                            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=nsmi_category' ) ); ?>" class="button button-secondary">
                                Manage NSMI Category Icons
                            </a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Style Set -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">
                        <?php echo $editing_style ? 'Edit Style Set' : 'Add New Style Set'; ?>
                        <?php if ( $editing_style ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'themes.php?page=menu-icon-styles' ) ); ?>" class="button button-secondary" style="margin-left: 10px; vertical-align: middle;">Cancel</a>
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="inside">
                    <form method="post">
                        <?php wp_nonce_field( 'lami_admin', 'lami_nonce' ); ?>
                        <?php if ( $editing_style ) : ?>
                            <input type="hidden" name="edit_id" value="<?php echo esc_attr( $editing_id ); ?>">
                        <?php endif; ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="style_name">Name</label>
                                </th>
                                <td>
                                    <input type="text" id="style_name" name="style_name" class="regular-text" 
                                           value="<?php echo esc_attr( $editing_style ? $editing_style['name'] : '' ); ?>"
                                           placeholder="e.g., Header Menu Icons" required>
                                    <p class="description">Descriptive name for this style set</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="style_selector">CSS Selector</label>
                                </th>
                                <td>
                                    <input type="text" id="style_selector" name="style_selector" class="regular-text" 
                                           value="<?php echo esc_attr( $editing_style ? $editing_style['selector'] : '' ); ?>"
                                           placeholder="main-nav, header-menu, or .main-nav, #header-menu" required>
                                    <p class="description">
                                        <strong>Class names:</strong> Enter with or without the dot (e.g., "main-nav" or ".main-nav")<br>
                                        <strong>IDs:</strong> Enter with or without the hash (e.g., "header-menu" or "#header-menu")<br>
                                        <strong>Multiple selectors:</strong> Separate with commas
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="style_color">Icon Color</label>
                                </th>
                                <td>
                                    <input type="color" id="style_color" name="style_color" 
                                           value="<?php echo esc_attr( $editing_style ? $editing_style['color'] : '#ffffff' ); ?>">
                                    <p class="description">Color for the menu icons</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="style_size">Icon Size</label>
                                </th>
                                <td>
                                    <input type="number" id="style_size" name="style_size" 
                                           value="<?php echo esc_attr( $editing_style ? $editing_style['size'] : '20' ); ?>"
                                           min="8" max="100" style="width: 80px;">
                                    <span>px</span>
                                    <p class="description">Size of the icons in pixels</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="style_padding">Text Padding</label>
                                </th>
                                <td>
                                    <input type="number" id="style_padding" name="style_padding" 
                                           value="<?php echo esc_attr( $editing_style ? $editing_style['padding'] : '50' ); ?>"
                                           min="20" max="200" style="width: 80px;">
                                    <span>px</span>
                                    <p class="description">Space between left edge and menu text</p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <?php if ( $editing_style ) : ?>
                                <button type="submit" name="lami_edit_style" class="button button-primary">Update Style Set</button>
                            <?php else : ?>
                                <button type="submit" name="lami_add_style" class="button button-primary">Add Style Set</button>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Instructions -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">How to Use</h2>
                </div>
                <div class="inside">
                    <h3>For Elementor Users:</h3>
                    <ol>
                        <li><strong>Create a style set</strong> above with your desired settings</li>
                        <li><strong>In Elementor:</strong> Edit your Nav Menu widget</li>
                        <li><strong>Go to Advanced tab → CSS ID</strong> and enter an ID (e.g., <code>header-menu</code>)</li>
                        <li><strong>Use that ID as the CSS selector</strong> in your style set: <code>#header-menu</code></li>
                        <li><strong>Update/Publish</strong> your page - icons will use the new styling</li>
                    </ol>
                    
                    <h3>Common CSS Selectors:</h3>
                    <ul>
                        <li><code>#header-menu</code> - If you set CSS ID to "header-menu" in Elementor</li>
                        <li><code>#footer-menu</code> - If you set CSS ID to "footer-menu" in Elementor</li>
                        <li><code>.my-custom-menu</code> - If you add CSS class "my-custom-menu" in Elementor</li>
                    </ul>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin-top: 15px;">
                        <strong>💡 Pro Tip:</strong> Use different IDs for different menus (header, footer, sidebar) so you can style them separately with different colors and sizes!
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /** Process CSS selector input to add appropriate prefixes */
    private function process_selector( $input ) {
        $selectors = array_map( 'trim', explode( ',', $input ) );
        $processed = [];
        
        foreach ( $selectors as $selector ) {
            // Skip if already has proper CSS prefix
            if ( strpos( $selector, '.' ) === 0 || strpos( $selector, '#' ) === 0 || 
                 strpos( $selector, '[' ) !== false || strpos( $selector, ':' ) !== false ||
                 strpos( $selector, ' ' ) !== false ) {
                $processed[] = $selector;
                continue;
            }
            
            // Auto-detect common ID patterns and add #
            if ( preg_match( '/^(header|footer|nav|menu|main|sidebar|content|wrapper|container)/i', $selector ) ||
                 strpos( $selector, 'menu' ) !== false ) {
                $processed[] = '#' . $selector;
            } else {
                // Default to class selector
                $processed[] = '.' . $selector;
            }
        }
        
        return implode( ', ', $processed );
    }

    /** Add new style set */
    private function add_style_set() {
        $styles = get_option( 'lami_icon_styles', [] );
        
        $new_style = [
            'name' => sanitize_text_field( $_POST['style_name'] ),
            'selector' => $this->process_selector( sanitize_text_field( $_POST['style_selector'] ) ),
            'color' => sanitize_hex_color( $_POST['style_color'] ),
            'size' => absint( $_POST['style_size'] ),
            'padding' => absint( $_POST['style_padding'] )
        ];
        
        $styles[] = $new_style;
        update_option( 'lami_icon_styles', $styles );
    }

    /** Delete style set */
    private function delete_style_set( $id ) {
        $styles = get_option( 'lami_icon_styles', [] );
        
        if ( isset( $styles[ $id ] ) ) {
            unset( $styles[ $id ] );
            $styles = array_values( $styles ); // Re-index array
            update_option( 'lami_icon_styles', $styles );
        }
    }

    /** Edit existing style set */
    private function edit_style_set( $id, $data ) {
        $styles = get_option( 'lami_icon_styles', [] );
        
        if ( isset( $styles[ $id ] ) ) {
            $styles[ $id ] = [
                'name' => sanitize_text_field( $data['style_name'] ),
                'selector' => $this->process_selector( sanitize_text_field( $data['style_selector'] ) ),
                'color' => sanitize_hex_color( $data['style_color'] ),
                'size' => absint( $data['style_size'] ),
                'padding' => absint( $data['style_padding'] )
            ];
            
            update_option( 'lami_icon_styles', $styles );
        }
    }

    /** Output dynamic CSS based on style sets */
    public function output_dynamic_css() {
        $styles = get_option( 'lami_icon_styles', [] );
        
        if ( empty( $styles ) ) {
            // Output essential base styles even without admin configuration
            echo "<style id='lami-base-styles'>\n";
            echo "/* Legal Aid Menu Icons - Base Styles */\n";
            echo "a[style*=\"--icon-url\"] {\n";
            echo "    position: relative;\n";
            echo "    padding-left: 50px; /* default padding */\n";
            echo "}\n";
            echo "a[style*=\"--icon-url\"]::before {\n";
            echo "    content: \"\";\n";
            echo "    position: absolute;\n";
            echo "    left: 8px;\n";
            echo "    top: 50%;\n";
            echo "    transform: translateY(-50%);\n";
            echo "    width: 20px;\n";
            echo "    height: 20px;\n";
            echo "    mask: var(--icon-url) no-repeat center / contain;\n";
            echo "    -webkit-mask: var(--icon-url) no-repeat center / contain;\n";
            echo "    background-color: currentColor;\n";
            echo "    opacity: 1;\n";
            echo "}\n";
            echo "</style>\n";
            return;
        }
        
        echo "<style id='lami-dynamic-styles'>\n";
        echo "/* Legal Aid Menu Icons - Base Styles */\n";
        echo "a[style*=\"--icon-url\"] {\n";
        echo "    position: relative;\n";
        echo "}\n";
        echo "a[style*=\"--icon-url\"]::before {\n";
        echo "    content: \"\";\n";
        echo "    position: absolute;\n";
        echo "    top: 50%;\n";
        echo "    transform: translateY(-50%);\n";
        echo "    mask: var(--icon-url) no-repeat center / contain;\n";
        echo "    -webkit-mask: var(--icon-url) no-repeat center / contain;\n";
        echo "    opacity: 1;\n";
        echo "    flex-shrink: 0;\n";
        echo "}\n\n";
        
        echo "/* Dynamic Style Sets */\n";
        foreach ( $styles as $style ) {
            $selector = esc_attr( $style['selector'] );
            $color = esc_attr( $style['color'] );
            $size = absint( $style['size'] );
            $padding = absint( $style['padding'] );
            $icon_left = round( $padding * 0.15 ); // Icon positioned at ~15% of padding
            
            echo "/* {$style['name']} */\n";
            echo "{$selector} a[style*=\"--icon-url\"].has-menu-icon,\n";
            echo "{$selector} .has-menu-icon[style*=\"--icon-url\"],\n";
            echo "{$selector} a.has-menu-icon,\n";
            echo "{$selector} .has-menu-icon {\n";  
            echo "    padding-left: {$padding}px !important;\n";
            echo "}\n";
            echo "{$selector} a[style*=\"--icon-url\"].has-menu-icon::before,\n";
            echo "{$selector} .has-menu-icon[style*=\"--icon-url\"]::before,\n";
            echo "{$selector} a.has-menu-icon::before,\n";
            echo "{$selector} .has-menu-icon::before,\n";
            echo "{$selector} .elementor-item.has-menu-icon::before {\n";
            echo "    content: \"\" !important;\n";
            echo "    position: absolute !important;\n";
            echo "    top: 50% !important;\n";
            echo "    transform: translateY(-50%) !important;\n";
            echo "    mask: var(--icon-url) no-repeat center / contain !important;\n";
            echo "    -webkit-mask: var(--icon-url) no-repeat center / contain !important;\n";
            echo "    background-color: {$color} !important;\n";
            echo "    color: {$color} !important;\n";
            echo "    width: {$size}px !important;\n";
            echo "    height: {$size}px !important;\n";
            echo "    left: {$icon_left}px !important;\n";
            echo "    opacity: 1 !important;\n";
            echo "    flex-shrink: 0 !important;\n";
            echo "}\n";
            // Override any hover effects that might be interfering
            echo "{$selector} .elementor-item.has-menu-icon:hover::before {\n";
            echo "    background-color: {$color} !important;\n";
            echo "    color: {$color} !important;\n";
            echo "}\n\n";
        }
        
        echo "</style>\n";
    }
}

endif;

// Initialize the plugin
new CTLawHelp_Menu_Icons();

/**
 * Optional NSMI Integration
 * 
 * The following functions are only active if Legal Aid Articles plugin is present.
 * They handle NSMI taxonomy-specific behaviors like redirects and URL replacement.
 */

// Redirect NSMI taxonomy archives to their Landing Pages (optional)
if ( function_exists( 'get_nsmi_icon_url' ) ) {
    add_action( 'template_redirect', function() {
        if ( is_tax( 'nsmi_category' ) ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) ) {
                $landing = get_posts([
                    'post_type'   => 'page',
                    'meta_key'    => '_nsmi_issue',
                    'meta_value'  => $term->slug,
                    'numberposts' => 1
                ]);
                if ( $landing ) {
                    wp_safe_redirect( get_permalink( $landing[0]->ID ), 301 );
                    exit;
                }
            }
        }
    });

    // Replace NSMI category menu item URLs with their Landing Page URLs (optional)
    add_filter( 'wp_nav_menu_objects', function( $items ) {
        foreach ( $items as &$item ) {
            if ( $item->object === 'nsmi_category' ) {
                $term = get_term( (int) $item->object_id, 'nsmi_category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $landing = get_posts([
                        'post_type'   => 'page',
                        'meta_key'    => '_nsmi_issue',
                        'meta_value'  => $term->slug,
                        'numberposts' => 1
                    ]);
                    if ( $landing ) {
                        $item->url = get_permalink( $landing[0]->ID );
                    }
                }
            }
        }
        return $items;
    });
}