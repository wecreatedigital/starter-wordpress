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
            'size' => 'text-32 md:text-42',
            'font' => 'font-questrial',
            'margin' => 'mb-30',
        ],

        'h2' => [
            'size' => 'text-26 leading-36 -tracking-0.6 md:text-36 md:-tracking-0.4 md:leading-45 md:-word-spacing-2.6',
            'font' => 'font-questrial',
            'margin' => 'mb-30 md:mb-40',
        ],

        'h3' => [
            'size' => 'text-24 leading-32',
            'font' => 'font-questrial',
            'margin' => 'mb-30',
        ],

        'h4' => [
            'size' => 'text-18',
            'font' => 'font-questrial',
            'margin' => '',
        ],

        'h5' => [
            'size' => 'text-16',
            'font' => 'font-questrial',
            'margin' => '',
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
