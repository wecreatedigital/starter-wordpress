<?php
// function google_maps()
// {
//     $api = 'https://maps.googleapis.com/maps/api/js?key='.getenv('GOOGLEMAPS');
//     wp_enqueue_script('google-map', $api, array(), '3', true);
//     wp_enqueue_script('google-map-init', get_template_directory_uri().'/assets/scripts/google-maps.js', array('google-map', 'jquery'), '0.1', true);
// }
//
// add_action('wp_enqueue_scripts', 'google_maps');
//
// function my_acf_google_map_api($api)
// {
//     $api['key'] = getenv('GOOGLEMAPS');
//
//     return $api;
// }
//
// add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');
