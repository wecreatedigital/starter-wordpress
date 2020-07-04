<?php

/**
 * Theme admin.
 */

namespace App;

use function Roots\asset;

use WP_Customize_Manager;

/**
 * Register the `.brand` selector as the blogname.
 *
 * @param  \WP_Customize_Manager $wp_customize
 * @return void
 */
add_action('customize_register', function (WP_Customize_Manager $wp_customize) {
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->selective_refresh->add_partial('blogname', [
        'selector' => '.brand',
        'render_callback' => function () {
            bloginfo('name');
        },
    ]);
});

/**
 * Register the customizer assets.
 *
 * @return void
 */
add_action('customize_preview_init', function () {
    wp_enqueue_script('sage/customizer.js', asset('scripts/customizer.js')->uri(), ['customize-preview'], null, true);
});

/**
 * Inject critical assets in head as early as possible
 */
add_action('wp_head', function (): void {
    if (is_front_page()) {
        // $critical_CSS = locate_asset('styles/critical-home.css');
        $critical_CSS = 'blog_critical.min.css';
    } elseif (is_singular()) {
        // $critical_CSS = locate_asset('styles/critical-singular.css');
        $critical_CSS = 'styles/critical-singular.css';
    } else {
        // $critical_CSS = locate_asset('styles/critical-landing.css');
        $critical_CSS = 'styles/critical-landing.css';
    }
    // dd(asset('styles/critical-landing.css')->uri());
    if (file_exists(get_template_directory().'/'.$critical_CSS)) {
        echo '<style id="critical-css">'.file_get_contents(get_template_directory().'/'.$critical_CSS).'</style>';
    }
}, 1);
