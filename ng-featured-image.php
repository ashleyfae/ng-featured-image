<?php
/**
 * Plugin Name: Nose Graze - Featured Image
 * Plugin URI: https://www.nosegraze.com
 * Description: Featured image handling.
 * Version: 1.0
 * Author: Ashley Gibson
 * Author URI: https://www.nosegraze.com
 * License: GPL2+
 */

namespace NG_Featured_Image;

const META_KEY = '_hide_featured_image';

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ng-featured-image', __( 'Featured Image' ), __NAMESPACE__ . '\metaboxCallback', 'post', 'side', 'core' );
} );

/**
 * Renders the meta box.
 *
 * @param \WP_Post $post
 */
function metaboxCallback( \WP_Post $post ): void {
	?>
	<p>
		<input type="checkbox" id="_hide_featured_image" name="<?php echo esc_attr( META_KEY ); ?>" value="1" <?php checked( get_post_meta( $post->ID, META_KEY, true ) ); ?>>
		<label for="_hide_featured_image"><?php esc_html_e( 'Hide featured image on post' ); ?></label>
		<?php wp_nonce_field( 'ng_save_featured_image_display', 'ng_save_featured_image_display_nonce' ); ?>
	</p>
	<?php
}

/**
 * Saves the post meta.
 */
add_action( 'save_post', function ( int $postId ) {
	if ( empty( $_POST['ng_save_featured_image_display_nonce'] ) || ! wp_verify_nonce( $_POST['ng_save_featured_image_display_nonce'], 'ng_save_featured_image_display' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'page' === $_POST['post_type'] && ! current_user_can( 'edit_page', $postId ) ) {
		return;
	} elseif ( ! current_user_can( 'edit_post', $postId ) ) {
		return;
	}

	if ( ! empty( $_POST[ META_KEY ] ) ) {
		update_post_meta( $postId, META_KEY, 1 );
	} else {
		delete_post_meta( $postId, META_KEY );
	}
} );

/**
 * Filters `the_content` to auto display the featured image.
 */
add_filter( 'the_content', function ( string $content ) {
	if ( 'post' !== get_post_type() ) {
		return $content;
	}

	// If we're in the excerpt, bail immediately.
	if ( in_array( 'get_the_excerpt', $GLOBALS['wp_current_filter'] ) ) {
		return $content;
	}

	return getFeaturedImage() . $content;
} );

/**
 * Retrieves the featured image.
 *
 * @return string
 */
function getFeaturedImage(): string {
	if ( ! has_post_thumbnail() ) {
		return '';
	}

	// Don't show featured images if "hide featured image" meta is checked on.
	if ( get_post_meta( get_the_ID(), '_hide_featured_image', true ) ) {
		return '';
	}

	ob_start();
	the_post_thumbnail( 'full', array( 'class' => 'aligncenter' ) );

	return ob_get_clean();
}
