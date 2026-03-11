<?php
/**
 * Plugin Name: CTLawHelp Menu Icons
 * Description: Add an image picker to each nav menu item and output it as a CSS variable (--icon-url) on the link element.
 * Version:     1.4.1
 * Author:      CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'CTLawHelp_Menu_Icons' ) ) :

// Optional UI CSS. Falls back to this plugin's assets if LAA_URL isn't defined.
add_action( 'wp_enqueue_scripts', function() {
    $base = defined('LAA_URL') ? LAA_URL : plugin_dir_url(__FILE__);
    wp_enqueue_style( 'ctlh-menu-icons', $base . 'assets/css/menu-icons.css', [], '1.0.0' );
});

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
                <?php esc_html_e( 'Menu Icon', 'ctlh' ); ?>
            </label>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <input type="hidden"
                    class="widefat code edit-menu-item-ctlh-icon-id"
                    id="edit-menu-item-ctlh-icon-<?php echo esc_attr( $item_id ); ?>"
                    name="menu-item-ctlh_icon_id[<?php echo esc_attr( $item_id ); ?>]"
                    value="<?php echo esc_attr( $attachment_id ); ?>" />

                <button type="button" class="button button-secondary ctlh-icon-select" data-item="<?php echo esc_attr( $item_id ); ?>">
                    <?php echo $attachment_id ? esc_html__( 'Change Icon', 'ctlh' ) : esc_html__( 'Select Icon', 'ctlh' ); ?>
                </button>

                <button type="button" class="button ctlh-icon-clear" data-item="<?php echo esc_attr( $item_id ); ?>" <?php disabled( ! $attachment_id ); ?>>
                    <?php esc_html_e( 'Clear', 'ctlh' ); ?>
                </button>

                <span class="ctlh-icon-preview" id="ctlh-icon-preview-<?php echo esc_attr( $item_id ); ?>">
                    <?php echo $thumb ? $thumb : '<em style="color:#666;">' . esc_html__( 'No icon selected', 'ctlh' ) . '</em>'; ?>
                </span>

                <?php if ( $url ) : ?>
                    <code style="opacity:0.7;"><?php echo esc_html( $url ); ?></code>
                <?php endif; ?>
            </div>

            <p class="description" style="margin-top:6px;">
                <?php esc_html_e( 'Recommended: SVG silhouettes (single-color). PNG also works.', 'ctlh' ); ?>
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
        wp_register_script( 'ctlh-menu-icons-admin', false, ['jquery', 'media-upload', 'media-views'], '1.0.0', true );
        wp_enqueue_script( 'ctlh-menu-icons-admin' );

        $js = <<<'JS'
        (function($){
            'use strict';

            console.log('CTLawHelp Menu Icons: Script loaded');

            function wire($wrap){
                var $id = $wrap.find('.edit-menu-item-ctlh-icon-id');
                var $preview = $wrap.find('.ctlh-icon-preview');

                $wrap.on('click', '.ctlh-icon-select', function(e){
                    console.log('CTLawHelp Menu Icons: Select button clicked');
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
                    console.log('CTLawHelp Menu Icons: Clear button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    $id.val('');
                    $preview.html('<em style="color:#666;">No icon selected</em>');
                    $(this).prop('disabled', true);
                    return false;
                });
            }

            $(document).ready(function(){
                console.log('CTLawHelp Menu Icons: Document ready, wiring ' + $('.field-ctlh-menu-icon').length + ' icon fields');
                $('.field-ctlh-menu-icon').each(function(){ wire($(this)); });
                $(document).on('menu-item-added', function(e, newItem){
                    $(newItem).find('.field-ctlh-menu-icon').each(function(){ wire($(this)); });
                });
            });
        })(jQuery);
        JS;

        wp_add_inline_script( 'ctlh-menu-icons-admin', $js );
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
        // 1) Manual icon
        $attachment_id = (int) get_post_meta( $item->ID, self::META_KEY, true );
        if ( $attachment_id ) {
            $url = wp_get_attachment_url( $attachment_id );
            if ( $url ) return $url;
        }

        // 2) Page mapped to NSMI term
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

        // 3) Direct NSMI category
        if ( isset( $item->object, $item->object_id ) && $item->object === 'nsmi_category' ) {
            $image_id = (int) get_term_meta( (int) $item->object_id, 'nsmi_icon_id', true );
            if ( $image_id ) {
                $url = wp_get_attachment_url( $image_id );
                if ( $url ) return $url;
            }
        }

        return '';
    }

    /** Inject --icon-url + class=has-menu-icon on the first <a> in given HTML (single quotes inside url()). */
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
}

endif;

new CTLawHelp_Menu_Icons();

/** Redirect NSMI taxonomy archives to their Landing Pages. */
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

/** Replace NSMI category menu item URLs with their Landing Page URLs. */
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
