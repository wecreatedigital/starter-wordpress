@include('flexible._start', [
  'classes' => 'fcb-contact',
  'padding' => $default_padding,
])
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
  <div>
    <div class="testimonial">
      @query([
        'post_type' => 'testimonials'
      ])

      @posts
        @include('partials.testimonial')
      @endposts
    </div>
  </div>

@include('flexible._end')
