<?php

add_action('wp_enqueue_scripts', 'enqueue_parent_styles');

function enqueue_parent_styles()
{
    wp_enqueue_style('parent-style', get_site_url().'/wp-content/themes/laravel-theme/dist/styles/main.css');
}
