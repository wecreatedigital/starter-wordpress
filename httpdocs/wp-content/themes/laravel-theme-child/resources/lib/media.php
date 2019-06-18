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
// add_image_size('image_size_name', 120, 240, false);
