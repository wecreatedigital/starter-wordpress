<?php

namespace App\View\Composers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Roots\Acorn\View\Composer;

class Heading extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'components.heading',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        return Arr::only(
            Config::get('theme'),
            ['h']
        );
    }
}
