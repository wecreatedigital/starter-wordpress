<?php

if (getenv('ENV') !== 'local' && getenv('ENV') !== 'dev') {
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
ExpiresDefault "access plus 10 years"
ExpiresByType image/gif "access plus 10 years"
ExpiresByType image/jpeg "access plus 10 years"
ExpiresByType image/png "access plus 10 years"
ExpiresByType text/css "access plus 10 years"
ExpiresByType text/html "access plus 1 seconds"
ExpiresByType text/javascript "access plus 10 years"
ExpiresByType application/x-unknown-content-type "access plus 10 years"
ExpiresByType application/x-javascript "access plus 10 years"
</IfModule>
\n\n
EOD;

        return $content.$rules;
    }
    add_filter('mod_rewrite_rules', 'add_to_htaccess');
}
