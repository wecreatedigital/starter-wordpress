<?php
 /**
  * Increment an ACF field, whether an option field or a post ID
  * Usage: increment_field('number_times_purchased', 'option');
  *
  * @author Dean Appleton-Claydon
  * @date   2019-12-27
  *
  * @param  string     $name    ACF field reference
  * @param  mixed      $post_id Either a post ID or 'option'
  * @param  integer    $amount  a fixed number, no decimal
  */
 function increment_field($name, $post_id = false, $amount = 1)
 {
     $count = (int) get_field($name, $post_id);

     $count = $count + $amount;

     update_field($name, $count, $post_id);
 }

  /**
   * Decrement an ACF field, whether an option field or a post ID
   * Usage: decrement_field('stock_level', 'option', 3);
   *
   * @author Dean Appleton-Claydon
   * @date   2019-12-27
   *
   * @param  string     $name    ACF field reference
   * @param  mixed      $post_id Either a post ID or 'option'
   * @param  integer    $amount  a fixed number, no decimal
   */
  function decrement_field($name, $post_id = false, $amount = 1)
  {
      $count = (int) get_field($name, $post_id);

      $count = $count - $amount;

      update_field($name, $count, $post_id);
  }

/*
 * Returns boolean if the first block exists or not
 *
 * @author Dean Appleton-Claydon
 * @date   2020-03-02
 *
 * @param  mixed      $fc_layouts    array of possible field names
 * @param  string     $fc_field      name of FCB field
 *
 * Adapted from: https://gist.github.com/secretstache/9f0a93a9953361edb7bb
*/
function content_block_exists_first($fc_layouts, $fc_field = 'page_content_block')
{
    if (class_exists('acf')) {
        if ( ! is_array($fc_layouts)) {
            $fc_layouts = [$fc_layouts];
        }

        if (have_rows($fc_field)) {
            $content_blocks = get_field($fc_field);

            if (in_array($content_blocks[0]['acf_fc_layout'], $fc_layouts)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Extending DOM-based Routing by FCB
 *
 * @author Dean Appleton-Claydon
 * @date   2020-07-04
 * @param  array     $classes existing body classes
 * @return array     existing body classes with fcb classes
 */
function edit_body_classes($classes)
{
    if (class_exists('acf') && have_rows('page_content_block')) {
        $content_blocks = get_field('page_content_block');

        foreach ($content_blocks as $block) {
            if (isset($block['acf_fc_layout'])) {
                $classes[] = 'has-'.str_replace('_', '-', $block['acf_fc_layout']);
            }
        }

        return $classes;
    }
}
add_filter('body_class', 'edit_body_classes');
