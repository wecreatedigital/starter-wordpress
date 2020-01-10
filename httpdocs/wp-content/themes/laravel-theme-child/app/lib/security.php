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
if (filter_var(getenv('SSL_ENABLED'), FILTER_VALIDATE_BOOLEAN)) {
    function enable_ssl_htaccess($rules)
    {
        $new_rules = '
<IfModule mod_rewrite.c>
  # Force ssl
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>';

        return $rules.$new_rules;
    }
    add_filter('mod_rewrite_rules', 'enable_ssl_htaccess');
}

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
// function add_csp_header($headers)
// {
//     $headers['content-security-policy'] = "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';";
//
//     return $headers;
// }
// add_filter('wp_headers', 'add_csp_header');

/**
 * X-Frame-Options
 */
add_action('send_headers', 'send_frame_options_header', 10, 0);
