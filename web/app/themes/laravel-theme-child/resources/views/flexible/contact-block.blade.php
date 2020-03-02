@include('flexible._start', [
  'classes' => 'fcb-contact',
  'padding' => $default_padding,
])

<div class="row">
  <div class="offset-lg-2 col-lg-8 text-center">
    @include('flexible.content', [
      'classes' => 'fcb-b40'
    ])
  </div>
</div>

<div class="row">
  <div class="offset-lg-2 col-lg-8">
    {!! do_shortcode('[contact-form-7 id="'.get_sub_field('contact_form').'" title="Contact"]') !!}
  </div>
</div>

@include('flexible._end')
