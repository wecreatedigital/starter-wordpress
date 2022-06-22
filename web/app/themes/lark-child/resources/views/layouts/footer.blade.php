<footer class="font-bitter relative text-white z-2 max-w-1470 mx-auto">
  <div class="bg-teal px-30 py-50 mx-20">
    <div class="flex flex-col space-y-20 lg:flex-row justify-between">
      <div class="flex flex-col order-2 lg:order-1 items-center lg:items-start mt-50 lg:mt-0">
        <a href="{{ $homeUrl }}"
            title="{!! $siteName !!}"
            class="block mb-30"
        >
          WeCreateDigital
        </a>

        <ul class="flex flex-col space-y-15 mt-auto text-center lg:text-left">
          @hasoption('copyright_message')
            <li>
              <span class="text-18">@option('copyright_message')</span>
            </li>
          @endoption
        </ul>
      </div>

      <div class="w-full md:w-auto lg:w-auto order-1 lg:order-2">
        <ul class="divide-y-1 divide-white">
          <li x-data="{ open: true }" class="pb-15">
            @php $footerNavigation = menu_for('browse_menu'); @endphp
            @if ( ! $footerNavigation->isEmpty())
              <button @click="open = !open" class="w-full focus:outline-0 outline-0 flex flex-row items-center justify-between">
                <x-heading size="h3" :size-options="['margin' => 'mb-0']">
                  Browse
                </x-heading>

                <svg class="md:hidden fill-current" width="17px" height="17px" viewBox="0 0 17 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                    <g transform="matrix(0.317549,0,0,0.522428,-1331.01,-2701.46)">
                        <g transform="matrix(1.245,0,0,0.0864861,-1020.07,4594.6)">
                            <rect x="4186" y="6835" width="43" height="35" />
                        </g>
                        <g :class="open ? 'hidden' : 'block'" transform="matrix(-2.28703e-16,0.756753,-0.142286,-1.58872e-17,5193.29,2003.21)">
                            <rect x="4186" y="6835" width="43" height="35" />
                        </g>
                    </g>
                </svg>
              </button>

              <div x-show.transition.in.duration.800ms="open" class="md:block-permanent mt-30">
                <ul class="flex flex-col md:flex-row md:flex-wrap space-y-15 md:-m-15">
                  @foreach ($footerNavigation as $menuItem)
                    <li class="md:m-15">
                      <a href="{{ $menuItem->url }}" class="text-20 hover:underline focus:underline">
                        {{ $menuItem->post_title ?: $menuItem->title }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif
          </li>

          <li x-data="{ open: false }" class="pt-15">
            @php $footerNavigation = menu_for('footer_navigation'); @endphp
            @if ( ! $footerNavigation->isEmpty())
              <button @click="open = !open" class="w-full focus:outline-0 outline-0 flex flex-row items-center justify-between">
                <x-heading size="h3">
                  Useful links
                </x-heading>

                <svg class="md:hidden fill-current" width="17px" height="17px" viewBox="0 0 17 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                    <g transform="matrix(0.317549,0,0,0.522428,-1331.01,-2701.46)">
                        <g transform="matrix(1.245,0,0,0.0864861,-1020.07,4594.6)">
                            <rect x="4186" y="6835" width="43" height="35" />
                        </g>
                        <g :class="open ? 'hidden' : 'block'" transform="matrix(-2.28703e-16,0.756753,-0.142286,-1.58872e-17,5193.29,2003.21)">
                            <rect x="4186" y="6835" width="43" height="35" />
                        </g>
                    </g>
                </svg>
              </button>

              <div x-show.transition.in.duration.800ms="open" class="md:block-permanent mt-30">
                <ul class="flex flex-col md:flex-row md:flex-wrap space-y-15 md:-m-15">
                  @foreach ($footerNavigation as $menuItem)
                    <li class="md:m-15">
                      <a href="{{ $menuItem->url }}" class="text-20 hover:underline focus:underline">
                        {{ $menuItem->post_title ?: $menuItem->title }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif
          </li>
        </ul>
      </div>
    </div>
  </div>
</footer>
