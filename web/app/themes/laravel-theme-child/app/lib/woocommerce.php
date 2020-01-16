<?php

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

if ( ! function_exists('my_yoast_breadcrumb')) {
    /* Replace WooCommerce Breadcrumbs With Yoast Breadcrumbs
     * Credit: Mixed
     * Last Tested: Oct 09, 2019 using WooCommerce 3.7.1 with Yoast SEO 12.2 on WordPress 5.2.3
     */
    // Remove WooCommerce Breadcrumbs - Most Themes
    // Credit: https://docs.woocommerce.com/document/customise-the-woocommerce-breadcrumb/#section-4
    add_action('init', 'woo_remove_wc_breadcrumbs');
    function woo_remove_wc_breadcrumbs()
    {
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
    }

    // Add Yoast Breadcrumbs to WooCommerce products and taxonomies
    // This does not add breadcrumbs to posts, pages, or non-WooCommerce content in most cases.
    // If you see duplicated breadcrumbs, you may need to use a different hook.
    // Documentation for WooCommerce hooks: https://docs.woocommerce.com/wc-apidocs/hook-docs.html
    add_action('woocommerce_before_main_content', 'my_yoast_breadcrumb', 20, 0);

    function my_yoast_breadcrumb()
    {
        yoast_breadcrumb('<p class="woocommerce-breadcrumb" id="breadcrumbs">', '</p>');
    }
}
