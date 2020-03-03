@include('flexible._start', [
  'classes' => 'fcb-testimonial',
  'padding' => $default_padding,
])

@php
  if( $h == 1 ) {
    $heading = 'h1';
    $sub_heading = 'h2';
  } elseif( $h >= 2 && $h <= 4 ) {
    $heading = 'h2';
    $sub_heading = 'h3';
  } else {
    $heading = 'h3';
    $sub_heading = 'h4';
  }
@endphp

<div class="row">
    <div class="col-12 col-md-12 col-sm-12">
      @hassub('testimonial_intro_header')
        <{{ $heading }} class="mb-3">
          @sub('testimonial_intro_header')
        </{{ $heading }}>
      @endsub

        @hassub('testimonial_intro_header')
          <{{ $sub_heading }} class="mb-3">
            @sub('testimonial_intro_block')
          </{{ $sub_heading }}>
        @endsub
    </div>
</div>
    <div class="testimonial-slick-slider testimonial-item">

      @php
        $post_objects = get_sub_field('testimonial_item')
      @endphp
      @if( $post_objects )
        @foreach( $post_objects as $post )
            @php
              setup_postdata($GLOBALS['post'] =& $post);
            @endphp
            <div class="col-12 col-md-6 col-lg-4 mb-30">
              @include("partials.testimonial")
            </div>
        @endforeach
        @php wp_reset_postdata(); @endphp
      @endif

    </div>

@include('flexible._end')
