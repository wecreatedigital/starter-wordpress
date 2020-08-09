<?php

/**
 * Start curl request for instagram
 * @author Steven Hardy
 * @date 2020-02-19
 *
 *  @param  [type]     $url     URL to retrieve data from
 */
function fetchInstagram($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

/**
 * Get instagram
 * Based on the options fields for Instagram within theme settings. We are passing this through to the fetchInstagram
 * function to be able to retrieve the latest instagram posts. This is limited by the number of posts set in the theme options.
 *
 * @author Steven Hardy
 * @date 2020-02-19
 */
function instagramData()
{
    $access_key = get_field('instagram_access_token', 'option');
    $data = fetchInstagram('https://graph.instagram.com/me/media?fields=media_url,permalink,caption,media_type&access_token='.$access_key);

    return $data;
}

/*
 * Check which header area is being used and adds acf fields
 * Adapted from: https://www.advancedcustomfields.com/resources/register-fields-via-php/
 *
 * @author Russell Mitchell
 * @date   2020-03-03
*/
if (function_exists('acf_add_options_sub_page')) {
    acf_add_options_sub_page(array(
        'page_title' => __('Instagram'),
        'menu_title' => __('Instagram'),
        'parent_slug' => 'acf-options',
    ));

    acf_add_local_field_group(array(
        'key' => 'instagram_field_group',
        'title' => 'Access token',
        'fields' => array(
            array(
                'key' => 'instagram_access_token',
                'label' => 'Instagram access token',
                'name' => 'instagram_access_token',
                'type' => 'text',
                'instructions' => 'Please follow https://guidelines.wecreate.digital/social-media/instagram-api for generating your Instagram access token',
            ),
            array(
                'key' => 'amount_of_instagram_posts',
                'label' => 'Number of Instagram posts',
                'name' => 'amount_of_instagram_posts',
                'type' => 'number',
                'min' => 3,
                'max' => 12,
                'default_value' => 6,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'acf-options-instagram',
                ),
            ),
        ),
    ));
}
