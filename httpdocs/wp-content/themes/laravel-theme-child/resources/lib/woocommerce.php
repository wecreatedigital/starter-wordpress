<?php

add_filter('woocommerce_register_post_type_product', 'wc_modify_product_post_type');

function wc_modify_product_post_type($args)
{
    $args['supports'][] = 'revisions';

    return $args;
}
