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

/**
 * Returns the primary term for the chosen taxonomy set by Yoast SEO
 * or the first term selected.
 *
 * @link https://www.tannerrecord.com/how-to-get-yoasts-primary-category/
 * @param integer $post The post id.
 * @param string  $taxonomy The taxonomy to query. Defaults to category.
 * @return array The term with keys of 'title', 'slug', and 'url'.
 */
function get_primary_taxonomy_term($post = 0, $taxonomy = 'category')
{
    if ( ! $post) {
        $post = get_the_ID();
    }

    $terms = get_the_terms($post, $taxonomy);
    $primary_term = array();

    if ($terms) {
        $term_display = '';
        $term_slug = '';
        $term_link = '';
        if (class_exists('WPSEO_Primary_Term')) {
            $wpseo_primary_term = new WPSEO_Primary_Term($taxonomy, $post);
            $wpseo_primary_term = $wpseo_primary_term->get_primary_term();
            $term = get_term($wpseo_primary_term);
            if (is_wp_error($term)) {
                $term_display = $terms[0]->name;
                $term_slug = $terms[0]->slug;
                $term_link = get_term_link($terms[0]->term_id);
            } else {
                $term_display = $term->name;
                $term_slug = $term->slug;
                $term_link = get_term_link($term->term_id);
            }
        } else {
            $term_display = $terms[0]->name;
            $term_slug = $terms[0]->slug;
            $term_link = get_term_link($terms[0]->term_id);
        }
        $primary_term['url'] = $term_link;
        $primary_term['slug'] = $term_slug;
        $primary_term['title'] = $term_display;
    }

    return $primary_term;
}

/*
 * Modify TinyMCE editor to remove H1 - this should be set in templates/FCBs only
 *
 * From: https://www.calliaweb.co.uk/code/modify-tinymce-editor/
 */
add_filter('tiny_mce_before_init', 'tiny_mce_remove_unused_formats');
function tiny_mce_remove_unused_formats($init)
{
    // Add block format elements you want to show in dropdown
    $init['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Address=address;Pre=pre';

    return $init;
}
