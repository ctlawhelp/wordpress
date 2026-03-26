<?php
/**
 * Plugin Name: CTLawHelp Section Prototype
 * Description: Minimal prototype block for CTLawHelp article sections.
 * Version: 0.1.2
 * Author: Kate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Block registration
// ---------------------------------------------------------------------------

add_action( 'init', 'ctlh_register_section_prototype_block' );

function ctlh_register_section_prototype_block() {
	wp_register_script(
		'ctlh-section-prototype-editor',
		plugin_dir_url( __FILE__ ) . 'section-block.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'section-block.js' )
	);

	register_block_type( 'ctlh/section-prototype', array(
		'editor_script'   => 'ctlh-section-prototype-editor',
		'render_callback' => 'ctlh_render_section_prototype_block',
	) );
}

add_action( 'enqueue_block_editor_assets', 'ctlh_enqueue_convert_to_sections' );

function ctlh_enqueue_convert_to_sections() {
	wp_enqueue_script(
		'ctlh-convert-to-sections',
		plugin_dir_url( __FILE__ ) . 'convert-to-sections.js',
		array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'convert-to-sections.js' )
	);
}

// ---------------------------------------------------------------------------
// Article-level display toggles
// ---------------------------------------------------------------------------

/**
 * Post types that show the Section Display meta box.
 */
function ctlh_section_meta_post_types() {
	return array( 'post', 'legal_article' );
}

/**
 * Read a boolean toggle meta value. Returns true when the meta has never been
 * saved (i.e. both toggles default to ON for new articles).
 */
function ctlh_get_section_toggle( $post_id, $key ) {
	$val = get_post_meta( $post_id, $key, true );
	return $val === '' ? true : (bool) $val;
}

add_action( 'init', 'ctlh_register_section_display_meta' );

function ctlh_register_section_display_meta() {
	$args = array(
		'type'         => 'boolean',
		'single'       => true,
		'default'      => true,
		'show_in_rest' => true,
	);
	foreach ( ctlh_section_meta_post_types() as $type ) {
		register_post_meta( $type, '_ctlh_show_toc', $args );
		register_post_meta( $type, '_ctlh_use_accordion', $args );
	}
}

add_action( 'add_meta_boxes', 'ctlh_add_section_display_meta_box' );

function ctlh_add_section_display_meta_box() {
	add_meta_box(
		'ctlh-section-display',
		'Section Display',
		'ctlh_render_section_display_meta_box',
		ctlh_section_meta_post_types(),
		'side',
		'default'
	);
}

function ctlh_render_section_display_meta_box( $post ) {
	wp_nonce_field( 'ctlh_section_display', 'ctlh_section_display_nonce' );

	$show_toc      = ctlh_get_section_toggle( $post->ID, '_ctlh_show_toc' );
	$use_accordion = ctlh_get_section_toggle( $post->ID, '_ctlh_use_accordion' );
	?>
	<p style="margin:8px 0;">
		<label>
			<input type="checkbox" name="ctlh_show_toc" value="1" <?php checked( $show_toc ); ?> />
			Show Table of Contents
		</label>
	</p>
	<p style="margin:8px 0;">
		<label>
			<input type="checkbox" name="ctlh_use_accordion" value="1" <?php checked( $use_accordion ); ?> />
			Use Accordion Sections
		</label>
	</p>
	<?php
}

add_action( 'save_post', 'ctlh_save_section_display_meta' );

