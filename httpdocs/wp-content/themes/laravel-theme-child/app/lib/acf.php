<?php

/**
 * ACF options https://www.advancedcustomfields.com/add-ons/options-page/
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page();
}

/**
 * ACF filter to speed up backend loading time
 * https://awesomeacf.com/snippets/speed-acf-backend-loading-time/
 * @var [type]
 */
// add_filter('acf/settings/remove_wp_meta_box', '__return_true');

/**
 * https://www.advancedcustomfields.com/blog/google-maps-api-settings/
 *
 * @author Dean Appleton-Claydon
 * @date   2019-09-02
 * @param  [type]     $api [description]
 * @return [type]          [description]
 */
function my_acf_google_map_api($api)
{
    $api['key'] = getenv('GOOGLEMAPS');

    return $api;
}

add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

/**
 * Example dynamic select box
 *
 * https://www.advancedcustomfields.com/resources/dynamically-populate-a-select-fields-choices/
 */
 function acf_load_color_field_choices($field)
 {

     // reset choices
     $field['choices'] = array();

     // get the textarea value from options page without any formatting
     $choices = get_field('my_select_values', 'option', false);

     // explode the value so that each line is a new array piece
     $choices = explode("\n", $choices);

     // remove any unwanted white space
     $choices = array_map('trim', $choices);

     // loop through array and add to field 'choices'
     if (is_array($choices)) {
         foreach ($choices as $choice) {
             $field['choices'][ $choice ] = $choice;
         }
     }

     // return the field
     return $field;
 }
 // add_filter('acf/load_field/name=color', 'acf_load_color_field_choices');