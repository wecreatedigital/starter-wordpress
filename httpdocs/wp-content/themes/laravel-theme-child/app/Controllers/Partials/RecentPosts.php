<?php

namespace App\Controllers\Partials;

/**
 * Influenced from https://jasonbaciulis.com/modern-wordpress-theme-development-with-sage-9/#controller-partials
 * Simply use $recent_posts to retrieve all recent posts
 */
trait RecentPosts
{
    public function recent_posts()
    {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        return $query = new \WP_Query($args);
    }
}
