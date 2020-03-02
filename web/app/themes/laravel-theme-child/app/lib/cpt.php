<?php

/**
 * Register Custom Post types and their taxonomies here...
 *
 * @author Christopher Kelker 10-01-2020
 * @return array void
 */

$taxonomies = [
    'author' => ['testimonial', 'faq'],
    'genre' => ['faq'],
];

$cpts = [
    'testimonial' => [
        'public' => false,
        'taxonomies' => ['author', 'category'],
        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
    ],

    'faq' => [
        'public' => true,
        'taxonomies' => ['author', 'genre'],
        'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    ],
];

/**
 * FIRST REGISTER TAXONOMIES TO LATER ASSOCIATE WITH CPT
 */
foreach ($taxonomies as $taxonomy => $post_types) {
    add_action('init', function () use ($post_types, $taxonomy) {
        $taxonomy_plural = str_plural($taxonomy);
        $uc_taxonomy_plural = ucwords(str_replace('-', ' ', $taxonomy_plural));
        $uc_taxonomy_singular = ucwords(str_replace('-', ' ', $taxonomy));

        // Labels part for the GUI
        $labels = array(
            'name' => _x($uc_taxonomy_plural, 'taxonomy general name'),
            'singular_name' => _x('{$uc_taxonomy_singular}', 'taxonomy singular name'),
            'search_items' => __("Search {$uc_taxonomy_plural}"),
            'popular_items' => __("Popular {$uc_taxonomy_plural}"),
            'all_items' => __("All {$uc_taxonomy_plural}"),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __("Edit {$uc_taxonomy_singular}"),
            'update_item' => __("Update {$uc_taxonomy_singular}"),
            'add_new_item' => __("Add New {$uc_taxonomy_singular}"),
            'new_item_name' => __("New {$uc_taxonomy_singular} Name"),
            'separate_items_with_commas' => __("Separate {$taxonomy_plural} with commas"),
            'add_or_remove_items' => __("Add or remove {$taxonomy_plural}"),
            'choose_from_most_used' => __("Choose from the most used {$taxonomy_plural}"),
            'menu_name' => __($uc_taxonomy_plural),
        );

        // Now register the non-hierarchical taxonomy like tag
        register_taxonomy($taxonomy_plural, $post_types, [
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => array('slug' => $taxonomy),
        ]);
    }, 0);
}

/**
 * NOW REGISTER CPTs
 */
foreach ($cpts as $cpt => $cpt_options) {

    // Register Custom Post Type
    add_action('init', function () use ($cpt, $cpt_options) {
        $cpt_plural = str_plural($cpt);
        $uc_post_type_plural = ucwords(str_replace('-', ' ', $cpt_plural));
        $uc_post_type_singular = ucwords(str_replace('-', ' ', $cpt));

        $labels = array(
            'name' => _x($uc_post_type_plural, 'Post Type General Name', 'text_domain'),
            'singular_name' => _x($uc_post_type_singular, 'Post Type Singular Name', 'text_domain'),
            'menu_name' => __($uc_post_type_plural, 'text_domain'),
            'name_admin_bar' => __($uc_post_type_singular, 'text_domain'),
            'archives' => __("{$uc_post_type_singular} Archives", 'text_domain'),
            'attributes' => __("{$uc_post_type_singular} Attributes", 'text_domain'),
            'parent_item_colon' => __("Parent {$uc_post_type_singular}:", 'text_domain'),
            'all_items' => __("All {$uc_post_type_plural}", 'text_domain'),
            'add_new_item' => __("Add New {$uc_post_type_singular}", 'text_domain'),
            'add_new' => __("Add New {$uc_post_type_singular}", 'text_domain'),
            'new_item' => __("New {$uc_post_type_singular}", 'text_domain'),
            'edit_item' => __("Edit {$uc_post_type_singular}", 'text_domain'),
            'update_item' => __("Update {$uc_post_type_singular}", 'text_domain'),
            'view_item' => __("View {$uc_post_type_singular}", 'text_domain'),
            'view_items' => __("View {$uc_post_type_plural}", 'text_domain'),
            'search_items' => __("Search {$uc_post_type_singular}", 'text_domain'),
            'not_found' => __('Not found', 'text_domain'),
            'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
            'featured_image' => __('Featured Image', 'text_domain'),
            'set_featured_image' => __('Set featured image', 'text_domain'),
            'remove_featured_image' => __('Remove featured image', 'text_domain'),
            'use_featured_image' => __('Use as featured image', 'text_domain'),
            'insert_into_item' => __("Insert into {$cpt}", 'text_domain'),
            'uploaded_to_this_item' => __("Uploaded to this {$cpt}", 'text_domain'),
            'items_list' => __("{$uc_post_type_plural} list", 'text_domain'),
            'items_list_navigation' => __("{$uc_post_type_plural} list navigation", 'text_domain'),
            'filter_items_list' => __("Filter {$cpt_plural} list", 'text_domain'),
        );
        $args = array(
            'label' => __('post_type', 'text_domain'),
            'description' => __($cpt, 'text_domain'),
            'labels' => $labels,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'supports' => $cpt_options['supports'],
            'taxonomies' => $cpt_options['taxonomies'],
            'menu_position' => 5,
            'can_export' => true,
            'has_archive' => true,
            'rewrite' => array('with_front' => false),

            // --- PUBLIC OPTIONS ---
            'exclude_from_search' => isset($cpt_options['public']) ? $cpt_options['public'] : false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'public' => isset($cpt_options['public']) ? $cpt_options['public'] : true,
            'publicly_queryable' => isset($cpt_options['public']) ? $cpt_options['public'] : true,
        );
        register_post_type($cpt, $args);
    }, 0);
}
