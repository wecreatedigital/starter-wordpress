<?php

use Illuminate\Support\Facades\Config;

/*
 * Modify TinyMCE editor to remove H1 - this should be set in templates/FCBs only
 *
 * From: https://www.calliaweb.co.uk/code/modify-tinymce-editor/
 */
add_filter('tiny_mce_before_init', function ($init) {
    // Add block format elements you want to show in dropdown
    $init['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Address=address;Pre=pre';

    $init['plugins'] = collect(explode(',', $init['plugins']))
    ->reject(function ($plugin) {
        return in_array($plugin, Config::get('theme.tinymce.hide-unused-buttons'));
    })->values()->implode(',');

    $init['menubar'] = false;

    return $init;
});

// Define filters to remove any unneeded buttons from advanced TinyMCE editor
function filter_tadv_used_plugins($buttons)
{
    $buttons = collect($buttons)
    ->reject(function ($button) {
        return in_array($button, Config::get('theme.tinymce.hide-unused-buttons'));
    })
    ->toArray();

    return $buttons;
}

// add the filters after the Advanced TinyMCE plugin has taken affect
add_filter('mce_buttons', 'filter_tadv_used_plugins', 1000, 1);
add_filter('mce_buttons_2', 'filter_tadv_used_plugins', 1000, 1);
add_filter('mce_buttons_3', 'filter_tadv_used_plugins', 1000, 1);
add_filter('mce_buttons_4', 'filter_tadv_used_plugins', 1000, 1);
