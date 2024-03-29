<?php

/**
 * ACF - define all alignment options here.
 *
 * @author Christopher Kelker
 * @date   29-07-2021
 */
$alignmentOptions = function ($field) {
    // reset choices
    $field['choices'] = array(
        'left' => 'Left',
        'center' => 'Centre',
        'right' => 'Right',
    );

    // return the field
    return $field;
};

// Default Alignment Options
add_filter('acf/load_field/name=alignment', $alignmentOptions);
add_filter('acf/load_field/name=links_alignment', $alignmentOptions);
add_filter('acf/load_field/name=text_alignment', $alignmentOptions);
add_filter('acf/load_field/name=heading_alignment', $alignmentOptions);

/**
 * ACF - define all theme colours here.
 *
 * @author Christopher Kelker
 * @date   29-07-2021
 */
$colourOptions = function ($field) {
    // reset choices
    $field['choices'] = array(
        'default' => '-- SELECT --',
        'transparent' => 'Transparent', // transparent
        'white' => 'White', // #FFFFFF
        'black' => 'Black', // #000000
        'pink' => 'Pink', #D2659B
        'grey-dark' => 'Dark Grey', #4F5356
        'grey-light' => 'Light Grey', #F2F2F2
        'teal' => 'Teal', #1B95AD
    );

    return $field;
};

// Default Colours Options
add_filter('acf/load_field/name=heading_colour', $colourOptions);
add_filter('acf/load_field/name=text_colour', $colourOptions);
add_filter('acf/load_field/name=background_colour', $colourOptions);
add_filter('acf/load_field/name=colour', $colourOptions);
add_filter('acf/load_field/name=box_background_colour', $colourOptions);
add_filter('acf/load_field/name=testimonials_colour', $colourOptions);

/**
 * ACF - define all heading sizes here.
 *
 * @author Christopher Kelker
 * @date   29-07-2021
 */
add_filter('acf/load_field/name=heading_size', function ($field) {
    // reset choices
    $field['choices'] = array(
        'default' => '-- SELECT --',
        'h1' => 'Heading 1',
        'h2' => 'Heading 2',
        'h3' => 'Heading 3',
        'h4' => 'Heading 4',
        'h5' => 'Heading 5',
    );

    // return the field
    return $field;
});

/**
 * ACF - define all link types here.
 *
 * @author Christopher Kelker
 * @date   29-07-2021
 */
add_filter('acf/load_field/name=link_type', function ($field) {
    // reset choices
    $field['choices'] = array(
        'default' => '-- SELECT --',
        'primary' => 'Primary',
        'secondary' => 'Secondary',
    );

    // return the field
    return $field;
});
