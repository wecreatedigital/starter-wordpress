@include('flexible._start', [
  'classes' => 'fcb-gallery',
  'padding' => $default_padding,
])

@php( $i = 1 )

<div class="row">
  <div class="offset-lg-2 col-lg-8 text-center fcb-b20">
    @include('flexible.content')
  </div>
</div>

<div class="row no-gutters justify-content-md-center">
  @fields('gallery')
    <img src="@sub('image', 'sizes', 'large')" alt="@sub('image', 'alt')" class="col col-sm-6 col-md-4 img-fluid">
    @if ($i % 3 == 0)
      </div><div class="row no-gutters justify-content-md-center">
    @endif
    @php( $i++ )
  @endfields
</div>

@include('flexible._end')
