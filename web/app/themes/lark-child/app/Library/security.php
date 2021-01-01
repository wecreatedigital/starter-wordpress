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
 * Security headers, use https://securityheaders.com/ to verify
 *
 * Strict-Transport-Security: helps to protect websites against man-in-the-middle attacks
 * X-XSS-Protection: to stop pages from loading when they detect reflected cross-site scripting
 * X-Content-Type-Options: prevent MIME type sniffing
 * X-Frame-Options: to indicate whether or not a browser should be allowed to render a page in a frame, iframe, embed or object tag
 * Content-Security-Policy: prevent cross-site scripting (XSS), clickjacking and other code injection attacks
 * Permissions-Policy: allows site owners to enable and disable certain web platform features on their own pages and those they embed
 * Referrer-Policy: controls how much referrer information should be included with requests
 */
function add_security_headers($headers)
{
    if ( ! empty($_SERVER['HTTPS'])) {
      $headers['Strict-Transport-Security'] = 'max-age=15552000; includeSubDomains';
    }
    $headers['X-XSS-Protection'] = "1; mode=block";
    $headers['X-Content-Type-Options'] = "nosniff";
    $headers['X-Frame-Options'] = "SAMEORIGIN";
    $headers['Content-Security-Policy'] = "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';"; //connect-src * data: *.tawk.to 'unsafe-inline' 'unsafe-eval';
    $headers['Permissions-Policy'] = "accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()";
    $headers['Referrer-Policy'] = "strict-origin-when-cross-origin";

    return $headers;
}
add_filter('wp_headers', 'add_security_headers');

header_remove("X-Powered-By");
