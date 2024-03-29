<?php

function lark_starter_add_woocommerce_support()
{
    add_theme_support('woocommerce', array(
        'thumbnail_image_width' => 150,
        'single_image_width' => 300,

        'product_grid' => array(
            'default_rows' => 3,
            'min_rows' => 2,
            'max_rows' => 8,
            'default_columns' => 4,
            'min_columns' => 3,
            'max_columns' => 5,
        ),
    ));

    // add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'lark_starter_add_woocommerce_support');

/**
* Manage WooCommerce styles and scripts.
*/
function grd_woocommerce_script_cleaner()
{
    // Remove the generator tag
    remove_action('wp_head', array($GLOBALS['woocommerce'], 'generator'));

    wp_dequeue_style('wc-block-style'); // We never use these blocks

    // Unless we're in the store, remove all the cruft!
    if ( ! is_woocommerce() && ! is_cart() && ! is_checkout()) {
        wp_dequeue_style('woocommerce_frontend_styles');
        wp_dequeue_style('woocommerce-general');
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');
        wp_dequeue_style('woocommerce_fancybox_styles');
        wp_dequeue_style('woocommerce_chosen_styles');
        wp_dequeue_style('woocommerce_prettyPhoto_css');
        wp_dequeue_script('selectWoo');
        wp_deregister_script('selectWoo');
        wp_dequeue_script('wc-add-payment-method');
        wp_dequeue_script('wc-lost-password');
        wp_dequeue_script('wc_price_slider');
        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('wc-credit-card-form');
        wp_dequeue_script('wc-checkout');
        wp_dequeue_script('wc-add-to-cart-variation');
        wp_dequeue_script('wc-single-product');
        wp_dequeue_script('wc-cart');
        wp_dequeue_script('wc-chosen');
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('prettyPhoto');
        wp_dequeue_script('prettyPhoto-init');
        wp_dequeue_script('jquery-blockui');
        wp_dequeue_script('jquery-placeholder');
        wp_dequeue_script('jquery-payment');
        wp_dequeue_script('fancybox');
        wp_dequeue_script('jqueryui');
    }
}
add_action('wp_enqueue_scripts', 'grd_woocommerce_script_cleaner', 99);

/**
 * Add revisions to Products CPT
 * Won't store all the meta data but gives feedback in the revision logs
 *
 * @author Dean Appleton-Claydon
 * @date   2019-09-02
 * @param  [type]     $args [description]
 * @return [type]           [description]
 */
function wc_modify_product_post_type($args)
{
    $args['supports'][] = 'revisions';

    return $args;
}
add_filter('woocommerce_register_post_type_product', 'wc_modify_product_post_type');

/**
 * Remove WooCommerce breadcrumbs if Yoast breadcrumbs exists
 *
 * Credit: https://docs.woocommerce.com/document/customise-the-woocommerce-breadcrumb/#section-4
 * Last Tested: 10/05/2020 using WooCommerce 4.1.0 with Yoast SEO 14.0.4 on WordPress 5.4.1
 */
if ( ! function_exists('my_yoast_breadcrumb')) {
    add_action('init', 'woo_remove_wc_breadcrumbs');
    function woo_remove_wc_breadcrumbs()
    {
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
    }
}

/**
 * TODO: Remove some/all Woo CSS and have this in the Lark Starter
 * Remove each style one by one
 *
 * https://docs.woocommerce.com/document/disable-the-default-stylesheet/
 */
add_filter('woocommerce_enqueue_styles', 'jk_dequeue_styles');
function jk_dequeue_styles($enqueue_styles)
{
    // unset($enqueue_styles['woocommerce-general']);	// Remove the gloss
    // unset($enqueue_styles['woocommerce-layout']);		// Remove the layout
    // unset($enqueue_styles['woocommerce-smallscreen']);	// Remove the smallscreen optimisation
    return $enqueue_styles;
}

/**
 * Or just remove them all in one line
 */
// add_filter('woocommerce_enqueue_styles', '__return_false');

/**
 * Remove Woocommerce Select2 - Woocommerce 3.2.1+
 */
function woo_dequeue_select2()
{
    if (class_exists('woocommerce')) {
        wp_dequeue_style('select2');
        wp_deregister_style('select2');

        wp_dequeue_script('selectWoo');
        wp_deregister_script('selectWoo');
    }
}
add_action('wp_enqueue_scripts', 'woo_dequeue_select2', 100);

/**
 * Add custom stock status
 */
// add_filter('woocommerce_product_stock_status_options', 'add_custom_stock_statuses');
// function add_custom_stock_statuses($statuses)
// {
//     // Add a new status
//     $statuses['twothreeworking'] = __('2-3 workings days to dispatch', 'plugin-name');
//
//     // Remove a built-in status
//     // unset($statuses['onbackorder']);
//
//     return $statuses;
// }
