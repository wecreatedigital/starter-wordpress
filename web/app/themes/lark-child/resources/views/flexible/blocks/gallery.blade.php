@component('components.container')
  'classes' => 'overflow-hidden relative mx-20',
])

  <div class="max-w-1250 {{ $alignment }}">
    @include('flexible.partials.heading')

    @include('flexible.partials.text')

    @if(isset(get_sub_field('text')['text']) && ! empty(get_sub_field('text')['text']))
      @php $marginTop = 'mt-50'; @endphp
    @elseif(isset(get_sub_field('heading')['heading']) && ! empty(get_sub_field('heading')['heading']))
      @php $marginTop = 'mt-0'; @endphp
    @endif

    <div class="max-w-1250 grid sm:grid-cols-3 gap-10 {{ $marginTop }}">
      @fields('gallery')
        <div class="grid sm:grid-rows-1 gap-10">
          @fields('images')
            <img loading="lazy"
                 src="@sub('image', 'url')"
                 alt="@sub('image', 'alt')"
                 class="object-cover w-screen
                        @hassub('small')
                          min-h-200 max-h-200 md:min-h-250 md:max-h-250
                        @else
                          min-h-390 max-h-390 md:min-h-410 md:max-h-410
                        @endsub"
            />
          @endfields
        </div>
      @endfields
    </div>

    @include('flexible.partials.links')
  </div>

@endcomponent
