<?php

add_filter('acf/load_field/name=padding_override', function ($field) {

    // reset choices
    $field['choices'] = array(
        'default' => '-- SELECT --',

        // Options
        'y' => 'Top and bottom',
        't' => 'Top',
        'b' => 'Bottom',
        'n' => 'None',
    );

    // return the field
    return $field;
});
