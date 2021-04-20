<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Required Plugins
    |--------------------------------------------------------------------------
    |
    | List of plugins which get automatically activated upon
    | switching/activating a theme. Some of these are required that's
    | why they are specified within this list.
    |
    */

    'required-plugins' => [
        'mailgun/mailgun.php',
        'contact-form-7/wp-contact-form-7.php',
        'wp-pagenavi/wp-pagenavi.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fonts
    |--------------------------------------------------------------------------
    |
    | There are some use cases whereby a global heading size has different
    | options for various breakpoints. The array below has options to
    | dynamically override any default font classes set below using the
    | headingSize helper method.
    |
    */

    'fonts' => [
        'h1' => [
            'mobile' => [
                'size' => 'text-30',
                'margin' => 'mb-30',
                'font' => 'font-cardo font-weight-bold',
            ],
            'desktop' => [
                'size' => 'md:text-44',
            ],
        ],
    ],

];
