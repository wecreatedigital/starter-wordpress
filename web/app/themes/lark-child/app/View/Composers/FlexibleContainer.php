<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Config;
use Roots\Acorn\View\Composer;

class FlexibleContainer extends Composer
{
    /**
     * Determine that only the blocks and container needs these variables.
     *
     * @var array
     */
    protected static $views = [
        'layouts.flexible',
        'components.blocks.container',
        'flexible.blocks.*',
        'components.heading',
    ];

    /**
     * Return the flexible config variables located within:
     * web/app/themes/lark-child/config/flexible.php
     *
     * @author Christopher Kelker
     * @date   2021-01-25 11:42:47
     * @return array
     */
    public function with()
    {
        return array_merge(Config::get('flexible'), [
            'object' => $this->object(),
        ]);
    }

    private function object()
    {
        return get_queried_object();
    }
}
