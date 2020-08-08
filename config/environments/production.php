<?php
/**
 * Configuration overrides for WP_ENV === 'production'
 */

use Roots\WPConfig\Config;

Config::define('WP_CACHE', true);
Config::define('WPCACHEHOME', '/var/www/vhosts/'.env('WP_DOMAIN').'/web/app/plugins/wp-super-cache/');

Config::define('DEV_DISABLED_PLUGINS', serialize([
    'query-monitor/query-monitor.php',
]));
