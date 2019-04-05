<?php

/**
 * Basic htaccess WP rules
 */
function output_htaccess($rules)
{
    $new_rules = '
<Files "xmlrpc.php">
  Order deny,allow
  Deny from all
</Files>';

    return $rules.$new_rules;
}
add_filter('mod_rewrite_rules', 'output_htaccess');

/**
 * Enable SSL
 */
if (getenv('SSL_ENABLED') === 'yes') {
    function enable_ssl_htaccess($rules)
    {
        $new_rules = '

<IfModule mod_rewrite.c>
  # Force ssl
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
';

        return $rules.$new_rules;
    }
    add_filter('mod_rewrite_rules', 'enable_ssl_htaccess');
}

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

/**
 * Strict-Transport-Security
 */
if ( ! empty($_SERVER['HTTPS'])) {
    function add_hsts_header($headers)
    {
        $headers['strict-transport-security'] = 'max-age=31536000; includeSubDomains';

        return $headers;
    }

    add_filter('wp_headers', 'add_hsts_header');
}

/**
 * Content-Security-Policy
 */
function add_csp_header($headers)
{
    $headers['content-security-policy'] = "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';";

    return $headers;
}
add_filter('wp_headers', 'add_csp_header');
