<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Truncate by word, not character
 *
 * @author Dean Appleton-Claydon
 * @date   2020-08-08
 * @param  [type]     $string             [description]
 * @param  [type]     $your_desired_width [description]
 * @return [type]                         [description]
 */
function truncate($string, $your_desired_width)
{
    $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
    $parts_count = count($parts);

    $length = 0;
    $last_part = 0;
    for (; $last_part < $parts_count; ++$last_part) {
        $length += strlen($parts[$last_part]);
        if ($length > $your_desired_width) {
            break;
        }
    }

    return implode(array_slice($parts, 0, $last_part));
}

/**
 * Facades don't work in Blade at the moment with Roots Sage, so created
 * a helper to make use of the Str helper class.
 *
 * @author Christopher Kelker
 * @param  string $string
 * @return \Illuminate\Support\Str string
 */
if ( ! function_exists('str_of')) {
    function str_of(string $value)
    {
        return Str::of($value);
    }
}

/**
 * Facades don't work in Blade at the moment with Roots Sage, so created
 * a helper to make use of the View helper class.
 *
 * @author Christopher Kelker
 * @param  string $string
 * @return \Illuminate\Support\Facades\View
 */
if ( ! function_exists('viewExists')) {
    function viewExists(string $value)
    {
        return View::exists($value);
    }
}

if ( ! function_exists('menu_for')) {
    function menu_for(string $menuName)
    {
        $locations = get_nav_menu_locations();

        if ( ! isset($locations[$menuName])) {
            return collect([]);
        }

        $menu = wp_get_nav_menu_object($locations[$menuName]);
        $menuItems = wp_get_nav_menu_items($menu->term_id);

        $menuItems = collect($menuItems);

        return collect($menuItems)->reject(function ($item) {
            return ! empty($item->menu_item_parent);
        })->transform(function ($menuItem, $key) use ($menuItems) {
            $menuItem->childMenuItems = $menuItems->where('menu_item_parent', $menuItem->ID);

            return $menuItem;
        });
    }
}

if ( ! function_exists('fullAddress')) {
    function fullAddress()
    {
        $address = [
            get_field('address_1', 'option'),
            get_field('address_2', 'option'),
            get_field('town', 'option'),
            get_field('county', 'option'),
            get_field('postcode', 'option'),
            get_field('country', 'option'),
        ];

        return implode(', ', array_filter($address, function ($value) {
            return ! is_null($value) && $value !== '';
        }));
    }
}

if ( ! function_exists('headingSize')) {
    function headingSize(string $size, array $options = [])
    {
        if ( ! array_key_exists($size, Config::get('theme.fonts'))) {
            dd("Cannot find heading options for '{$size}'");
        }

        $heading = Config::get('theme.fonts.'.$size);

        if ( ! empty($options)) {
            foreach ($options as $key => $value) {
                Arr::set($heading, $key, $value);
            }
        }

        return implode(' ', Arr::flatten($heading));
    }
}
