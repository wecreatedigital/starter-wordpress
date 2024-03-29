<?php

// $h = 1;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if ( ! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect([
    'helpers',
    'setup',
    'filters',
    'admin',
    'Library/acf/dynamic-select',
    'Library/acf/general',
    'Library/acf/helpers',
    'Library/acf/options',
    'Library/acf/override-padding',
    'Library/helpers/text',
    'Library/social/index',
    'Library/social/instagram',
    'Library/admin',
    // 'Library/ajax',
    'Library/content',
    'Library/cpt',
    // 'Library/donate',
    // 'Library/form',
    'Library/media',
    'Library/menu/register-nav-menus',
    // 'Library/menu/mega-menu', // NOTE: Visit for setup instructions
    'Library/menu/navwalker',
    'Library/scripts',
    'Library/security',
    'Library/seo',
    'Library/speed',
    // 'Library/woocommerce',
    // 'Library/stripe-api', // NOTE: please install: https://github.com/stripe/stripe-php and enable the Stripe REST routes
    'Library/google-maps',
    'Library/tinymce',
])
    ->each(function ($file) {
        $file = "app/{$file}.php";
        if ( ! locate_template($file, true, true)) {
            wp_die(
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });

/*
|--------------------------------------------------------------------------
| Enable Sage Theme Support
|--------------------------------------------------------------------------
|
| Once our theme files are registered and available for use, we are almost
| ready to boot our application. But first, we need to signal to Acorn
| that we will need to initialize the necessary service providers built in
| for Sage when booting.
|
*/

add_theme_support('sage');

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We are ready to bootstrap the Acorn framework and get it ready for use.
| Acorn will provide us support for Blade templating as well as the ability
| to utilize the Laravel framework and its beautifully written packages.
|
*/

new Roots\Acorn\Bootloader();

// $items = new \WP_Query([
//     'posts_per_page' => -1,
//     'post_type' => 'post',
//     'orderby' => 'date',
//     'order' => 'DESC',
// ]);

// $mediaIds = [246, 247, 248];

// foreach ($items->posts as $item) {
//     set_post_thumbnail($item->ID, $mediaIds[ array_rand($mediaIds)]);
// }
