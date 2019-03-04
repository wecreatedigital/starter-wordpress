<?php

/**
 * ACF options https://www.advancedcustomfields.com/add-ons/options-page/
 */
if( function_exists('acf_add_options_page') ) {
	acf_add_options_page();
}

/**
 * Image sizes https://developer.wordpress.org/reference/functions/add_image_size/
 */
//add_image_size( 'image_size_name', 120, 240, false);

/**
 * Custom footer menu https://codex.wordpress.org/Function_Reference/register_nav_menus
 */
register_nav_menus([
	'footer_navigation' => __('Footer Navigation', 'sage')
]);
