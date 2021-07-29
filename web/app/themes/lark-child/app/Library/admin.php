<?php
/**
 * Remove the sample custom template declared in the parent theme
 *
 * @author Dean Appleton-Claydon
 * @date   2019-10-15
 * @param  array     $templates   an array of templates declared by both child and parent themes
 * @return array                  return the modified array
 */
function tfc_remove_page_templates($templates)
{
    unset($templates['views/template-custom.blade.php']);

    return $templates;
}
add_filter('theme_page_templates', 'tfc_remove_page_templates');

/**
 * Screen options display excerpt by default (new users only)
 */
 function wpse_edit_post_show_excerpt()
 {
     $user = wp_get_current_user();
     $unchecked = get_user_meta($user->ID, 'metaboxhidden_post', true);
     if ( ! is_array($unchecked)) {
         update_user_meta($user->ID, 'metaboxhidden_post', ['postexcerpt' => 1]);
     } else {
         $key = array_search('postexcerpt', $unchecked);
         if (false !== $key) {
             array_splice($unchecked, $key, 1);
             update_user_meta($user->ID, 'metaboxhidden_post', $unchecked);
         }
     }
 }
 add_action('admin_init', 'wpse_edit_post_show_excerpt', 10);

/**
 * Dynamic year for the footer
 *
 * @author Dean Appleton-Claydon
 * @date   2019-12-06
 */
function year_shortcode()
{
    $year = date('Y');

    return $year;
}
add_shortcode('year', 'year_shortcode');

/**
 * Inspired from https://kinsta.com/blog/wordpress-maintenance-mode/#manually-enabling-wordpress-maintenance-mode-with-code
 * Uses ACF field to trigger maintenance mode
 *
 * @author Dean Appleton-Claydon
 * @date   2019-12-27
 */
function wp_maintenance_mode()
{
    if (filter_var(getenv('MAINTENANCE'), FILTER_VALIDATE_BOOLEAN)) {
        if ( ! current_user_can('edit_themes') && ! is_user_logged_in()) {
            wp_die('<h1>Website under maintenance</h1><br>We are performing scheduled maintenance. The website will be back online shortly.');
        }
    }
}
add_action('get_header', 'wp_maintenance_mode');

/**
 * Remove the continued link
 * @author Dean Appleton-Claydon
 * @date   2020-02-29
 * @param  string     $more
 * @return string
 */
function remove_excerpt_link($more)
{
    global $post;

    return '...';
}
add_filter('excerpt_more', 'remove_excerpt_link');

/**
 * Add excerpt to pages
 *
 * @author Christopher Kelker
 * @date   29-07-2021
 */
add_post_type_support( 'page', 'excerpt' );
