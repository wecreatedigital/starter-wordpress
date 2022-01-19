<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Query;

class LatestPosts extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'flexible.blocks.latest-posts',
    ];

    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        return [
            'latestPosts' => new WP_Query([
                'orderby' => 'published',
                'order' => 'DESC',
                'posts_per_page' => 3,
            ]),
        ];
    }
}
