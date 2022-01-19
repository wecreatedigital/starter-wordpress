@component('components.blocks.container')

  <div class="max-w-895 flex flex-col mx-auto">
    @include('flexible.partials.heading')

    @include('flexible.partials.text')
  </div>

  <div class="max-w-1040 flex flex-col mx-auto mt-50">
    <ul class="flex flex-row flex-wrap items-stretch justify-center -m-15">
      @fields('cards')
        @group('card')
          <li class="max-w-315 flex flex-col items-start m-15">
            @if (isset(get_sub_field('link')['link']['url']) && ! empty(get_sub_field('link')['link']['url']))
              <a href="{{ get_sub_field('link')['link']['url'] }}" class="block w-full">
                <img src="@sub('image', 'url')"
                     alt="@sub('image', 'alt')"
                     class="object-center object-cover max-h-200 w-full"
                />
              </a>
            @else
              <img src="@sub('image', 'url')"
                   alt="@sub('image', 'alt')"
                   class="object-center object-cover max-h-200 w-full"
              />
            @endif

            <div class="h-full px-20 py-50 bg-white flex flex-col items-start">
              @if (isset(get_sub_field('link')['link']['url']) && ! empty(get_sub_field('link')['link']['url']))
                <a href="{{ get_sub_field('link')['link']['url'] }}" class="block w-full">
                  @include('flexible.partials.heading', [
                    'default' => 'h3',
                  ])
                </a>
              @else
                @include('flexible.partials.heading', [
                  'default' => 'h3',
                ])
              @endif

              @include('flexible.partials.text', [
                'classes' => 'mb-auto',
              ])

              @group('link')
                @hassub('link')
                  <x-link href="{{ get_sub_field('link')['url'] }}"
                          style="{{ get_sub_field('link_type') }}"
                          target="{{ get_sub_field('link')['target'] }}"
                          colour="{{ get_sub_field('colour') }}"
                          class="mx-auto mt-50"
                  >
                    @sub('link', 'title')
                  </x-link>
                @endsub
              @endgroup
            </div>
          </li>
        @endgroup
      @endfields
    </ul>
  </div>

  <div class="max-w-895 flex flex-col items-center mx-auto">
    @include('flexible.partials.links')
  </div>

@endcomponent
