<?php
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

/**
 * jQuery is outdated in WP and has vulnerabilities, but we don't want break the WP admin_init
 * @author Dean Appleton-Claydon
 * @date   2020-03-02
 *
 * https://stackoverflow.com/questions/1157531/how-can-i-remove-jquery-from-the-frontside-of-my-wordpress
 */
if ( ! is_admin()) {
    add_action('wp_enqueue_scripts', 'my_jquery_enqueue', 11);
}
function my_jquery_enqueue()
{
    wp_deregister_script('jquery');
    wp_register_script('jquery', get_stylesheet_directory_uri().'/assets/scripts/jquery-3.4.1.slim.min.js', false, null, true);
    wp_enqueue_script('jquery');
}
