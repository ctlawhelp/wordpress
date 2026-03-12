<?php
/**
 * Template: Single NSMI Landing Page
 * Minimal starter template for nsmi_landing CPT
 * Ensures global $post is set and Hello Elementor layout is preserved
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<main id="primary" class="site-main">
<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            <div class="entry-content">
                <p><?php esc_html_e('NSMI Landing Page placeholder content.', 'ctlawhelp-nsmi-landing'); ?></p>
            </div>
        </article>
        <?php
    endwhile;
endif;
?>
</main>
<?php
get_footer();
