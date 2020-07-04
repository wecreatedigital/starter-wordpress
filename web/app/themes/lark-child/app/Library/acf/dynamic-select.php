<?php
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
  * FCB padding
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
          'n' => 'None',
      );

      // return the field
      return $field;
  }
  add_filter('acf/load_field/name=padding_override', 'acf_load_padding_override_choices');

 /**
  * FCB offset
  *
  * https://www.advancedcustomfields.com/resources/dynamically-populate-a-select-fields-choices/
  */
  function acf_load_column_offset_choices($field)
  {
      // reset choices
      $field['choices'] = array(
          'left' => 'Left',
          'center' => 'Centre',
          'right' => 'Right',
      );

      // return the field
      return $field;
  }
  add_filter('acf/load_field/name=column_offset', 'acf_load_column_offset_choices');

 /**
  * FCB container
  *
  * https://www.advancedcustomfields.com/resources/dynamically-populate-a-select-fields-choices/
  */
  function acf_load_container_type_choices($field)
  {

      // reset choices
      $field['choices'] = array(
          'container' => 'Fixed',
          'container-fluid' => 'Full-width',
      );

      // return the field
      return $field;
  }
  add_filter('acf/load_field/name=container_type', 'acf_load_container_type_choices');

 /**
  * FCB background colour
  *
  * https://www.advancedcustomfields.com/resources/dynamically-populate-a-select-fields-choices/
  */
  function acf_load_background_colour_choices($field)
  {

      // reset choices
      $field['choices'] = array(
          'primary' => 'Primary',
          'secondary' => 'Secondary',
          'white' => 'White',
      );

      // return the field
      return $field;
  }
  add_filter('acf/load_field/name=background_colour', 'acf_load_background_colour_choices');
