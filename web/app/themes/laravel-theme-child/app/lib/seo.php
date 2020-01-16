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
