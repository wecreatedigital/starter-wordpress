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
        'tinymce-advanced/tinymce-advanced.php',

        // Used for duplicating pages as well as the ACF on the page,
        // very useful for when copying the homepage on dev
        // 'duplicate-page/duplicatepage.php',
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
            'size' => 'text-46',
            'font' => 'font-bitter',
            'margin' => 'mb-20',
        ],

        'h2' => [
            'size' => 'text-36 -word-spacing-0.4',
            'font' => 'font-bitter',
            'margin' => 'mb-35',
        ],

        'h3' => [
            'size' => 'text-24',
            'font' => 'font-bitter',
            'margin' => 'mb-35',
        ],

        'h4' => [
            'size' => 'text-20',
            'font' => 'font-bitter',
            'margin' => 'mb-35',
        ],
    ],

    'siteName' => get_bloginfo('name', 'display'),

    'homeUrl' => get_home_url(),

    /*
    |--------------------------------------------------------------------------
    | Heading for SEO
    |--------------------------------------------------------------------------
    |
    | Defining the very first heading <h1> tag.
    |
    */

    'h' => 1,

    /*
    |--------------------------------------------------------------------------
    | TinyMCE editor
    |--------------------------------------------------------------------------
    */

    'tinymce' => [
        'hide-unused-buttons' => [
            'fontselect',
            'fontsizeselect',
            'blockquote',
            'wp_more',
            'textcolor',
            'colorpicker',
        ],
    ],

];
