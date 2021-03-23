<?php

use Illuminate\Support\Facades\Config;

/**
 * ACF filter to speed up backend loading time
 * https://awesomeacf.com/snippets/speed-acf-backend-loading-time/
 * @var [type]
 */
// add_filter('acf/settings/remove_wp_meta_box', '__return_true');

/**
 * https://www.advancedcustomfields.com/blog/google-maps-api-settings/
 *
 * Add the following in Google API Console:
 * Google Maps Directions API
 * Google Maps Distance Matrix API
 * Google Maps Elevation API
 * Google Maps Geocoding API
 * Google Maps JavaScript API
 * Google Places API Web Service
 * Google Static Maps API
 *
 * @author Dean Appleton-Claydon
 * @date   2019-09-02
 * @param  [type]     $api [description]
 * @return [type]          [description]
 */
function my_acf_google_map_api($api)
{
    $api['key'] = getenv('GOOGLE_API');

    return $api;
}

add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

/**
 * Hide the 'Custom Fields' menu from WordPress Admin when logged in user email address matches TLD
 */
add_filter('acf/settings/show_admin', function () {
    if ( ! is_user_logged_in()) {
        return false;
    }

    $user_email = explode("@", wp_get_current_user()->user_email);

    return in_array(
        $user_email[1],
        Config::get('flexible.developers')
    );
});
