<?php

function fetchInstagramPosts() {
    $instagram_connect = Instagram_Connect::object();

    if(class_exists('ACF')):
        $query_string = build_query(
            array(
                'access_token' => get_option('instagram_access_token'),
                'client_secret' => get_field('instagram_app_secret', 'option'),
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
                'limit' => get_field('amount_of_instagram_posts', 'option')
            )
        );
    else:
        $query_string = build_query(
            array(
                'access_token' => get_option('instagram_access_token'),
                'client_secret' => get_option('instagram_app_secret'),
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
                'limit' => get_option('amount_of_instagram_posts')
            )
        );
    endif;

    $headers = array();
    $args = array( 'headers' => $headers, 'sslverify' => false, 'body' => array() );


    $response = wp_remote_get( "https://graph.instagram.com/me/media?$query_string", $args) ;
    if( empty($response) )
        return false;

    return wp_remote_retrieve_body($response);
}
