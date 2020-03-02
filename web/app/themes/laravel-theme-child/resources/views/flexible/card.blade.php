@include('flexible._start', [
  'classes' => 'fcb-contact',
  'padding' => $default_padding,
])

  <div class="container">
    <div class="row justify-content-center">
      @fields('card_item')
        <div class="col-12 col-md-4 mb-5 d-flex align-items-stretch">
          <div class="card card-block-item shadow-lg card-with-button">
            @if(get_sub_field('link') && get_sub_field('image'))
              <a href="@sub('link', 'url')">
                <img class="img-fluid rounded-0" src="@sub('image', 'url')" alt="@sub('image', 'alt')">
              </a>
            @elseif(get_sub_field('image'))
              <img class="img-fluid rounded-0" src="@sub('image', 'url')" alt="@sub('image', 'alt')">
            @endif
              <div class="card-body pb-4">
                @hassub('link')
                  <a href="@sub('link', 'url')">
                    <h3 class="card-title">@sub('heading')</h3>
                  </a>
                @else
                  <h3 class="card-title">@sub('heading')</h3>
                @endsub
                @sub('text')
                @hassub('link')
                  <a href="@sub('link', 'url')" class="btn btn-dark btn-rounded">
                    @sub('link', 'title')
                  </a>
                @endsub
              </div>
            </div>
        </div>
      @endfields
    </div>
  </div>


@include('flexible._end')
