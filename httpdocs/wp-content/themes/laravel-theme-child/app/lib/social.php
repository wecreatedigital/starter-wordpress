<?php

/**
 * Often requested, lightweight solution to quickly generate sharer URLs
 *
 * @author Dean Appleton-Claydon
 * @date   2019-12-27
 *
 * @param  [type]     $url     URL to the page to be shared
 * @param  [type]     $text    Title text for the page to be shared
 * @param  string     $excerpt (optional) only used for LinkedIn and email
 * @return array               An array of sharer URLs
 */
function sharer_links($url, $text, $excerpt = '', $media = '')
{
    $encoded_excerpt = urlencode($excerpt);
    $encoded_text = urlencode($text);

    return [
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.$url,
        'twitter' => 'https://twitter.com/intent/tweet?url='.$url.'&text='.$encoded_text,
        'pinterest' => 'https://pinterest.com/pin/create/button/?url='.$url.'&media='.$media.'&description='.$encoded_text,
        'linkedin' => 'https://www.linkedin.com/shareArticle?mini=true&url='.$url.'&title='.$encoded_text.'&summary='.$encoded_excerpt.'&source='.get_bloginfo('name'),
        'email' => 'mailto:?&subject='.$text.'&body='.$excerpt.' '.$url,
    ];
}
