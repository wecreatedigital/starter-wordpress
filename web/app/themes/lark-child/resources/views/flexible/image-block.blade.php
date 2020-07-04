@php
  $image = get_sub_field('image');
@endphp
@if( $image )

  @include('flexible._start', [
    'classes' => 'fcb-image-block',
    'padding' => $default_padding,
  ])

  {!! wp_get_attachment_image( $image['ID'], 'full_width' , '', [
    'class' => 'img-fluid'
  ]) !!}

  @include('flexible._end')

@endif
