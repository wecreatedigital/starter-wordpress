<section @hassub('id') id="@sub('id')" @endsub class="fcb fcb-left-image-right-text">
  <div class="row">
    <div class="col-12 col-sm-12 col-md-6">
      <h2>@sub('heading')</h2>
      <h3>@sub('sub_heading')</h3>
      @sub('text')
    </div>
    <div class="col-12 col-sm-12 col-md-6">
      <img src="@sub('image', 'url')" alt="@sub('image', 'alt')" class="img-fluid mt-4 mt-md-0">
    </div>
  </div>
</section>
