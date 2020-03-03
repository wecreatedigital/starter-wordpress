@php
  $h = 1;
  $default_padding = 100;
@endphp

@layouts('page_content_block')

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
    @include('flexible.accordion')
@endlayout

@layout('card_block')
    @include('flexible.card')
@endlayout

@layout('icon_block')
    @include('flexible.icon')
@endlayout

@layout('testimonial_block')
    @include('flexible.testimonial-block')
@endlayout

@php
  $h++;
@endphp

@endlayouts
