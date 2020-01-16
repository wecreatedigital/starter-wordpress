<section @hassub('id') id="@sub('id')" @endsub class="fcb fcb-text-center-block">
  <div class="row">
    <div class="offset-lg-2 col-lg-8">
      @sub('text')
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
</section>
