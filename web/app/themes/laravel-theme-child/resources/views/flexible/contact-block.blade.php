<section @hassub('id') id="@sub('id')" @endsub class="fcb @hassub('padding_override') fcb-@sub('padding_override')100 @endsub fcb-contact">
  <div class="container">
    <div class="row">
      <div class="offset-lg-2 col-lg-8 text-center">
        @include('flexible.content', [
          'class' => 'fcb-b40'
        ])
      </div>
    </div>
    <div class="row">
      <div class="offset-lg-2 col-lg-8">
        {!! do_shortcode('[contact-form-7 id="'.get_sub_field('contact_form').'" title="Contact"]') !!}
      </div>
    </div>
  </div>
</section>
