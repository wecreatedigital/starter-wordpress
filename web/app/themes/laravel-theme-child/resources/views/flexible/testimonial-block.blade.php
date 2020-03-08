@include('flexible._start', [
  'classes' => 'fcb-testimonial',
  'padding' => $default_padding,
])

<div class="row no-gutters">
  <div class="fcb-col-center fcb-align-text col-md-8">
    @include('flexible.content', [
      'classes' => ''
    ])
  </div>
</div>

<div class="container">
  <div class="testimonial-slick-slider">
    @php
      $post_objects = get_sub_field('testimonials')
    @endphp
    @if( $post_objects )
      @foreach( $post_objects as $post )
        @php
          setup_postdata($GLOBALS['post'] =& $post);
        @endphp
        @include("partials.testimonial")
      @endforeach
      @php
        wp_reset_postdata();
      @endphp
    @endif
  </div>
</div>

@include('flexible._end')
