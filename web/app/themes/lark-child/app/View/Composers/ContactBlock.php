<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Query;

class ContactBlock extends Composer
{
    /**
     * Determine that only the blocks and container needs these variables.
     *
     * @var array
     */
    protected static $views = [
        'flexible.blocks.contact',
    ];

    public function with()
    {
        $args = array(
            'post_type' => 'wpcf7_contact_form',
            'order' => 'ASC',
        );

        $contactForm = (new WP_Query($args))->posts[0];

        return compact('contactForm');
    }
}
