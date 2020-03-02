<?php

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
