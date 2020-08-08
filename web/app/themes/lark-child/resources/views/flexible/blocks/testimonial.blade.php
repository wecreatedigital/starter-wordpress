@component('components.blocks.container', [
  'classes' => 'fcb-testimonial',
  'padding' => $default_padding,
])

  @php
    $post_objects = get_sub_field('testimonials')
  @endphp
  
  @if( $post_objects )

    <div class="row no-gutters">
      <div class="fcb-col-center fcb-align-text col-md-8">
        @include('flexible.content', [
          'classes' => ''
        ])
      </div>
    </div>

    <div class="container">
      <div class="testimonial-slick-slider">
          @foreach( $post_objects as $post )
            @php
              setup_postdata($GLOBALS['post'] =& $post);
            @endphp
            @include("partials.testimonial")
          @endforeach
          @php
            wp_reset_postdata();
          @endphp
      </div>
    </div>

  @endif

@endcomponent
