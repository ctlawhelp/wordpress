<?php
/**
 * Last Reviewed Date meta box for Interactive Guides (interactive_guide).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function ig_add_last_reviewed_meta_box() {
	add_meta_box(
		'ig_last_reviewed',
		'Last Reviewed Date',
		'ig_render_last_reviewed_meta_box',
		'interactive_guide',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'ig_add_last_reviewed_meta_box' );

function ig_render_last_reviewed_meta_box( $post ) {
	wp_nonce_field( 'ig_last_reviewed_nonce_action', 'ig_last_reviewed_nonce' );

	$value = get_post_meta( $post->ID, '_last_reviewed_date', true );
	?>
	<p>
		<label for="ig_last_reviewed_date">Last reviewed (text)</label><br />
		<input type="text" class="widefat" id="ig_last_reviewed_date" name="ig_last_reviewed_date" value="<?php echo esc_attr( $value ); ?>" />
	</p>
	<p class="description">Free-form text, e.g. "December 2024" or "Reviewed fall 2024". This is separate from the WordPress publish date.</p>
	<?php
}

function ig_save_last_reviewed_meta_box( $post_id ) {
	if ( ! isset( $_POST['ig_last_reviewed_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['ig_last_reviewed_nonce'], 'ig_last_reviewed_nonce_action' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'interactive_guide' ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	} else {
		return;
	}

	if ( isset( $_POST['ig_last_reviewed_date'] ) ) {
		$text = trim( wp_unslash( $_POST['ig_last_reviewed_date'] ) );

		if ( $text === '' ) {
			delete_post_meta( $post_id, '_last_reviewed_date' );
			return;
		}

		update_post_meta( $post_id, '_last_reviewed_date', sanitize_text_field( $text ) );
	}
}
add_action( 'save_post_interactive_guide', 'ig_save_last_reviewed_meta_box' );
