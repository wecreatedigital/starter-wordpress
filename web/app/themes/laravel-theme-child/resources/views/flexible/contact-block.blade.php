<section @hassub('id') id="@sub('id')" @endsub class="fcb fcb-contact">
  <div class="row">
    <div class="offset-lg-2 col-lg-8">
      <h3 class="h2">@sub('heading')</h3>
      @sub('text')
    </div>
  </div>
  <div class="row">
    <div class="offset-lg-2 col-lg-8">
      {!! do_shortcode('[contact-form-7 id="'.get_sub_field('contact_form').'" title="Contact"]') !!}
    </div>
  </div>
</section>
