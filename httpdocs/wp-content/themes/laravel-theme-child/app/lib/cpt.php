<?php
/**
 * Sample CPT - PLEASE UNCOMMENT function.php
 *
 * Feel free to use:
 * - https://generatewp.com/taxonomy/
 * - https://generatewp.com/post-type/
 */

 function services_post_type()
 {
     $labels = array(
         'name' => _x('Services', 'Post Type General Name', 'text_domain'),
         'singular_name' => _x('Service', 'Post Type Singular Name', 'text_domain'),
         'menu_name' => __('Services', 'text_domain'),
         'parent_item_colon' => __('Parent Service:', 'text_domain'),
         'all_items' => __('All Services', 'text_domain'),
         'view_item' => __('View Service', 'text_domain'),
         'add_new_item' => __('Add New Service', 'text_domain'),
         'add_new' => __('Add New', 'text_domain'),
         'edit_item' => __('Edit Service', 'text_domain'),
         'update_item' => __('Update Service', 'text_domain'),
         'search_items' => __('Search Services', 'text_domain'),
         'not_found' => __('Not found', 'text_domain'),
         'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
     );
     $args = array(
         'label' => __('post_type', 'text_domain'),
         'description' => __('Service', 'text_domain'),
         'labels' => $labels,
         'supports' => array(),
         'hierarchical' => true,
         'public' => true,
         'show_ui' => true,
         'show_in_menu' => true,
         'show_in_nav_menus' => true,
         'show_in_admin_bar' => true,
         'supports' => array('title'),
         'menu_position' => 5,
         'can_export' => false,
         'has_archive' => true,
         'exclude_from_search' => false,
         'publicly_queryable' => true,
         'capability_type' => 'post',
     );
     register_post_type('service', $args);
 }

 add_action('init', 'services_post_type', 0);
