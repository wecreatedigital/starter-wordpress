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
        'container',
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
            'overridePaddingFieldValue' => $this->overridePadding(),
        ]);
    }

    private function object()
    {
        return get_queried_object();
    }

    public function overridePadding()
    {
        if (
          empty(get_sub_field('padding_override'))
          || is_null(get_sub_field('padding_override'))
          || get_sub_field('padding_override') == 'default'
        ) {
            return '';
        }

        switch (get_sub_field('padding_override')) {
            case('t'):
                $paddingOverride = 'pb-0 pt-50 md:pt-100';

                break;
            case('b'):
                $paddingOverride = 'pt-0 pb-50 md:pb-100';

                break;
            case('y'):
                $paddingOverride = 'py-50 md:py-100';

                break;
            case('n'):
                $paddingOverride = 'p-0';

                break;
            default:
                $paddingOverride = '';

                break;
        }

        return $paddingOverride;
    }
}
