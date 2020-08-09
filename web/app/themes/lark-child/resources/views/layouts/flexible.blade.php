@php
  $h = 1; // Heading for SEO
  $unique_id = 1; // For counting the number of accordions
  $default_padding = 100;

  if( ! isset($post_id) ) { // For blog home
    $post_id = false;
  }
@endphp

@layouts('page_content_block', $post_id)

@layout('accordion_block')
    @include('flexible.blocks.accordion')
@endlayout

@layout('card_block')
    @include('flexible.blocks.card')
@endlayout

@layout('contact_block')
    @include('flexible.blocks.contact')
@endlayout

@layout('donate_block')
    @include('flexible.blocks.donate')
@endlayout

@layout('hero_block')
    @include('flexible.blocks.hero')
@endlayout

@layout('icon_block')
    @include('flexible.blocks.icon')
@endlayout

@layout('image_block')
    @include('flexible.blocks.image')
@endlayout

@layout('instagram_block')
    @include('flexible.blocks.instagram')
@endlayout

@layout('gallery_block')
    @include('flexible.blocks.gallery')
@endlayout

@layout('latest_block')
    @include('flexible.blocks.latest')
@endlayout

@layout('left_right_image_text_block')
    @include('flexible.blocks.text-image')
@endlayout

@layout('testimonial_block')
    @include('flexible.blocks.testimonial')
@endlayout

@layout('text_block')
    @include('flexible.blocks.text')
@endlayout

@php
  $h++;
  $unique_id++;
@endphp

@endlayouts
