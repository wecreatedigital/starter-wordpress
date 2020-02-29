<section @hassub('id') id="@sub('id')" @endsub class="fcb @hassub('padding_override') fcb-@sub('padding_override')100 @endsub fcb-text-center-block">
  <div class="container">
    <div class="row">
      <div class="offset-sm-2 col-sm-8">
        @include('flexible.content', [
          'class' => ''
        ])
      </div>
    </div>
    <div class="row text-center">
      <div class="offset-lg-2 col-lg-8">
        <p class="lead m-0">
          @hassub('primary_call_to_action')
            <a target="@sub('primary_call_to_action', 'target')" class="btn btn-primary btn-lg" href="@sub('primary_call_to_action', 'url')">@sub('primary_call_to_action', 'title')</a>
          @endsub
          @hassub('secondary_call_to_action')
            <a target="@sub('secondary_call_to_action', 'target')" class="btn btn-link btn-lg" href="@sub('secondary_call_to_action', 'url')">@sub('secondary_call_to_action', 'title')</a>
          @endsub
        </p>
      </div>
    </div>
  </div>
</section>
