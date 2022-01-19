<nav class="relative z-999 bg-white font-nunito text-black"
     x-data="{ isOpen: false }"
>
  <div class="bg-grey-dark py-15">
    <div class="container">
      <div class="flex flex-row items-center justify-between md:justify-center">
        <a href="{{ $homeUrl }}"
            title="{!! $siteName !!}"
            class="block"
          >
            {{-- LOGO --}}
          </a>

          <div class="flex items-center md:hidden">
            <button @click="isOpen = !isOpen"
                    class="inline-flex items-center justify-center rounded-md focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                    aria-expanded="false"
            >
              <span class="sr-only">
                Open main menu
              </span>
              <div id="hamburger"
                  :class="isOpen ? 'open' : ''"
                  class="block relative text-white"
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
  </div>

  <div class="py-20 bg-white md:relative md:block md:mx-auto" :class="isOpen ? 'block' : 'hidden'">
    <div class="container">
      <div class="relative md:flex md:items-center md:justify-between">
        <ul class="-mx-20 md:mx-0 px-20 flex md:flex flex-col items-start md:p-0 md:flex-row md:w-full md:items-center md:justify-center md:ml-auto md:space-x-30 xl:space-x-70">
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
                  <li class="w-full md:w-auto">
                    <a href="{{ $menuItem->url }}"
                      target="{{ $target }}"
                      class="text-pink flex flex-row items-center py-20 md:py-0"
                    >
                      <span class="mr-5">{!! $menuItem->title !!}</span>

                      <svg class="fill-current" width="12px" height="13px" viewBox="0 0 12 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                          <g transform="matrix(0.120098,0.120098,-0.120098,0.120098,447.091,-1278.2)">
                              <path d="M3533,7161L3482,7161L3533,7212L3533,7161Z" />
                          </g>
                          <g transform="matrix(0.120098,0.120098,-0.120098,0.120098,441.841,-1278.2)">
                              <path d="M3533,7161L3482,7161L3533,7212L3533,7161Z" />
                          </g>
                      </svg>
                    </a>
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
    </div>
  </div>
</nav>
