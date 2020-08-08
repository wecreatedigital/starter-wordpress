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
    @include('flexible.text-block')
@endlayout

@layout('image_block')
    @include('flexible.image-block')
@endlayout

@layout('hero_block')
    @include('flexible.hero-block')
@endlayout

@layout('left_right_image_text_block')
    @include('flexible.left-right-image-text-block')
@endlayout

@layout('latest_block')
    @include('flexible.latest-block')
@endlayout

@layout('donate_block')
    @include('flexible.donate-block')
@endlayout

@layout('gallery_block')
    @include('flexible.gallery-block')
@endlayout

@layout('contact_block')
    @include('flexible.contact-block')
@endlayout

@layout('accordion_block')
    @include('flexible.accordion-block')
@endlayout

@layout('card_block')
    @include('flexible.card-block')
@endlayout

@layout('icon_block')
    @include('flexible.icon-block')
@endlayout

@layout('testimonial_block')
    @include('flexible.testimonial-block')
@endlayout

@php
  $h++;
  $unique_id++;
@endphp

@endlayouts
