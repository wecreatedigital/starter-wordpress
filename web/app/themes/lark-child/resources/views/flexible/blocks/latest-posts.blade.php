@if($latestPosts->have_posts())

  @component('components.container')

    <div class="mx-auto">
      <x-heading size="h2" alignment="center">
        Latest Posts
      </x-heading>

      <ul class="grid lg:grid-cols-2 gap-x-65 gap-y-30">
        @while($latestPosts->have_posts()) @php $latestPosts->the_post(); @endphp
          <li class="">
            <a href="@permalink" class="block">
              <img src="@thumbnail('full', false)"
                   alt="@title"
                   class="max-h-185 min-h-185 md:max-h-360 md:min-h-360 object-cover w-full"
              />
            </a>

            <div class="px-50 pb-50 pt-45 bg-lightGrey rounded-b-md">
              <a href="@permalink" class="block mb-15">
                <x-heading size="h2" alignment="left" :size-options="['margin' => 'mb-0']">
                  @title
                </x-heading>
              </a>

              <div class="mb-30">
                @excerpt
              </div>

              @php $terms = collect(get_the_terms($post->ID, 'category')); @endphp

              @if ( ! $terms->isEmpty())
                <div class="flex flex-row items-center space-x-10">
                  @foreach ($terms as $term)
                    <a href="{{ get_term_link($term->term_id) }}" class="text-16 bg-Grey py-10 px-12 rounded-sm">
                      {{ $term->name }}
                    </a>
                  @endforeach
                </div>
              @endif
            </div>
          </li>
        @endwhile
      </ul>

      @include('flexible.partials.links')
    </div>

  @endcomponent

@endif
