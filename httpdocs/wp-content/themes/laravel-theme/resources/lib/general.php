<?php

/**
 * ACF options https://www.advancedcustomfields.com/add-ons/options-page/
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page();
}

/**
 * Custom footer menu https://codex.wordpress.org/Function_Reference/register_nav_menus
 */
register_nav_menus([
    'footer_navigation' => __('Footer Navigation', 'sage'),
]);

/**
 * X-Frame-Options
 */
add_action('send_headers', 'send_frame_options_header', 10, 0);

/**
 * Clean up head section inspired from https://wordpress.stackexchange.com/questions/211467/remove-json-api-links-in-header-html and https://stackoverflow.com/questions/34750148/how-to-delete-remove-wordpress-feed-urls-in-header
 */
function remove_excess_links_etc()
{
    remove_action('wp_head', 'feed_links_extra', 3);                    // Display the links to the extra feeds such as category feeds
  remove_action('wp_head', 'feed_links', 2);                          // Display the links to the general feeds: Post and Comment Feed
  remove_action('wp_head', 'rsd_link');                               // Display the link to the Really Simple Discovery service endpoint, EditURI link
  remove_action('wp_head', 'wlwmanifest_link');                       // Display the link to the Windows Live Writer manifest file.
  remove_action('wp_head', 'index_rel_link');                         // index link
  remove_action('wp_head', 'parent_post_rel_link', 10, 0);            // prev link
  remove_action('wp_head', 'start_post_rel_link', 10, 0);             // start link
  remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);         // Display relational links for the posts adjacent to the current post.
  remove_action('wp_head', 'wp_generator');                           // Display the XHTML generator that is generated on the wp_head hook, WP version

  remove_action('wp_head', 'rest_output_link_wp_head', 10);           // Remove the REST API lines from the HTML Header
  remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);      // Remove the REST API lines from the HTML Header
  remove_action('rest_api_init', 'wp_oembed_register_route');         // Remove the REST API endpoint.
  add_filter('embed_oembed_discover', '__return_false');              // Turn off oEmbed auto discovery.
  remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);   // Don't filter oEmbed results.
  remove_action('wp_head', 'wp_oembed_add_host_js');                  // Remove oEmbed-specific JavaScript from the front-end and back-end.

  // Filters for WP-API version 1.x
    add_filter('json_enabled', '__return_false');
    add_filter('json_jsonp_enabled', '__return_false');

    // Filters for WP-API version 2.x
    add_filter('rest_enabled', '__return_false');
    add_filter('rest_jsonp_enabled', '__return_false');
}
add_action('after_setup_theme', 'remove_excess_links_etc');

/**
 * Disable pingback http://marketingwithvladimir.com/3-methods-disable-xmlrpc-pingback-wordpress/
 */
 add_filter('xmlrpc_methods', function ($methods) {
     unset($methods['pingback.ping']);

     return $methods;
 });

/**
 * Remove RSS feeds
 */
function itsme_disable_feed()
{
    wp_die(__('No feed available'));
}

add_action('do_feed', 'itsme_disable_feed', 1);
add_action('do_feed_rdf', 'itsme_disable_feed', 1);
add_action('do_feed_rss', 'itsme_disable_feed', 1);
add_action('do_feed_rss2', 'itsme_disable_feed', 1);
add_action('do_feed_atom', 'itsme_disable_feed', 1);
add_action('do_feed_rss2_comments', 'itsme_disable_feed', 1);
add_action('do_feed_atom_comments', 'itsme_disable_feed', 1);
