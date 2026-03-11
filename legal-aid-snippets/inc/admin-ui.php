<?php
/**
 * Legal Aid Snippets - Admin UI Enhancements
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Add meta box to show shortcode usage
add_action( 'add_meta_boxes', 'las_add_snippet_meta_boxes' );
function las_add_snippet_meta_boxes() {
	add_meta_box(
		'las_snippet_usage',
		__( 'How to Use This Snippet', 'legal-aid-snippets' ),
		'las_render_snippet_usage_meta_box',
		'legal_snippet',
		'side',
		'high'
	);
}

function las_render_snippet_usage_meta_box( $post ) {
	?>
	<div class="snippet-usage-info">
		<h4><?php _e( 'Shortcode Options:', 'legal-aid-snippets' ); ?></h4>
		
		<div style="margin-bottom: 15px;">
			<strong><?php _e( 'By Slug (Recommended):', 'legal-aid-snippets' ); ?></strong>
			<input type="text" readonly value='[snippet slug="<?php echo esc_attr( $post->post_name ); ?>"]' 
				   style="width: 100%; margin-top: 5px;" onclick="this.select();" />
			<p class="description"><?php _e( 'Use this if you want the shortcode to work even if you change the snippet ID.', 'legal-aid-snippets' ); ?></p>
		</div>
		
		<div style="margin-bottom: 15px;">
			<strong><?php _e( 'By ID:', 'legal-aid-snippets' ); ?></strong>
			<input type="text" readonly value='[snippet id="<?php echo esc_attr( $post->ID ); ?>"]' 
				   style="width: 100%; margin-top: 5px;" onclick="this.select();" />
			<p class="description"><?php _e( 'Direct ID reference. Faster but less flexible.', 'legal-aid-snippets' ); ?></p>
		</div>
		
		<div style="margin-bottom: 15px;">
			<strong><?php _e( 'With Custom CSS Class:', 'legal-aid-snippets' ); ?></strong>
			<input type="text" readonly value='[snippet slug="<?php echo esc_attr( $post->post_name ); ?>" class="highlight"]' 
				   style="width: 100%; margin-top: 5px;" onclick="this.select();" />
			<p class="description"><?php _e( 'Add custom CSS classes for styling.', 'legal-aid-snippets' ); ?></p>
		</div>
		
		<hr style="margin: 15px 0;">
		
		<h4><?php _e( 'Usage Tips:', 'legal-aid-snippets' ); ?></h4>
		<ul style="margin-left: 15px;">
			<li><?php _e( 'Use in any post, page, or widget that supports shortcodes', 'legal-aid-snippets' ); ?></li>
			<li><?php _e( 'Changes here will update everywhere the snippet is used', 'legal-aid-snippets' ); ?></li>
			<li><?php _e( 'Keep snippets focused and reusable', 'legal-aid-snippets' ); ?></li>
		</ul>
	</div>
	
	<style>
	.snippet-usage-info input[readonly] {
		background: #f9f9f9;
		border: 1px solid #ddd;
		padding: 8px;
		font-family: monospace;
		font-size: 12px;
		cursor: pointer;
	}
	.snippet-usage-info input[readonly]:focus {
		box-shadow: 0 0 5px rgba(0, 115, 170, 0.3);
		border-color: #0073aa;
	}
	</style>
	<?php
}

// Add admin notice for new snippets
add_action( 'admin_notices', 'las_show_snippet_creation_notice' );
function las_show_snippet_creation_notice() {
	global $post, $pagenow;
	
	if ( $pagenow === 'post.php' 
		&& isset( $_GET['message'] ) 
		&& $_GET['message'] == '6' 
		&& isset( $post ) 
		&& $post->post_type === 'legal_snippet' 
	) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><strong><?php _e( 'Snippet published!', 'legal-aid-snippets' ); ?></strong> 
			<?php _e( 'You can now use it anywhere with:', 'legal-aid-snippets' ); ?> 
			<code>[snippet slug="<?php echo esc_html( $post->post_name ); ?>"]</code></p>
		</div>
		<?php
	}
}

// Customize the "Enter title here" placeholder
add_filter( 'enter_title_here', 'las_change_snippet_title_placeholder', 10, 2 );
function las_change_snippet_title_placeholder( $title, $post ) {
	if ( $post->post_type === 'legal_snippet' ) {
		return __( 'Enter snippet name (e.g., "Legal Aid Contact Info")', 'legal-aid-snippets' );
	}
	return $title;
}

// Add quick links to admin menu
add_action( 'admin_menu', 'las_add_quick_links' );
function las_add_quick_links() {
	add_submenu_page(
		'edit.php?post_type=legal_snippet',
		__( 'All Snippets List', 'legal-aid-snippets' ),
		__( 'Quick Reference', 'legal-aid-snippets' ),
		'edit_posts',
		'snippet-reference',
		'las_render_snippet_reference_page'
	);
}

function las_render_snippet_reference_page() {
	$snippets = las_get_all_snippets();
	?>
	<div class="wrap">
		<h1><?php _e( 'Snippet Quick Reference', 'legal-aid-snippets' ); ?></h1>
		<p><?php _e( 'Copy and paste these shortcodes to use your snippets anywhere on your website.', 'legal-aid-snippets' ); ?></p>
		
		<?php if ( empty( $snippets ) ): ?>
			<div class="notice notice-info">
				<p><?php _e( 'No snippets found.', 'legal-aid-snippets' ); ?> 
				<a href="<?php echo admin_url( 'post-new.php?post_type=legal_snippet' ); ?>" class="button button-primary">
					<?php _e( 'Create Your First Snippet', 'legal-aid-snippets' ); ?>
				</a></p>
			</div>
		<?php else: ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Snippet Name', 'legal-aid-snippets' ); ?></th>
						<th><?php _e( 'Shortcode', 'legal-aid-snippets' ); ?></th>
						<th><?php _e( 'Preview', 'legal-aid-snippets' ); ?></th>
						<th><?php _e( 'Actions', 'legal-aid-snippets' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $snippets as $snippet ): ?>
						<tr>
							<td><strong><?php echo esc_html( $snippet->post_title ); ?></strong></td>
							<td>
								<input type="text" readonly value='[snippet slug="<?php echo esc_attr( $snippet->post_name ); ?>"]' 
									   style="width: 100%; font-family: monospace; background: #f9f9f9;" 
									   onclick="this.select();" />
							</td>
							<td>
								<div style="max-width: 300px; max-height: 100px; overflow: hidden; font-size: 12px; color: #666;">
									<?php echo wp_trim_words( strip_tags( $snippet->post_content ), 15 ); ?>
								</div>
							</td>
							<td>
								<a href="<?php echo get_edit_post_link( $snippet->ID ); ?>" class="button button-small">
									<?php _e( 'Edit', 'legal-aid-snippets' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}