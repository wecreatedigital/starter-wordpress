<?php

/**
 * These Two functions will call the js required to display a google maps image
 * Associated files: google-maps.js  google-map.scss partials/google.map.php
 * ammened from: https://www.aliciaramirez.com/2015/02/advanced-custom-fields-google-maps-tutorial/
 *
 * @author Brandon Hull
 * @return [type]
 */
function google_maps()
{
    if (App::page_template('contact') && getenv('GOOGLE_API')) {
        $api = 'https://maps.googleapis.com/maps/api/js?key='.getenv('GOOGLE_API');
        wp_enqueue_script('google-map', $api, array(), '3', true);
        wp_enqueue_script('google-map-init', get_stylesheet_directory_uri().'/assets/scripts/google-maps.js', array('google-map', 'jquery'), '0.1', true);
    }
}

add_action('wp_enqueue_scripts', 'google_maps');

/*
 * w3c valid script and style tags
 */
add_action(
    'after_setup_theme',
    function () {
        add_theme_support('html5', ['script', 'style']);
    }
);

add_filter('wpcf7_load_css', '__return_false');

/**
 * jQuery is outdated in WP and has vulnerabilities, but we don't want break the WP admin_init
 * @author Dean Appleton-Claydon
 * @date   2020-03-02
 *
 * https://stackoverflow.com/questions/1157531/how-can-i-remove-jquery-from-the-frontside-of-my-wordpress
 */
if ( ! is_admin()) {
    add_action('wp_enqueue_scripts', 'my_jquery_enqueue', 11);
}
function my_jquery_enqueue()
{
    wp_deregister_script('jquery');
    wp_register_script('jquery', get_stylesheet_directory_uri().'/assets/scripts/jquery-3.4.1.min.js', false, null, true);
    wp_enqueue_script('jquery');
}
