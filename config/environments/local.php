<?php
/**
 * Configuration overrides for WP_ENV === 'local'
 */

use Roots\WPConfig\Config;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', false);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', false);
Config::define('SCRIPT_DEBUG', true);

ini_set('display_errors', '1');

// Enable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', false);

Config::define('DEV_DISABLED_PLUGINS', serialize([
    'autoptimize/autoptimize.php',
    'wp-super-cache/wp-cache.php',
    'ithemes-security-pro/ithemes-security-pro.php',
    'disable-json-api/disable-json-api.php',
]));
