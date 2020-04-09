<?php

if (getenv('WP_ENV') == 'production') {
    function add_to_htaccess($rules)
    {
        $content = <<<EOD
\n
<IfModule mod_deflate.c>
# Compress HTML, CSS, JavaScript, Text, XML and fonts
AddOutputFilterByType DEFLATE application/javascript
AddOutputFilterByType DEFLATE application/rss+xml
AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
AddOutputFilterByType DEFLATE application/x-font
AddOutputFilterByType DEFLATE application/x-font-opentype
AddOutputFilterByType DEFLATE application/x-font-otf
AddOutputFilterByType DEFLATE application/x-font-truetype
AddOutputFilterByType DEFLATE application/x-font-ttf
AddOutputFilterByType DEFLATE application/x-javascript
AddOutputFilterByType DEFLATE application/xhtml+xml
AddOutputFilterByType DEFLATE application/xml
AddOutputFilterByType DEFLATE font/opentype
AddOutputFilterByType DEFLATE font/otf
AddOutputFilterByType DEFLATE font/ttf
AddOutputFilterByType DEFLATE image/svg+xml
AddOutputFilterByType DEFLATE image/x-icon
AddOutputFilterByType DEFLATE text/css
AddOutputFilterByType DEFLATE text/html
AddOutputFilterByType DEFLATE text/javascript
AddOutputFilterByType DEFLATE text/plain
AddOutputFilterByType DEFLATE text/xml
# Remove browser bugs (only needed for really old browsers)
BrowserMatch ^Mozilla/4 gzip-only-text/html
BrowserMatch ^Mozilla/4\.0[678] no-gzip
BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
Header append Vary User-Agent
</IfModule>
\n
# Check that the expires module has been installed
<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType image/gif "access plus 1 month"
ExpiresByType image/jpg "access plus 1 month"
ExpiresByType image/jpeg "access plus 1 month"
ExpiresByType image/png "access plus 1 month"
ExpiresByType image/x-icon "access plus 1 month"
ExpiresByType text/css "access plus 1 month"
ExpiresByType text/html "access plus 1 seconds"
ExpiresByType text/javascript "access plus 1 month"
ExpiresByType text/x-javascript "access plus 1 month"
ExpiresByType application/pdf "access plus 1 month"
ExpiresByType application/x-unknown-content-type "access plus 1 month"
ExpiresByType application/x-javascript "access plus 1 month"
ExpiresDefault "access plus 2 days"
</IfModule>
\n\n
EOD;

        return $content.$rules;
    }
    add_filter('mod_rewrite_rules', 'add_to_htaccess');
}

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
    add_filter('rest_jsonp_enabled', '__return_false');

    // Remove emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('after_setup_theme', 'remove_excess_links_etc');

/**
 * Remove comment styles
 *
 * @author Dean Appleton-Claydon
 * @date   2019-08-21
 */
function remove_recent_comments_style()
{
    global $wp_widget_factory;
    remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
}
add_action('widgets_init', 'remove_recent_comments_style');

/**
 * Remove the Gutenburg styles
 *
 * @author Dean Appleton-Claydon
 * @date   2019-08-21
 */
function remove_block_css()
{
    wp_dequeue_style('wp-block-library');
}
add_action('wp_enqueue_scripts', 'remove_block_css', 100);

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

//Remove JQuery migrate
function remove_jquery_migrate($scripts)
{
    if ( ! is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];

        if ($script->deps) { // Check whether the script has any dependencies
            $script->deps = array_diff($script->deps, array(
                'jquery-migrate',
            ));
        }
    }
}
add_action('wp_default_scripts', 'remove_jquery_migrate');
