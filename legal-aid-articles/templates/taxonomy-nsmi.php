<?php
get_header();

$term = get_queried_object();
?>

<div class="wrap">
    <h1><?php echo esc_html($term->name); ?></h1>
    <?php if (!empty($term->description)): ?>
        <div class="taxonomy-description"><?php echo wpautop($term->description); ?></div>
    <?php endif; ?>

    <?php
    $subterms = get_terms([
        'taxonomy'   => 'nsmi_category',
        'hide_empty' => false,
        'parent'     => $term->term_id,
    ]);

    if (!empty($subterms) && !is_wp_error($subterms)): ?>
        <div class="nsmi-subterms">
            <?php foreach ($subterms as $subterm): ?>
                <div class="nsmi-subterm">
                    <h2>
                        <a href="<?php echo esc_url(get_term_link($subterm)); ?>">
                            <?php echo esc_html($subterm->name); ?>
                        </a>
                    </h2>
                    <?php
                    $sub_posts = new WP_Query([
                        'post_type'      => 'legal_article',
                        'posts_per_page' => 5,
                        'tax_query'      => [[
                            'taxonomy' => 'nsmi_category',
                            'field'    => 'term_id',
                            'terms'    => $subterm->term_id,
                        ]],
                    ]);
                    if ($sub_posts->have_posts()):
                        echo '<ul>';
                        while ($sub_posts->have_posts()): $sub_posts->the_post();
                            echo '<li><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></li>';
                        endwhile;
                        echo '</ul>';
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $posts = new WP_Query([
        'post_type'      => 'legal_article',
        'posts_per_page' => 10,
        'tax_query'      => [[
            'taxonomy' => 'nsmi_category',
            'field'    => 'term_id',
            'terms'    => $term->term_id,
        ]],
    ]);

    if ($posts->have_posts()):
        echo '<h2>Articles</h2><ul>';
        while ($posts->have_posts()): $posts->the_post();
            echo '<li><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></li>';
        endwhile;
        echo '</ul>';
        wp_reset_postdata();
    endif;
    ?>
</div>

<?php
get_footer();
