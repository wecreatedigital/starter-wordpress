@component('components.blocks.container', [
  'classes' => 'fcb-blog',
  'paddingOverride' => 'py-50',
])

  @php $counter = 1; @endphp

  <div class="grid grid-cols-1 md:grid-cols-12 gap-x-50 gap-y-30">
    @while(have_posts()) @php the_post(); @endphp

        @if($counter == 1 || $counter == 2)
          <div class="md:col-span-6 bg-white">
            @php $imageId = get_post_thumbnail_id($post); @endphp

            <a href="@permalink" class="block">
              <img src="{{ wp_get_attachment_image_src($imageId, 'full')[0] }}"
                  alt="@title"
                  class="h-full w-full object-cover max-h-290 min-h-290 lg:max-h-340 lg:min-h-340"
              />
            </a>

            @include('partials.posts.content')
          </div>
        @else
          <div class="md:col-span-6 lg:col-span-4 bg-white">
            @php $imageId = get_post_thumbnail_id($post); @endphp

            <a href="@permalink" class="block">
              <img src="{{ wp_get_attachment_image_src($imageId, 'full')[0] }}"
                  alt="@title"
                  class="h-full w-full object-cover max-h-290 min-h-290"
              />
            </a>

            @include('partials.posts.content')
          </div>
        @endif

        @if($counter == 5)
          @php $counter = 0; @endphp
        @endif

      @php $counter++; @endphp
    @endwhile
  </div>

  <div class="mt-50">
    @if ( function_exists('wp_pagenavi') )
      <div class="text-24 items-end mx-auto text-center font-libre-baskerville">
        {!! wp_pagenavi([
          'next_text' => '',
          'prev_text' => '',
          ]) !!}
      </div>
    @endif
  </div>

@endcomponent
