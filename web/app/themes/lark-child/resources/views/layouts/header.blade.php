<nav class="relative z-999 bg-white font-libre-baskerville Grande text-black"
     x-data="{ isOpen: false }"
>
  <div class="container md:max-w-1470 md:mx-auto">
    <div class="relative md:flex md:items-center md:justify-between py-20 md:py-35">
      <div class="md:flex items-center justify-start md:items-stretch md:justify-start md:px-0">
        <div class="relative md:position-unset flex-shrink-0 flex flex-row items-center justify-between">
          <a href="{{ $homeUrl }}"
             title="{!! $siteName !!}"
             class="block"
          >
            WeCreateDigital
          </a>

          <div class="flex items-center md:hidden">
            {{-- TODO --}}
            <a href="#">
              <svg class="text-teal fill-current mr-30" width="31.5px" height="36px" viewBox="0 0 32 36" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                  <g transform="matrix(36,0,0,36,0,31.4999)">
                      <path d="M0.612,-0.312C0.556,-0.312 0.529,-0.281 0.437,-0.281C0.346,-0.281 0.319,-0.312 0.263,-0.312C0.118,-0.312 0,-0.195 0,-0.05L0,0.031C0,0.083 0.042,0.125 0.094,0.125L0.781,0.125C0.833,0.125 0.875,0.083 0.875,0.031L0.875,-0.05C0.875,-0.195 0.757,-0.312 0.612,-0.312ZM0.812,0.031C0.812,0.048 0.798,0.062 0.781,0.062L0.094,0.062C0.077,0.062 0.062,0.048 0.062,0.031L0.062,-0.05C0.062,-0.16 0.152,-0.25 0.263,-0.25C0.301,-0.25 0.339,-0.219 0.437,-0.219C0.536,-0.219 0.574,-0.25 0.612,-0.25C0.723,-0.25 0.812,-0.16 0.812,-0.05L0.812,0.031ZM0.437,-0.375C0.576,-0.375 0.687,-0.487 0.687,-0.625C0.687,-0.763 0.576,-0.875 0.437,-0.875C0.299,-0.875 0.187,-0.763 0.187,-0.625C0.187,-0.487 0.299,-0.375 0.437,-0.375ZM0.437,-0.812C0.541,-0.812 0.625,-0.728 0.625,-0.625C0.625,-0.522 0.541,-0.437 0.437,-0.437C0.334,-0.437 0.25,-0.522 0.25,-0.625C0.25,-0.728 0.334,-0.812 0.437,-0.812Z" style="fill-rule:nonzero;"/>
                  </g>
              </svg>
            </a>

            <button @click="isOpen = !isOpen"
                    class="inline-flex items-center justify-center rounded-md focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                    aria-expanded="false"
            >
              <span class="sr-only">
                Open main menu
              </span>
              <div id="hamburger"
                   :class="isOpen ? 'open' : ''"
                   class="block relative text-teal"
              >
                <span class="bg-current"></span>
                <span class="bg-current"></span>
                <span class="bg-current"></span>
                <span class="bg-current"></span>
              </div>
            </button>
          </div>
        </div>
      </div>

      <div class="md:block mt-30 md:mt-0 md:mx-20" :class="isOpen ? 'block' : 'hidden'">
        <ul class="px-30 md:px-0 divide-white divide-y-1 flex flex-col bg-warmGrey md:bg-transparent items-center justify-between md:flex-row md:space-x-30 xl:space-x-70">
          @php $primaryNavigation = menu_for('primary_navigation'); @endphp

          @if ( ! $primaryNavigation->isEmpty())
            @foreach ($primaryNavigation as $menuItem)
              @if ( ! $menuItem->childMenuItems->isEmpty())
                <li class="w-full md:w-auto">
                  <x-nav-dropdown title="{{ $menuItem->title }}"
                                  class="py-20 md:py-0"
                                  root-classes="w-full"
                  >
                    @foreach ($menuItem->childMenuItems as $childMenuItem)
                      <x-nav-dropdown-link link="{{ $childMenuItem->url }}"
                                           title="{!! $childMenuItem->title !!}"
                      />
                    @endforeach
                  </x-nav-dropdown>
                </li>
              @else
                @if (get_field('is_button', $menuItem->ID))
                <li class="w-full md:w-auto hidden md:block">
                  <x-link href="{{ $menuItem->url }}"
                          style="primary"
                          target="{{ $target }}"
                          colour="black"
                  >
                    {!! $menuItem->title !!}
                  </x-link>
                </li>
                @else
                  <li class="w-full md:w-auto">
                    <x-nav-link link="{{ $menuItem->url }}"
                                title="{!! $menuItem->title !!}"
                                class="block py-20 md:py-0"
                    />
                  </li>
                @endif
              @endif
            @endforeach
          @endif
        </ul>
      </div>

      <div class="hidden w-180 lg:flex xl:w-265 flex-row items-center justify-end">
        {{-- TODO --}}
        <x-link href="#" colour="teal" padding-y="py-18">
          Sign up today
        </x-link>
      </div>
    </div>
  </div>
</nav>
