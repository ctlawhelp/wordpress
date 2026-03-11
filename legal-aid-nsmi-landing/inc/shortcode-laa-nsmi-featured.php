<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [laa_nsmi_featured term="" include_children="1" limit="3" show_images="1" image_size="medium" show_excerpts="0" excerpt_words="24" heading="Featured" class=""]
 * - term: slug of the parent NSMI term; if blank, resolves from the page's _nsmi_issue
 * - include_children: 1 to pull pins from child terms too
 * - limit: how many pinned posts to show
 * - show_images: 1 to show post thumbnail if present
 * - image_size: WP image size for thumbnails (e.g., thumbnail, medium, medium_large)
 * - show_excerpts: 1 to show short excerpt under title
 * - excerpt_words: word limit for the excerpt
 * - heading: section heading text
 * - class: extra CSS class on wrapper
 */
function laa_nsmi_featured_sc($atts) {
  $a = shortcode_atts(array(
    'term'             => '',
    'include_children' => '1',
    'limit'            => '3',
    'show_images'      => '1',
    'image_size'       => 'medium',
    'show_excerpts'    => '0',
    'excerpt_words'    => '24',
    'heading'          => 'Featured',
    'class'            => '',
  ), $atts, 'laa_nsmi_featured');

  // Resolve parent slug (fallback to page meta)
  $page_id = get_the_ID() ?: get_queried_object_id();
  $parent_slug = sanitize_title($a['term']);
  if (!$parent_slug && $page_id) {
    $maybe = get_post_meta($page_id, '_nsmi_issue', true);
    if ($maybe) { $parent_slug = sanitize_title($maybe); }
  }
  if (!$parent_slug) return '';

  $parent = get_term_by('slug', $parent_slug, 'nsmi_category');
  if (!$parent || is_wp_error($parent)) return '';

  $limit            = max(1, (int)$a['limit']);
  $include_children = ($a['include_children'] !== '0');
  $show_images      = ($a['show_images'] !== '0');
  $show_excerpts    = ($a['show_excerpts'] !== '0');
  $excerpt_words    = max(5, (int)$a['excerpt_words']);
  $image_size       = sanitize_key($a['image_size']);
  $wrap_class       = sanitize_html_class($a['class']);
  $heading          = trim($a['heading']);

  // Fetch pinned posts for this term (optionally including children)
  $q = new WP_Query(array(
    'post_type'      => 'legal_article',
    'post_status'    => 'publish',
    'posts_per_page' => $limit,
    'no_found_rows'  => true,
    'meta_query'     => array(array('key' => '_laa_pin', 'value' => '1', 'compare' => '=')),
    'orderby'        => array('meta_value_num' => 'ASC', 'date' => 'DESC'),
    'meta_key'       => '_laa_pin_order',
    'tax_query'      => array(array(
      'taxonomy'         => 'nsmi_category',
      'field'            => 'term_id',
      'terms'            => $parent->term_id,
      'include_children' => $include_children,
    )),
  ));
  if (!$q->have_posts()) return '';

  ob_start();
  ?>
  <section class="laa-nsmi-featured <?php echo esc_attr($wrap_class); ?>" data-parent="<?php echo esc_attr($parent->slug); ?>">
    <?php if ($heading !== ''): ?>
      <h2 class="laa-featured-heading"><?php echo esc_html($heading); ?></h2>
    <?php endif; ?>
    <div class="laa-featured-grid">
      <?php while ($q->have_posts()): $q->the_post();
        $pid   = get_the_ID();
        $title = get_the_title($pid);
        $url   = get_permalink($pid);
        $thumb = $show_images ? get_the_post_thumbnail($pid, $image_size, array('class'=>'laa-featured-thumb')) : '';
        $raw   = '';
        if ($show_excerpts) {
          $raw = get_post_field('post_excerpt', $pid);
          if (!$raw) { $raw = wp_strip_all_tags(get_post_field('post_content', $pid)); }
          $words = preg_split('/\s+/', trim($raw));
          if (count($words) > $excerpt_words) { $raw = implode(' ', array_slice($words, 0, $excerpt_words)) . '…'; }
        }
        ?>
        <article class="laa-featured-card">
          <?php if ($thumb): ?><a class="laa-featured-media" href="<?php echo esc_url($url); ?>"><?php echo $thumb; ?></a><?php endif; ?>
          <div class="laa-featured-body">
            <h3 class="laa-featured-title"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a></h3>
            <?php if ($show_excerpts && $raw): ?>
              <p class="laa-featured-excerpt"><?php echo esc_html($raw); ?></p>
            <?php endif; ?>
          </div>
        </article>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
}
add_shortcode('laa_nsmi_featured', 'laa_nsmi_featured_sc');
