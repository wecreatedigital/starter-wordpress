<?php
/*
Plugin Name: Simple Environment plugin disabler
Description:  Disables plugins based on environment settings.
Author: Kamil Grzegorczyk
Version: 0.1
*/

if (defined('DEV_DISABLED_PLUGINS')) {
    $plugins_to_disable = unserialize(DEV_DISABLED_PLUGINS);

    if ( ! empty($plugins_to_disable) && is_array($plugins_to_disable)) {
        require_once(dirname(__FILE__).'/disable-plugins.php');
        $utility = new DisablePlugins($plugins_to_disable);

        // part below is optional but for me it is crucial
        // error_log('Locally disabled plugins: '.var_export($plugins_to_disable, true));
    }
}
