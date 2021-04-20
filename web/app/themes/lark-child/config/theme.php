<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto Activiated Plugins
    |--------------------------------------------------------------------------
    |
    | List of plugins which get automatically activated upon
    | switching/activating a theme. These plugins are generally used across
    | every single Lark build which is why they are listed here.
    |
    | Please add or remove any when/if called for.
    |
    */

    'auto-activiated-plugins' => [
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

    'siteName' => get_bloginfo('name', 'display'),

    'homeUrl' => get_home_url(),

];
