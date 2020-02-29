<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class App extends Controller
{
    // https://jasonbaciulis.com/modern-wordpress-theme-development-with-sage-9/#advanced-custom-fields-module
    // protected $acf = 'header';

    public function siteName()
    {
        return get_bloginfo('name');
    }

    public static function title()
    {
        if (is_home()) {
            if ($home = get_option('page_for_posts', true)) {
                return get_the_title($home);
            }

            return __('Latest Posts', 'sage');
        }
        if (is_archive()) {
            return get_the_archive_title();
        }
        if (is_search()) {
            return sprintf(__('Search Results for %s', 'sage'), get_search_query());
        }
        if (is_404()) {
            return __('Not Found', 'sage');
        }

        return get_the_title();
    }

    /**
    * Custom is page template
    * @author Brandon Hull
    * @param  string  $template [description]
    * @return boolean
    */
    public static function page_template($template = '')
    {
        return is_page_template('views/'.$template.'.blade.php');
    }

    /**
    * Create a function to output an image array of an acf sub field
    *
    * @author Russell Mitchell
    * @date   2019-02-19
    * @param  string $field            Name of ACF field
    * @param  string $image_class      Add a class that passes to <img>
    * @param  string $image_size       Assumes 'full' unless specified
    * @param  boolean $is_sub_field    Whether the ACF field is part of a repeater or not
    * @param  boolean $url_only        If you just need the image URL...
    * @param  integer $post_id         If you need to get a specific field from another post
    * @return string                   Either HTML or URL
    */
    public static function acf_image($field, $image_class, $image_size = '', $is_sub_field = false, $url_only = false, $post_id = false)
    {
        $image = get_field($field, $post_id);
        if ($is_sub_field) {
            $image = get_sub_field($field, $post_id);
        }

        if ( ! empty($image)) {
            return false;
        }

        if ( ! empty($image_size) && array_key_exists($image_size, $image['sizes'])) {
            $url = $image['sizes'][ $image_size ];
            $width = $image['sizes'][ $image_size.'-width' ];
            $height = $image['sizes'][ $image_size.'-height' ];
        } else {
            $url = $image['url'];
            $width = $image['width'];
            $height = $image['height'];
        }
        if ($url_only) {
            return $url;
        }

        return '<img src="'.$url.'" class="'.$image_class.'" height="'.$height.'" width="'.$width.'" alt="'.$image['alt'].'">';
    }
}
