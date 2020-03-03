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

/*
 * Returns boolean if the first block exists or not
 *
 * @author Dean Appleton-Claydon
 * @date   2020-03-02
 *
 * @param  mixed      $fc_layouts    array of possible field names
 * @param  string     $fc_field      name of FCB field
 *
 * Adapted from: https://gist.github.com/secretstache/9f0a93a9953361edb7bb
*/
function content_block_exists_first($fc_layouts, $fc_field = 'page_content_block')
{
    if (class_exists('acf')) {
        if ( ! is_array($fc_layouts)) {
            $fc_layouts = [$fc_layouts];
        }

        if (have_rows($fc_field)) {
            $content_blocks = get_field($fc_field);

            if (in_array($content_blocks[0]['acf_fc_layout'], $fc_layouts)) {
                return true;
            }
        }

        return false;
    }
}
/*
 * Check which header area is being used and adds acf fields
 *
 * @author Russell Mitchell
 * @date   2020-03-03

 *
 * Adapted from: https://www.advancedcustomfields.com/resources/register-fields-via-php/
*/

if (env('DISABLE_HAMBURGER')):

acf_add_local_field_group(array(
	'key' => 'group_nav_button',
	'title' => 'Nav Button',
	'fields' => array (
		array (
			'key' => 'field_nav_button',
			'label' => 'Navigation Button',
			'name' => 'navigation_button',
			'type' => 'link',
      'instructions' => 'This is the link to the header navigation button. Please update ',
		)
	),
  'location' => array (
			array (
				array (
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'acf-options',
				),
			),
		),
));
endif;