function ctlh_save_section_display_meta( $post_id ) {
	if ( ! isset( $_POST['ctlh_section_display_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['ctlh_section_display_nonce'], 'ctlh_section_display' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_ctlh_show_toc',      isset( $_POST['ctlh_show_toc'] )      ? '1' : '0' );
	update_post_meta( $post_id, '_ctlh_use_accordion', isset( $_POST['ctlh_use_accordion'] ) ? '1' : '0' );
}

// ---------------------------------------------------------------------------
// Block front-end rendering
// ---------------------------------------------------------------------------

function ctlh_render_section_prototype_block( $attributes, $content ) {
	$post_id       = get_the_ID();
	$use_accordion = ctlh_get_section_toggle( $post_id, '_ctlh_use_accordion' );

	// Extract the first heading's inner text.
	if ( preg_match( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/s', $content, $matches ) ) {
		$heading = $matches[1];

		if ( $use_accordion ) {
			// Hide first heading visually but keep in DOM for accessibility/SEO.
			$content = preg_replace(
				'/<(h[1-6])([^>]*)>/',
				'<$1$2 style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;">',
				$content,
				1
			);
		}
		// When accordion is off, the heading stays visible — no modification needed.
	} else {
		$heading = '';
	}

	// Remove empty paragraphs that create visual gaps in section content.
	// Matches <p> tags (with any attributes) whose content is empty, whitespace,
	// or solely &nbsp; / other whitespace entities — but not tags with real content.
	$content = preg_replace( '/<p[^>]*>(\s|&nbsp;)*<\/p>/', '', $content );

	$id         = $heading !== '' ? sanitize_title( wp_strip_all_tags( $heading ) ) : '';
	$id_attr    = $id !== '' ? ' id="' . esc_attr( $id ) . '"' : '';
	// data-label lets the TOC parser find the section title regardless of tag type.
	$label_attr = $heading !== '' ? ' data-label="' . esc_attr( wp_strip_all_tags( $heading ) ) . '"' : '';

	if ( $use_accordion ) {
		return '<details' . $id_attr . $label_attr . ' class="ctlh-section-prototype" style="border:1px solid #ddd; margin:0 0 16px; border-radius:6px; background:#fafafa;">
	<summary style="font-weight:600; padding:16px; cursor:pointer;">
		' . ( $heading !== '' ? $heading : 'Section' ) . '
	</summary>
	<div style="padding:0 16px 16px;">
		' . $content . '
	</div>
</details>';
	}

	return '<section' . $id_attr . $label_attr . ' class="ctlh-section-prototype" style="margin:0 0 24px;">
	' . $content . '
</section>';
}

// ---------------------------------------------------------------------------
// Table of Contents
// ---------------------------------------------------------------------------

/**
 * Prepend a Table of Contents to singular post content when the post contains
 * at least two Section blocks with headings and the TOC toggle is on.
 *
 * Runs at priority 12 so it fires after block rendering (priority 10).
 */
add_filter( 'the_content', 'ctlh_prepend_section_toc', 12 );

function ctlh_prepend_section_toc( $content ) {
	if ( ! is_singular() ) {
		return $content;
	}

	if ( ! ctlh_get_section_toggle( get_the_ID(), '_ctlh_show_toc' ) ) {
		return $content;
	}

	/*
	 * Find every element (details or section) that carries both
	 * class="ctlh-section-prototype" and a data-label attribute.
	 * Using data-label means the TOC works regardless of whether the
	 * accordion toggle is on or off.
	 */
	preg_match_all(
		'/<(?:details|section)\b([^>]*)>/i',
		$content,
		$tags,
		PREG_SET_ORDER | PREG_OFFSET_CAPTURE
	);

	$sections = array();

	foreach ( $tags as $match_set ) {
		$tag = $match_set[0][0]; // full opening tag string

		if ( strpos( $tag, 'ctlh-section-prototype' ) === false ) {
			continue;
		}

		if ( ! preg_match( '/\bid="([^"]+)"/i', $tag, $id_m ) ) {
			continue;
		}

		if ( ! preg_match( '/\bdata-label="([^"]+)"/i', $tag, $label_m ) ) {
			continue;
		}

		$sections[] = array(
			'id'    => $id_m[1],
			'label' => $label_m[1],
		);
	}

	if ( count( $sections ) < 2 ) {
		return $content;
	}

	$items = '';
	foreach ( $sections as $section ) {
		$items .= '<li><a href="#' . esc_attr( $section['id'] ) . '">' . esc_html( $section['label'] ) . '</a></li>';
	}

	$toc = '<nav class="ctlh-section-toc" aria-label="Table of contents" style="border:1px solid #ddd; border-radius:6px; background:#f5f5f5; padding:16px 20px; margin-bottom:24px;">
	<p style="font-weight:700; margin:0 0 8px;">Contents</p>
	<ul style="margin:0; padding-left:20px;">
		' . $items . '
	</ul>
</nav>';

	return $toc . $content;
}
