<section @hassub('id') id="@sub('id')" @endsub class="fcb @hassub('padding_override') fcb-@sub('padding_override')100 @endsub fcb-gallery">
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
</section>
