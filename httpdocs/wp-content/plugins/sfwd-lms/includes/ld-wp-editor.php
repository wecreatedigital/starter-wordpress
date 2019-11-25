<?php
/**
 * Customizations to wp editor for LearnDash
 *
 * All functions currently are customizations for custom certificate implementations
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\TinyMCE
 */


/**
 * Add hooks for TinyMCE customization if we are on edit screen
 *
 * @since 2.1.0
 */
function learndash_mce_init() {	
	$screen = get_current_screen();
	if (($screen) && ( $screen instanceof WP_Screen)) {
		if ( ( $screen->base == 'post') && ( $screen->post_type == 'sfwd-certificates' ) ) {
			add_filter( 'tiny_mce_before_init', 'wpLD_tiny_mce_before_init' );
			add_filter( 'mce_css', 'filter_mce_css' );
		}
	}
}

/**
 * changes hook in LD v2.3 to only hook into the load of post.php and post-new.php
 */
//add_action( 'init', 'learndash_mce_init' );
add_action( 'load-post.php', 'learndash_mce_init' );
add_action( 'load-post-new.php', 'learndash_mce_init' );



/**
 * Load editor styles for LearnDash. We need to add the LD custom CSS to the function parameter. Not replace it
 * see https://codex.wordpress.org/Plugin_API/Filter_Reference/mce_css
 * @since 2.1.0
 * 
 * @param  string 	$mce_css 
 * @return string 	$mce_css 	path to sfwd_editor.css
 */
function filter_mce_css( $mce_css = '' ) {
	$ld_mce_css = plugins_url( 'assets/css/sfwd_editor.css', LEARNDASH_LMS_PLUGIN_DIR .'index.php' );
	if ( !empty( $ld_mce_css ) ) {
		if ( !empty( $mce_css ) ) {
			$mce_css .= ',';
		}
		$mce_css .= $ld_mce_css;
	}
	return $mce_css;
}



/**
 * Make the background of the vidual editor the image of the certificate
 *
 * @todo  confirm intent of function and if it's still needed
 *        not currently functional
 * 
 * @since 2.1.0
 * 
 * @param  array $initArray tinymce settings
 * @return array $initArray tinymce settings
 */
function wpLD_tiny_mce_before_init( $initArray ) {
	if ( isset( $_GET['post'] ) ) {	
		$post_id = $_GET['post']; 
	} else { 
		$post_id = get_the_id(); 
	}

	if ( !empty( $post_id ) ) {
		$img_path = learndash_get_thumb_url( $post_id );
		if ( !empty( $img_path ) ) {
			$initArray['setup'] = <<<JS
[function(ed) {
    ed.onInit.add(function(ed, e) {
		var w = jQuery("#content_ifr").width();
		var editorId = ed.getParam("fullscreen_editor_id") || ed.id;
		jQuery("#content_ifr").contents().find("#tinymce").css
		({"background-image":"url($img_path)"
		});
		
		if(editorId == 'wp_mce_fullscreen'){
		jQuery("#wp_mce_fullscreen_ifr").contents().find("#tinymce").css
		({"background-image":"url($img_path)"
		});
		}
    });

}][0]
JS;
		}
	}
	return $initArray;
}



/**
 * Get featured image of post
 *
 * @since 2.1.0
 * 
 * @param  int 		$post_id
 * @return string 	full path of image
 */
function learndash_get_thumb_url( $post_id = 0, $size = 'full' ) {
	
	if ( ( !empty( $post_id ) ) && ( has_post_thumbnail( $post_id ) ) ) {
		$image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id(), $size );
		if ( ( !empty($image_src_array ) ) && ( is_array( $image_src_array ) ) && ( !empty( $image_src_array[0] ) ) ) {
			return $image_src_array[0];
		}
	}
}
