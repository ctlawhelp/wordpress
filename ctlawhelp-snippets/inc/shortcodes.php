<?php
/**
 * Legal Aid Snippets - Shortcode System
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function las_register_shortcodes() {
	add_shortcode( 'snippet', 'las_render_snippet_shortcode' );
}

// Ensure the snippet shortcode is registered on init even if the
// main plugin class fails to call las_register_shortcodes().
add_action( 'init', 'las_register_shortcodes' );

/**
 * Render snippet shortcode
 * Usage: [snippet id="123"] or [snippet slug="contact-info"]
 */
function las_render_snippet_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'id'    => '',
		'slug'  => '',
		'class' => '',
	), $atts, 'snippet' );

	// Determine which snippet to load
	$snippet_post = null;
	
	if ( ! empty( $atts['id'] ) ) {
		// Load by ID
		$snippet_post = get_post( intval( $atts['id'] ) );
	} elseif ( ! empty( $atts['slug'] ) ) {
		// Load by slug
		$snippet_post = get_page_by_path( $atts['slug'], OBJECT, 'legal_snippet' );
	}

	// Validate snippet exists and is published
	if ( ! $snippet_post || $snippet_post->post_type !== 'legal_snippet' || $snippet_post->post_status !== 'publish' ) {
		// Return nothing in production, error message for admins
		if ( current_user_can( 'edit_posts' ) ) {
			return '<div class="snippet-error" style="background: #ffebee; border: 1px solid #f44336; padding: 10px; color: #c62828;">
				<strong>Snippet Error:</strong> Could not find snippet with ' . 
				( ! empty( $atts['id'] ) ? 'ID "' . esc_html( $atts['id'] ) . '"' : 'slug "' . esc_html( $atts['slug'] ) . '"' ) .
				'</div>';
		}
		return '';
	}

	// Render the snippet using its own content only, without
	// re-running the full global "the_content" filter which may
	// replace it with the current page's content.
	$content = $snippet_post->post_content;
	$content = do_shortcode( $content );
	$content = wpautop( $content );
	$title = $snippet_post->post_title;
	$css_class = 'legal-snippet';
	
	if ( ! empty( $atts['class'] ) ) {
		$css_class .= ' ' . sanitize_html_class( $atts['class'] );
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( $css_class ); ?>" data-snippet-id="<?php echo esc_attr( $snippet_post->ID ); ?>">
		<?php echo $content; ?>
	</div>
	<?php
	
	return ob_get_clean();
}

/**
 * Get all snippets for admin use
 */
function las_get_all_snippets() {
	$snippets = get_posts( array(
		'post_type'      => 'legal_snippet',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC'
	) );
	
	return $snippets;
}

/**
 * Get snippet by slug (helper function)
 */
function las_get_snippet_by_slug( $slug ) {
	return get_page_by_path( $slug, OBJECT, 'legal_snippet' );
}

/**
 * Get snippet content by ID or slug (helper function)
 */
function las_get_snippet_content( $id_or_slug ) {
	if ( is_numeric( $id_or_slug ) ) {
		$snippet = get_post( intval( $id_or_slug ) );
	} else {
		$snippet = las_get_snippet_by_slug( $id_or_slug );
	}
	
	if ( $snippet && $snippet->post_type === 'legal_snippet' && $snippet->post_status === 'publish' ) {
		return apply_filters( 'the_content', $snippet->post_content );
	}
	
	return false;
}