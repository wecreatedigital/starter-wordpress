<?php

/**
 * ACF options https://www.advancedcustomfields.com/add-ons/options-page/
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
        'page_title' => 'Theme Settings',
        'menu_slug' => 'acf-options',
        'position' => '2.1',
    ]);
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
 * Add the following in Google API Console:
 * Google Maps Directions API
 * Google Maps Distance Matrix API
 * Google Maps Elevation API
 * Google Maps Geocoding API
 * Google Maps JavaScript API
 * Google Places API Web Service
 * Google Static Maps API
 *
 * @author Dean Appleton-Claydon
 * @date   2019-09-02
 * @param  [type]     $api [description]
 * @return [type]          [description]
 */
function my_acf_google_map_api($api)
{
    $api['key'] = getenv('GOOGLE_API');

    return $api;
}

add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

/**
 * Example dynamic select box
 *
 * https://www.advancedcustomfields.com/resources/dynamically-populate-a-select-fields-choices/
 */
 function acf_load_padding_override_choices($field)
 {

     // reset choices
     $field['choices'] = array(
         'y' => 'Top and bottom',
         'x' => 'Left and right',
         't' => 'Top',
         'b' => 'Bottom',
         'a' => 'All sides',
     );

     // return the field
     return $field;
 }
 add_filter('acf/load_field/name=padding_override', 'acf_load_padding_override_choices');

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

 /**
  * Increment an ACF field, whether an option field or a post ID
  * Usage: increment_field('number_times_purchased', 'option');
  *
  * @author Dean Appleton-Claydon
  * @date   2019-12-27
  *
  * @param  string     $name    ACF field reference
  * @param  mixed      $post_id Either a post ID or 'option'
  * @param  integer    $amount  a fixed number, no decimal
  */
 function increment_field($name, $post_id = false, $amount = 1)
 {
     $count = (int) get_field($name, $post_id);

     $count = $count + $amount;

     update_field($name, $count, $post_id);
 }

  /**
   * Decrement an ACF field, whether an option field or a post ID
   * Usage: decrement_field('stock_level', 'option', 3);
   *
   * @author Dean Appleton-Claydon
   * @date   2019-12-27
   *
   * @param  string     $name    ACF field reference
   * @param  mixed      $post_id Either a post ID or 'option'
   * @param  integer    $amount  a fixed number, no decimal
   */
  function decrement_field($name, $post_id = false, $amount = 1)
  {
      $count = (int) get_field($name, $post_id);

      $count = $count - $amount;

      update_field($name, $count, $post_id);
  }
