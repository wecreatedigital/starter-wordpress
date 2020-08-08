@php
  $image = get_sub_field('image');
@endphp
@if( $image )

  @component('components.blocks.container', [
    'classes' => 'fcb-image-block',
    'padding' => $default_padding,
  ])

  {!! wp_get_attachment_image( $image['ID'], 'full_width' , '', [
    'class' => 'img-fluid'
  ]) !!}

  @endcomponent

@endif
