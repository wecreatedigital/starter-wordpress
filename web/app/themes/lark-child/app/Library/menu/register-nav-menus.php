<?php

/**
 * Custom primary menu https://codex.wordpress.org/Function_Reference/register_nav_menus
 */
register_nav_menus([
    'primary_navigation' => __('Primary Navigation', 'sage'),
]);

/**
 * Custom footer menu https://codex.wordpress.org/Function_Reference/register_nav_menus
 */
register_nav_menus([
    'footer_navigation' => __('Footer Navigation', 'sage'),
]);
