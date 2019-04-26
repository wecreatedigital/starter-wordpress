<?php

namespace App;

use Roots\Sage\Container;

/**
 * Get the sage container.
 *
 * @param string $abstract
 * @param array  $parameters
 * @param Container $container
 * @return Container|mixed
 */
function sage($abstract = null, $parameters = [], Container $container = null)
{
    $container = $container ?: Container::getInstance();
    if ( ! $abstract) {
        return $container;
    }

    return $container->bound($abstract)
        ? $container->makeWith($abstract, $parameters)
        : $container->makeWith("sage.{$abstract}", $parameters);
}

/**
 * Get / set the specified configuration value.
 *
 * If an array is passed as the key, we will assume you want to set an array of values.
 *
 * @param array|string $key
 * @param mixed $default
 * @return mixed|\Roots\Sage\Config
 * @copyright Taylor Otwell
 * @link https://github.com/laravel/framework/blob/c0970285/src/Illuminate/Foundation/helpers.php#L254-L265
 */
function config($key = null, $default = null)
{
    if (is_null($key)) {
        return sage('config');
    }
    if (is_array($key)) {
        return sage('config')->set($key);
    }

    return sage('config')->get($key, $default);
}

/**
 * @param string $file
 * @param array $data
 * @return string
 */
function template($file, $data = [])
{
    return sage('blade')->render($file, $data);
}

/**
 * Retrieve path to a compiled blade view
 * @param $file
 * @param array $data
 * @return string
 */
function template_path($file, $data = [])
{
    return sage('blade')->compiledPath($file, $data);
}

/**
 * @param $asset
 * @return string
 */
function asset_path($asset)
{
    return sage('assets')->getUri($asset);
}

/**
 * @param string|string[] $templates Possible template files
 * @return array
 */
function filter_templates($templates)
{
    $paths = apply_filters('sage/filter_templates/paths', [
        'views',
        'resources/views',
    ]);
    $paths_pattern = '#^('.implode('|', $paths).')/#';

    return collect($templates)
        ->map(function ($template) use ($paths_pattern) {
            /** Remove .blade.php/.blade/.php from template names */
            $template = preg_replace('#\.(blade\.?)?(php)?$#', '', ltrim($template));

            /** Remove partial $paths from the beginning of template names */
            if (strpos($template, '/')) {
                $template = preg_replace($paths_pattern, '', $template);
            }

            return $template;
        })
        ->flatMap(function ($template) use ($paths) {
            return collect($paths)
                ->flatMap(function ($path) use ($template) {
                    return [
                        "{$path}/{$template}.blade.php",
                        "{$path}/{$template}.php",
                    ];
                })
                ->concat([
                    "{$template}.blade.php",
                    "{$template}.php",
                ]);
        })
        ->filter()
        ->unique()
        ->all();
}

/**
 * @param string|string[] $templates Relative path to possible template files
 * @return string Location of the template
 */
function locate_template($templates)
{
    return \locate_template(filter_templates($templates));
}

/**
 * Determine whether to show the sidebar
 * @return bool
 */
function display_sidebar()
{
    static $display;
    isset($display) || $display = apply_filters('sage/display_sidebar', false);

    return $display;
}

/**
 * Custom Helpers
 */

function siteName()
{
    return get_bloginfo('name');
}
  function title()
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
 function acf_image($field, $image_class, $image_size = '', $is_sub_field = false, $url_only = false, $post_id = false)
 {
     if ($is_sub_field) {
         $image = get_sub_field($field, $post_id);
     } else {
         $image = get_field($field, $post_id);
     }
     if ( ! empty($image)) {
         if ( ! empty($image_size) && array_key_exists($image_size, $image['sizes'])) {
             $url = $image['sizes'][ $image_size ];
             $width = $image['sizes'][ $image_size.'-width' ];
             $height = $image['sizes'][ $image_size.'-height' ];
         } else {
             $url = $image['url'];
             $width = $image['width'];
             $height = $image['height'];
         }
         if ($url_only == true) {
             return $url;
         }

         return '<img src="'.$url.'" size="'.$image_size.'" class="'.$image_class.'" height="'.$height.'" width="'.$width.'" alt="'.$image['alt'].'">';
     }
 }

/**
 * Custom is page template
 * @author Brandon Hull
 * @param  string  $template [description]
 * @return boolean
 */
 function page_template($template = '')
 {
     $template = 'views/'.$template.'.blade.php';

     return is_page_template($template);
 }
