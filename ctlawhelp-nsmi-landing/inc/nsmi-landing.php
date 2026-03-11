<?php
/**
 * NSMI Landing – Pin metabox only (accordion moved to dedicated file)
 */
if ( ! defined('ABSPATH') ) exit;

/**
 * Metabox: Pin to Accordion + Pin Order (on legal_article)
 */
add_action('add_meta_boxes', function(){
  add_meta_box('laa_pin_mb', __('Accordion Pinning','laa'), function($post){
    wp_nonce_field('laa_pin_mb','laa_pin_mb_n');
    $pin = get_post_meta($post->ID, '_laa_pin', true);
    $ord = get_post_meta($post->ID, '_laa_pin_order', true);
    ?>
    <p>
      <label><input type="checkbox" name="_laa_pin" value="1" <?php checked($pin,'1'); ?>>
        <?php _e('Pin this article to the top of its NSMI sections','laa'); ?>
      </label>
    </p>
    <p>
      <label for="_laa_pin_order"><strong><?php _e('Pin Order','laa'); ?></strong></label><br>
      <input type="number" min="0" step="1" style="width:120px" id="_laa_pin_order" name="_laa_pin_order" value="<?php echo esc_attr($ord !== '' ? $ord : '0'); ?>">
      <span class="description"><?php _e('Lower numbers appear first (0,1,2…).','laa'); ?></span>
    </p>
    <p class="description"><?php _e('Pinned articles appear first within every NSMI term they belong to.','laa'); ?></p>
    <?php
  }, 'legal_article', 'side', 'high');
});

add_action('save_post_legal_article', function($post_id){
  if ( ! isset($_POST['laa_pin_mb_n']) || ! wp_verify_nonce($_POST['laa_pin_mb_n'], 'laa_pin_mb') ) return;
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
  if ( ! current_user_can('edit_post', $post_id) ) return;

  $pin = isset($_POST['_laa_pin']) ? '1' : '0';
  update_post_meta($post_id, '_laa_pin', $pin);

  $ord = isset($_POST['_laa_pin_order']) ? intval($_POST['_laa_pin_order']) : 0;
  update_post_meta($post_id, '_laa_pin_order', $ord);
});
