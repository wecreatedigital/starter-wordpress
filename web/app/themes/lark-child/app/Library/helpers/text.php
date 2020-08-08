<?php
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
