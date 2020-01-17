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
