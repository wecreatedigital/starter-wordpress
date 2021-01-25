<?php

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
