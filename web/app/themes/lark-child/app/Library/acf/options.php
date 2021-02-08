<?php

use Illuminate\Support\Facades\Config;

/**
 * ACF options https://www.advancedcustomfields.com/add-ons/options-page/
 */
if (function_exists('acf_add_options_page')) {
    $parent = acf_add_options_page([
        'page_title' => 'Theme Settings',
        'menu_slug' => 'acf-options',
        'position' => '2.1',
        'redirect' => false,
    ]);

    $child_header_page = acf_add_options_sub_page(array(
        'page_title' => __('404'),
        'menu_title' => __('404 Page'),
        'parent_slug' => $parent['menu_slug'],
    ));

    acf_add_local_field_group(array(
        'key' => 'group_404',
        'title' => '404 page Settings',
        'fields' => array(
            array(
                'key' => 'field_5ed2a298c6cd7',
                'label' => 'Commonly used pages',
                'name' => 'commonly_used_pages',
                'type' => 'post_object',
                'instructions' => 'Choose pages that you think might be best next steps for someone landing on a 404 page',
                'allow_null' => 1,
                'multiple' => 1,
                'return_format' => 'id',
                'ui' => 1,
            ),
            array(
                'key' => 'field_5ed2a298c6cdb',
                'label' => 'Supporting text 1',
                'name' => 'supporting_text_1',
                'type' => 'wysiwyg',
                'instructions' => 'This field provides the relevant link for the main call to action button in the header. You can specify both the link and the label using this single button.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => $child_header_page['menu_slug'],
                ),
            ),
        ),
    ));
}

/*
 * Check which header area is being used and adds acf fields
 * Adapted from: https://www.advancedcustomfields.com/resources/register-fields-via-php/
 *
 * @author Russell Mitchell
 * @date   2020-03-03
*/
if (function_exists('acf_add_options_sub_page') && getenv('DISABLE_HAMBURGER')) {
    $child_header_page = acf_add_options_sub_page(array(
        'page_title' => __('Header'),
        'menu_title' => __('Header'),
        'parent_slug' => $parent['menu_slug'],
    ));

    acf_add_local_field_group(array(
        'key' => 'group_nav_button',
        'title' => 'Header Call To Action',
        'fields' => array(
            array(
                'key' => 'field_nav_button',
                'label' => 'Button link',
                'name' => 'header_call_to_action_link',
                'type' => 'link',
                'instructions' => 'This field provides the relevant link for the main call to action button in the header. You can specify both the link and the label using this single button.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => $child_header_page['menu_slug'],
                ),
            ),
        ),
    ));
}

/**
 * Hide the 'Custom Fields' menu from WordPress Admin when on live.
 */
add_filter('acf/settings/show_admin', function () {
    if ( ! is_user_logged_in()) {
        return false;
    }

    return in_array(
        wp_get_current_user()->user_email,
        Config::get('flexible.developers')
    );
});
