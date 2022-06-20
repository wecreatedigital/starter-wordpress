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
    if (is_null(getenv('GOOGLEMAPS')) || empty(getenv('GOOGLEMAPS'))) {
        return;
    }

    $api = 'https://maps.googleapis.com/maps/api/js?key='.getenv('GOOGLEMAPS');

    wp_enqueue_script('google-map', $api, array(), '3', true);

    wp_enqueue_script(
        'google-map-init',
        get_stylesheet_directory_uri().'/resources/assets/scripts/google-maps.js',
        array('google-map', 'jquery'),
        null,
        false
    );
}
add_action('wp_enqueue_scripts', 'google_maps');
