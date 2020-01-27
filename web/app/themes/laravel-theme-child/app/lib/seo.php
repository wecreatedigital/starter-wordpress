<?php

/**
 * Add Yoast SEO sitemap to virtual robots.txt file
 *
 * @author Dean Appleton-Claydon via https://github.com/Surbma/surbma-yoast-seo-sitemap-to-robotstxt/blob/master/surbma-yoast-seo-sitemap-to-robotstxt.php
 * @date   2019-04-29
 */
function yoast_seo_sitemap_to_robotstxt_function($output)
{
    $options = get_option('wpseo_xml');
    if (class_exists('WPSEO_Sitemaps') && $options['enablexmlsitemap'] == true) {
        $homeURL = get_home_url();
        $output .= "Sitemap: $homeURL/sitemap_index.xml\n";
    }

    return $output;
}
add_filter('robots_txt', 'yoast_seo_sitemap_to_robotstxt_function', 9999, 1);

/**
 * Remove the tag taxonomy from WordPress
 *
 * @author Dean Appleton-Claydon via https://wordpress.stackexchange.com/questions/48017/remove-tag-from-theme-support
 * @date   2020-01-27
 */
add_action('init', 'wecreate_remove_tags');
function wecreate_remove_tags()
{
    global $wp_taxonomies;
    $tax = 'post_tag'; // this may be wrong, I never remember the names on the defaults
    if (taxonomy_exists($tax)) {
        unset($wp_taxonomies[$tax]);
    }
}
