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
     * Limits the amount of characters to be displayed in an excerpt and appends
     * trailing periods.
     * @author Brandon Hull
     * @date 2019-05-28
     */
    public static function excerpt($limit)
    {
        $excerpt = explode(' ', get_the_excerpt(), $limit);
        if (count($excerpt) >= $limit) {
            array_pop($excerpt);
            $excerpt = implode(' ', $excerpt).'...';
        } else {
            $excerpt = implode(' ', $excerpt);
        }
        $excerpt = preg_replace('`\[[^\]]*\]`', '', $excerpt);

        return $excerpt;
    }
}
