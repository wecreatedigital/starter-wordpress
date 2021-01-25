@component('components.blocks.container', [
  'classes' => 'fcb-icon',
])

  <div class="container">
    <div class="row justify-content-center">
      @fields('icon_item')
        <div class="card col-12 col-md-3">
          @if(get_sub_field('link') && get_sub_field('image'))
            <a href="@sub('link', 'url')">
              <img loading="lazy" class="rounded-0" src="@sub('image', 'sizes', 'thumbnail')" alt="@sub('image', 'alt')">
            </a>
          @elseif(get_sub_field('image'))
            <img loading="lazy" class="rounded-0" src="@sub('image', 'sizes', 'thumbnail')" alt="@sub('image', 'alt')">
          @endif

          <div class="card-body">
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
      @endfields
    </div>
  </div>

@endcomponent
