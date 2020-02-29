@php
  $position = get_sub_field('image_position');
  $url = '';
  $background_image = get_sub_field('image');
  if( $background_image ) {
    $url = $background_image['sizes']['large_square'];
  }
@endphp

@include('flexible._start', [
  'classes' => 'fcb-left-image-right-text',
  'padding' => 0,
])

<div class="row">
  <div class="@hassub('padding_override'){{ 'fcb-' }}@sub('padding_override'){{ '100' }}@endsub col-sm-12 col-md-7 @if( $position == "left" ) order-2 @else order-2 order-sm-2 order-md-1 @endif">
    @include('flexible.content', [
      'classes' => ''
    ])
  </div>
  <div style="background-image: url({{ $url }})" class="fcb-col-image col-sm-12 col-md-5 @if( $position == "left" ) order-1 @else order-1 order-sm-1 order-md-2 @endif"></div>
</div>

@include('flexible._end')
