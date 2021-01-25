@php
if( isset($background_image) ) {
  $srcset = wp_get_attachment_image_srcset($background_image, 'large_square');
}
@endphp
<section
  @if( isset($srcset) && ! empty($srcset) )
    data-background-image-srcset="{{ $srcset }}"
  @endif

  @if( isset($style) )
    style="{{ $style }}"
  @endif

  @hassub('id')
    id="{{ str_replace(' ', '-', preg_replace('/\s+/', ' ', strtolower(get_sub_field('id')))) }}"
  @endsub

  class="fcb
  @isset($classes)
    {{ $classes }}
  @endisset

  @hassub('padding_override')
    {{ 'fcb-' }}@sub('padding_override'){{ $defaultPadding }}
  @endsub

  @hassub('background_colour')
    {{ 'fcb-' }}@sub('background_colour')
  @endsub
">
  @hassub('container_type')
    <div class="@sub('container_type')">
  @else
    <div class="container">
  @endsub

  {!! $slot !!}

  @hassub('container_type')
    </div>
  @endsub
</section>
