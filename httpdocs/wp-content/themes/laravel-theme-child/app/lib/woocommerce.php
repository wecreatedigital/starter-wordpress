<?php

/**
 * Add revisions to Products CPT
 * Won't store all the meta data but gives feedback in the revision logs
 *
 * @author Dean Appleton-Claydon
 * @date   2019-09-02
 * @param  [type]     $args [description]
 * @return [type]           [description]
 */
function wc_modify_product_post_type($args)
{
    $args['supports'][] = 'revisions';

    return $args;
}
add_filter('woocommerce_register_post_type_product', 'wc_modify_product_post_type');
