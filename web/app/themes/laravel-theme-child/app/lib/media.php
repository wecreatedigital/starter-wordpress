<?php

function cc_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';

    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

/**
 * Image sizes https://developer.wordpress.org/reference/functions/add_image_size/
 */
add_image_size('large_square', 1000, 1000, false);
add_image_size('full_width', 1440);

/**
 * Increase jpeg quality from the default of 82/100
 */
// add_filter('jpeg_quality', function ($arg) {
//     return 95;
// });
