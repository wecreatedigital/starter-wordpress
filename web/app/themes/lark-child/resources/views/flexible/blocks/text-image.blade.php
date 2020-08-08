@php
  $position = get_sub_field('image_position');
@endphp

@component('components.blocks.container', [
  'classes' => 'fcb-text-image',
  'padding' => 0,
])

  <div class="row">
    <div class="@hassub('padding_override'){{ 'fcb-' }}@sub('padding_override'){{ '100' }}@endsub col-sm-12 col-md-7 @if( $position == "left" ) order-2 @else order-2 order-sm-2 order-md-1 @endif">
      @include('flexible.content', [
        'classes' => ''
      ])
    </div>
    <div data-background-image-srcset="{{ wp_get_attachment_image_srcset(get_sub_field('image'), 'large_square') }}" class="fcb-col-image background-image col-sm-12 col-md-5 @if( $position == "left" ) order-1 @else order-1 order-sm-1 order-md-2 @endif"></div>
  </div>

@endcomponent
