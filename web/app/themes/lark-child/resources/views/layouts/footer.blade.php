<footer class="font-nunito relative z-2">
  <div class="bg-grey-dark text-white py-50">
    @php $footerNavigation = menu_for('footer_navigation'); @endphp
    @if ( ! $footerNavigation->isEmpty())
      <ul class="flex flex-col space-y-20 md:space-y-0 md:flex-row justify-between mx-auto items-center max-w-1140 px-20">
        @foreach ($footerNavigation as $menuItem)
          <li>
            <a href="{{ $menuItem->url }}" class="text-16 hover:underline focus:underline">
              {{ $menuItem->post_title ?: $menuItem->title }}
            </a>
          </li>
        @endforeach
      </ul>
    @endif

    <div class="container mt-50">
      <div class="flex flex-col space-y-30 items-center md:flex-row md:space-y-0 justify-between">
        <a href="{{ $homeUrl }}"
            title="{!! $siteName !!}"
            class="block mb-0"
        >
          {{-- LOGO --}}
        </a>

        <a href="" class="flex flex-row items-center">
          <x-heading size="h2" alignment-classes=" " :size-options="['margin' => 'mb-0']" additional-classes="mr-40">
            Donate today
          </x-heading>

          <svg class="text-teal fill-current" width="21.2px" height="21.2px" viewBox="0 0 12 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
              <g transform="matrix(0.120098,0.120098,-0.120098,0.120098,447.091,-1278.2)">
                  <path d="M3533,7161L3482,7161L3533,7212L3533,7161Z" />
              </g>
              <g transform="matrix(0.120098,0.120098,-0.120098,0.120098,441.841,-1278.2)">
                  <path d="M3533,7161L3482,7161L3533,7212L3533,7161Z" />
              </g>
          </svg>
        </a>
      </div>
    </div>
  </div>

  @hasoption('copyright_message')
    <div class="py-30 bg-white">
      <div class="container">
        <div class="flex flex-col text-center md:text-left space-y-30 md:space-y-0 md:flex-row items-center justify-between">
          <p>
            @option('copyright_message')
          </p>

          <p>
            Copyright © {{ date('Y') }}
          </p>
        </div>
      </div>
    </div>
  @endoption
</footer>
