<?php
/*
* Function creates post duplicate as a draft and redirects then to the edit post screen
*/
function rd_duplicate_post_as_draft()
{
    global $wpdb;
    if ( ! (isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'rd_duplicate_post_as_draft' == $_REQUEST['action']))) {
        wp_die('No post to duplicate has been supplied!');
    }

    /*
    * get the original post id
    */
    $post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
    /*
    * and all the original post data then
    */
    $post = get_post($post_id);

    /*
    * if you don't want current user to be the new post author,
    * then change next couple of lines to this: $new_post_author = $post->post_author;
    */
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    /*
    * if post data exists, create the post duplicate
    */
    if (isset($post) && $post != null) {

/*
* new post data array
*/
        $args = array(
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_author' => $new_post_author,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_name' => $post->post_name,
            'post_parent' => $post->post_parent,
            'post_password' => $post->post_password,
            'post_status' => 'draft',
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'to_ping' => $post->to_ping,
            'menu_order' => $post->menu_order,
        );

        /*
        * insert the post by wp_insert_post() function
        */
        $new_post_id = wp_insert_post($args);

        /*
        * get all current post terms ad set them to the new post draft
        */
        $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        /*
        * duplicate all post meta just in two SQL queries
        */
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
        if (count($post_meta_infos) != 0) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }
            $sql_query .= implode(' UNION ALL ', $sql_query_sel);
            $wpdb->query($sql_query);
        }

        /*
        * finally, redirect to the edit post screen for the new draft
        */
        wp_redirect(admin_url('post.php?action=edit&post='.$new_post_id));
        exit;
    }
    wp_die('Post creation failed, could not find original post: '.$post_id);
}
add_action('admin_action_rd_duplicate_post_as_draft', 'rd_duplicate_post_as_draft');

/*
* Add the duplicate link to action list for post_row_actions
*/
function rd_duplicate_post_link($actions, $post)
{
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="admin.php?action=rd_duplicate_post_as_draft&amp;post='.$post->ID.'" title="Duplicate this item" rel="permalink">Duplicate</a>';
    }

    return $actions;
}

add_filter('post_row_actions', 'rd_duplicate_post_link', 10, 2);
add_filter('page_row_actions', 'rd_duplicate_post_link', 10, 2); /* for pages */
add_filter('portfolio_row_actions', 'rd_duplicate_post_link', 10, 2); /* a custom post called portfolio */

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
 * Force permalinks to be postname from the point of accepting child theme
 *
 * @author Dean Appleton-Claydon
 * @date   2019-11-17
 */
function change_permalinks()
{
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%/');
    $wp_rewrite->flush_rules();
}
add_action('after_switch_theme', 'change_permalinks');

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
