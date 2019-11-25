<?php
/**
 * Gutenberg Customization.
 *
 * Used to customize Gutenberg behavior.
 *
 * @package LearnDash
 */

namespace LearnDash\Admin\Gutenberg;

/**
 * Disable Gutenberg on specific CPTs.
 *
 * @param bool   $is_enabled Define if Gutenberg is enabled.
 * @param string $post_type  Current post type.
 * @return bool
 */
function disable_on_cpts( $is_enabled, $post_type ) {
	// Disable Gutenberg on the following CPTs.
	$disabled_cpts = array(
		'sfwd-question',
		'groups',
	);

	if ( in_array( $post_type, $disabled_cpts ) ) {
		return false;
	}

	return $is_enabled;

}
add_filter( 'use_block_editor_for_post_type', '\LearnDash\Admin\Gutenberg\disable_on_cpts', 10, 2 );
add_filter( 'gutenberg_can_edit_post_type', '\LearnDash\Admin\Gutenberg\disable_on_cpts', 10, 2 );
