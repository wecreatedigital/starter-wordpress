@include('flexible._start', [
  'classes' => 'fcb-gallery pb-0',
  'padding' => $default_padding,
])

<div class="row">
  <div class="offset-lg-2 col-lg-8 text-center">
    @include('flexible.content')
  </div>
</div>

<div class="row fcb-t40 no-gutters justify-content-md-center">
  @fields('gallery')
    <img loading="lazy" src="@sub('image', 'sizes', 'large')" alt="@sub('image', 'alt')" class="col-6 col-sm-6 col-md-4 img-fluid">
  @endfields
</div>

@include('flexible._end')
