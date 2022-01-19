@component('components.blocks.container')

  <div class="{{ $alignment }}">
    @include('flexible.partials.heading')

    @include('flexible.partials.text')

    <div class="max-w-1250 grid grid-cols-1 gap-20">
      @fields('gallery')
        <div class="grid grid-rows-1 md:grid-cols-12 gap-20">
          @fields('images')
            <img loading="lazy"
                 src="@sub('image', 'url')"
                 alt="@sub('image', 'alt')"
                 class="object-cover w-full
                        @hassub('small')
                          min-h-330 max-h-330
                          md:col-span-4
                        @else
                          min-h-315 max-h-315
                          md:col-span-6
                        @endsub"
            />
          @endfields
        </div>
      @endfields
    </div>

    @include('flexible.partials.links')
  </div>

@endcomponent
