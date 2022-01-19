@component('components.blocks.container', [
  'classes' => 'relative',
  'overridePadding' => ' ',
  'overrideContainerClasses' => ' ',
])

  <div class="relative">
    <img src="@sub('image', 'url')"
         alt="@sub('image', 'alt')"
         class="z-2 relative w-full max-h-175 md:max-h-650 md:max-h-unset object-cover"
    />

    <div class="w-full max-w-940 flex flex-col items-start lg:items-center md:flex-row mx-auto px-20 md:px-30 py-25 {{ 'bg-' . get_sub_field('box_background_colour') }} lg:transform lg:-translate-y-50% z-4 relative">
      @include('flexible.partials.heading', [
        'alignmentClasses' => 'text-start',
        'sizeOptions' => ['margin' => 'mb-15 lg:mb-0'],
      ])

      @group('link')
        @hassub('link')
            <x-link href="{{ get_sub_field('link')['url'] }}"
                    style="{{ get_sub_field('link_type') }}"
                    target="{{ get_sub_field('link')['target'] }}"
                    colour="{{ get_sub_field('colour') }}"
                    class="flex-shrink-0"
            >
              @sub('link', 'title')
            </x-link>
        @endsub
      @endgroup
    </div>
  </div>

@endcomponent
