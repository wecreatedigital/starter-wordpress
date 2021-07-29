<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Block extends Composer
{
    /**
     * Determine that only the blocks needs these variables.
     *
     * @var array
     */
    protected static $views = [
        'flexible.blocks.*',
    ];

    public function with()
    {
        return ['alignment' => $this->alignment()];
    }

    private function alignment()
    {
        switch (get_sub_field('alignment')) {
          case 'left':
            $contentAlignment = 'mr-auto';

            break;
          case 'center':
            $contentAlignment = 'mx-auto';

            break;
          case 'right':
            $contentAlignment = 'ml-auto';

            break;
          default:
            // CENTER
            $contentAlignment = 'mx-auto';

            break;
        }

        return $contentAlignment;
    }
}
