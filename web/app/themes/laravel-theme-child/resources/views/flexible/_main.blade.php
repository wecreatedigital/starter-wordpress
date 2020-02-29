@php
  $h = 1;
@endphp

@layouts('page_content_block')

@layout('text_left_block')
    @include('flexible.text-left-block')
@endlayout

@layout('text_right_block')
    @include('flexible.text-right-block')
@endlayout

@layout('text_center_block')
    @include('flexible.text-center-block')
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

@php
  $h++;
@endphp

@endlayouts
