<section @hassub('id') id="@sub('id')" @endsub class="fcb @hassub('padding_override') fcb-@sub('padding_override')100 @endsub fcb-text-left-block">
  <div class="container">
    <div class="row">
      <div class="col-sm-8">
        @include('flexible.content', [
          'class' => ''
        ])
      </div>
    </div>
  </div>
</section>
