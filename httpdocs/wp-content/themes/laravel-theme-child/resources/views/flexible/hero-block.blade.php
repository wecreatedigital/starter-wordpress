<section style="background-image: url(@sub('background', 'url'))" class="fcb fcb-hero jumbotron background-image">
  <div class="contents">
    <h1>
      @sub('heading')
    </h1>
    <p class="lead m-0">
      @hassub('primary_call_to_action')
        <a target="@sub('primary_call_to_action', 'target')" class="btn btn-light btn-lg" href="@sub('primary_call_to_action', 'url')">@sub('primary_call_to_action', 'title')</a>
      @endsub
      @hassub('secondary_call_to_action')
        <a target="@sub('secondary_call_to_action', 'target')" class="btn btn-link btn-lg" href="@sub('secondary_call_to_action', 'url')">@sub('secondary_call_to_action', 'title')</a>
      @endsub
    </p>
  </div>
  <div class="overlay"></div>
</section>
