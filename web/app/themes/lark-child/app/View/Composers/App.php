<?php

namespace App\View\Composers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    protected function with()
    {
        return Arr::only(
            Config::get('theme'),
            ['siteName', 'homeUrl']
        );
    }
}
