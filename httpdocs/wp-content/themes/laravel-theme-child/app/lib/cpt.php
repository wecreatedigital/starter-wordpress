<?php
/**
 * Sample CPT - PLEASE UNCOMMENT function.php
 *
 * Feel free to use:
 * - https://generatewp.com/taxonomy/
 * - https://generatewp.com/post-type/
 */

/**
 * A custom post type example with single posts
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
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
        'menu_position' => 20,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => false,
        'capability_type' => 'post',
        'hierarchical' => false,
        'public' => true,
        'publicly_queryable' => true,
    );
    register_post_type('service', $args);
}

add_action('init', 'services_post_type', 0);

/**
 * A custom post type example WITHOUT single posts
 */
function testimonials_post_type()
{
    $labels = array(
        'name' => _x('Testimonials', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('Testimonial', 'Post Type Singular Name', 'text_domain'),
        'menu_name' => __('Testimonials', 'text_domain'),
        'parent_item_colon' => __('Parent Testimonial:', 'text_domain'),
        'all_items' => __('All testimonials', 'text_domain'),
        'view_item' => __('View Testimonial', 'text_domain'),
        'add_new_item' => __('Add New Testimonial', 'text_domain'),
        'add_new' => __('Add New', 'text_domain'),
        'edit_item' => __('Edit Testimonial', 'text_domain'),
        'update_item' => __('Update Testimonial', 'text_domain'),
        'search_items' => __('Search testimonials', 'text_domain'),
        'not_found' => __('Not found', 'text_domain'),
        'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
    );
    $args = array(
        'label' => __('post_type', 'text_domain'),
        'description' => __('Testimonial', 'text_domain'),
        'labels' => $labels,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
        'menu_position' => 20,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => false,
        'capability_type' => 'post',
    );
    register_post_type('testimonial', $args);
}

add_action('init', 'testimonials_post_type', 0);
