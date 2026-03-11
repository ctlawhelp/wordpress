<?php
/**
 * Last Reviewed Date meta box and shortcode for Legal Aid Articles and related content.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------------------------------
// Meta Box: Legal Aid Articles (legal_article)
// -----------------------------------------------------------------------------

function laa_add_last_reviewed_meta_box() {
	add_meta_box(
		'laa_last_reviewed',
		'Last Reviewed Date',
		'laa_render_last_reviewed_meta_box',
		'legal_article',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'laa_add_last_reviewed_meta_box' );

function laa_render_last_reviewed_meta_box( $post ) {
	wp_nonce_field( 'laa_last_reviewed_nonce_action', 'laa_last_reviewed_nonce' );

	$value = get_post_meta( $post->ID, '_last_reviewed_date', true );
	?>
	<p>
		<label for="laa_last_reviewed_date">Last reviewed (text)</label><br />
		<input type="text" class="widefat" id="laa_last_reviewed_date" name="laa_last_reviewed_date" value="<?php echo esc_attr( $value ); ?>" />
	</p>
	<p class="description">Free-form text, e.g. "December 2024" or "Reviewed fall 2024". This is separate from the WordPress publish date.</p>
	<?php
}

function laa_save_last_reviewed_meta_box( $post_id ) {
	// Nonce and capability checks
	if ( ! isset( $_POST['laa_last_reviewed_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['laa_last_reviewed_nonce'], 'laa_last_reviewed_nonce_action' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'legal_article' ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	} else {
		return;
	}

	// Save value as free-form text
	if ( isset( $_POST['laa_last_reviewed_date'] ) ) {
		$text = trim( wp_unslash( $_POST['laa_last_reviewed_date'] ) );

		if ( $text === '' ) {
			delete_post_meta( $post_id, '_last_reviewed_date' );
			return;
		}

		update_post_meta( $post_id, '_last_reviewed_date', sanitize_text_field( $text ) );
	}
}
add_action( 'save_post_legal_article', 'laa_save_last_reviewed_meta_box' );

// -----------------------------------------------------------------------------
// Shortcode: [last_reviewed]
// -----------------------------------------------------------------------------

/**
 * Output the last reviewed date for the current post.
 *
 * Usage in templates: echo do_shortcode('[last_reviewed]');
 * Usage in content: [last_reviewed]
 */
function laa_last_reviewed_shortcode( $atts ) {
	global $post;

	if ( ! $post || ! isset( $post->ID ) ) {
		return '';
	}

	$raw = get_post_meta( $post->ID, '_last_reviewed_date', true );
	$raw = is_string( $raw ) ? trim( $raw ) : '';
	if ( $raw === '' ) {
		return '';
	}

	$atts = shortcode_atts( array(
		'label'  => '', // Optional label prefix, e.g. "Last reviewed: "
		'format' => '', // Optional PHP date format override; if provided and the
		                 // value is parseable as a date, we will reformat it.
	), $atts, 'last_reviewed' );

	$label = $atts['label'] !== '' ? $atts['label'] . ' ' : '';

	// If a format is provided and the value looks like a date, try to format it.
	if ( $atts['format'] !== '' ) {
		$timestamp = strtotime( $raw );
		if ( $timestamp ) {
			$format = $atts['format'];
			$date   = date_i18n( $format, $timestamp );
			return esc_html( $label . $date );
		}
	}

	// Default: treat stored text as the final display value.
	return esc_html( $label . $raw );
}
add_shortcode( 'last_reviewed', 'laa_last_reviewed_shortcode' );
