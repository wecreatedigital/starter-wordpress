@php
  $h = 1; // Heading for SEO
  $unique_id = 1; // For counting the number of accordions
  $default_padding = 100;

  if( ! isset($post_id) ) { // For blog home
    $post_id = false;
  }
@endphp

@layouts('page_content_block', $post_id)

@layout('text_block')
    @include('flexible.blocks.text')
@endlayout

@layout('image_block')
    @include('flexible.blocks.image')
@endlayout

@layout('hero_block')
    @include('flexible.blocks.hero')
@endlayout

@layout('left_right_image_text_block')
    @include('flexible.blocks.text-image')
@endlayout

@layout('latest_block')
    @include('flexible.blocks.latest')
@endlayout

@layout('donate_block')
    @include('flexible.blocks.donate')
@endlayout

@layout('gallery_block')
    @include('flexible.blocks.gallery')
@endlayout

@layout('contact_block')
    @include('flexible.blocks.contact')
@endlayout

@layout('accordion_block')
    @include('flexible.blocks.accordion')
@endlayout

@layout('card_block')
    @include('flexible.blocks.card')
@endlayout

@layout('icon_block')
    @include('flexible.blocks.icon')
@endlayout

@layout('testimonial_block')
    @include('flexible.blocks.testimonial')
@endlayout

@php
  $h++;
  $unique_id++;
@endphp

@endlayouts
