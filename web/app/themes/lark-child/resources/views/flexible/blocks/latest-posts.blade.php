@if($latestPosts->have_posts())
  @component('components.blocks.container')

    <div class="mx-auto">
      @include('flexible.partials.heading')

      @include('flexible.partials.text')

      <ul class="space-y-30">
        @php
        $counter = 1;
        @endphp

        @while($latestPosts->have_posts()) @php $latestPosts->the_post(); @endphp
          <li class="flex flex-col md:flex-row">
            <img src="@thumbnail('full', false)"
                 alt="@title"
                 class="md:max-w-412 md:min-w-412 object-cover max-h-300 md:max-h-unset md:min-h-340"
            />

            <div class="flex flex-col items-start justify-center p-30 md:px-60 md:py-65 bg-white">
              <a href="{{ get_permalink() }}" class="block mb-25">
                <x-heading size="h3" alignment="start" :size-options="['margin' => 'mb-0']">
                  @title
                </x-heading>
              </a>

              <div class="mb-25">
                @excerpt
              </div>

              <x-link href="{{ get_permalink() }}" colour="dark-grey">
                Read story
              </x-link>
            </div>
          </li>

          @php $counter++; @endphp
        @endwhile
      </ul>

      @include('flexible.partials.links', [
        'spacing' => 'mt-35'
      ])
    </div>

  @endcomponent
@endif
