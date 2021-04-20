<?php

use Illuminate\Support\Facades\Config;

/**
 * Create content when theme activated
 *
 * @author Dean Appleton-Claydon
 * @date   2020-01-21
 */
function create_page_on_theme_activation()
{
    // If we have a front page, don't do anything else
    if (get_option('page_on_front')) {
        return true;
    }

    // Create a front page
    update_option('page_on_front', wecreate_insert_post([
        'post_title' => 'Home',
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]));
    update_option('show_on_front', 'page');

    // Create a blog page
    update_option('page_for_posts', wecreate_insert_post([
        'post_title' => 'Blog',
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]));

    // Create a privacy policy page
    update_option('wp_page_for_privacy_policy', wecreate_insert_post([
        'post_title' => 'Privacy policy',
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]));

    // This is our sitemap
    $posts = [
        [
            'post_title' => 'About',
            'post_type' => 'page',
            'child_pages' => [
                [
                    'post_title' => 'Our team',
                    'post_type' => 'page',
                    'child_pages' => [
                        [
                            'post_title' => 'Support',
                            'post_type' => 'page',
                        ],
                    ],
                ], [
                    'post_title' => 'Careers',
                    'post_type' => 'page',
                ],
            ],
        ],
        [
            'post_title' => 'Contact',
            'post_type' => 'page',
        ],
        [
            'post_title' => 'Cookie policy',
            'post_type' => 'page',
        ],
        [
            'post_title' => 'Terms and conditions',
            'post_type' => 'page',
        ],
    ];

    // Now import the array and insert posts
    foreach ($posts as $post) {
        $parent_id = wecreate_insert_post($post);
        if (isset($post['child_pages'])) {
            foreach ($post['child_pages'] as $_p) {
                $_parent_id = wecreate_insert_post($_p, $parent_id);

                if (isset($_p['child_pages'])) {
                    foreach ($_p['child_pages'] as $__p) {
                        wecreate_insert_post($__p, $_parent_id);
                    }
                }
            }
        }
    }

    // Add terms
    // $taxonomy_terms = [
    //     'category' => [
    //         'Company updates',
    //     ],
    // ];
    //
    // foreach ($taxonomy_terms as $taxonomy => $terms) {
    //     foreach ($terms as $term) {
    //         wp_insert_term($term, $taxonomy);
    //     }
    // }

    for ($x = 0; $x <= 30; $x++) {
        wp_insert_post(array(
            'post_title' => 'Test Post: '.($x + 1),
            'post_content' => 'post content goes here...',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_excerpt' => 'Post excerpt goes here... Post excerpt goes here... Post excerpt goes here... Post excerpt goes here...',
            'post_author' => get_current_user_id(),
        ));
    }

    include_once(ABSPATH.'wp-admin/includes/plugin.php');

    $pluginsToActivate = Config::get('theme.auto-activiated-plugins');
    $installedPlugins = get_plugins();

    if ( ! empty($pluginsToActivate)) {
        foreach ($pluginsToActivate as $plugin) {
            if (is_plugin_active($$plugin)) {
                continue;
            }

            if ( ! array_key_exists($plugin, $installedPlugins)) {
                continue;
            }

            activate_plugin($plugin);
        }
    }

    // WP settings
    update_option('blogdescription', '');
    update_option('admin_email', 'dean@wecreate.digital');
    update_option('new_admin_email', 'dean@wecreate.digital');
    update_option('users_can_register', 0);
    update_option('WPLANG', 'en_GB');
    update_option('timezone_string', 'Europe/London');
    update_option('blog_public', 0);
    update_option('default_comment_status', 'closed');
    update_option('comment_registration', 1);
    update_option('comment_moderation', 1);
}
add_action('after_switch_theme', 'create_page_on_theme_activation');

function wecreate_insert_post($post, $parent_id = 0)
{
    // Required for checking whether a post already exists
    if ( ! function_exists('post_exists') && current_user_can('administrator')) {
        require_once(ABSPATH.'wp-admin/includes/post.php');
    }

    // What if the page exists already, we don't want to duplicate it!
    if ($post_id = post_exists($post['post_title'], '', '', $post['post_type']) > 0) {
        return $post_id;
    }

    if ($parent_id > 0) {
        $post['post_parent'] = $parent_id;
    }

    if ( ! isset($post['post_status'])) {
        $post['post_status'] = 'publish';
    }

    if ( ! isset($post['post_author'])) {
        $post['post_author'] = get_current_user_id();
    }

    return wp_insert_post($post);
}
