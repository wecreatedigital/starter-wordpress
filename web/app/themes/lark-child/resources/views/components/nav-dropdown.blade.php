<div @click.away="open = false"
     class="dropdown relative @isset($rootClasses) {{ $rootClasses }} @endisset" x-data="{ open: false }"
     x-init="() => {$el.querySelector('.dropdown-container').classList.remove('hidden')}"
>
  <a href="javascript:;"
     @click="open = !open"
     {{ $attributes->merge(['class' => 'text-20 flex flex-row items-center justify-between hover:underline focus:underline']) }}
  >
    <span class="mr-10">
      {{ $title }}
    </span>

    <svg :class="{ 'transform rotate-180' : open}" class="fill-current text-teal" width="13px" height="9.2px" viewBox="0 0 13 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
        <g transform="matrix(-0.254902,-3.12165e-17,3.12165e-17,-0.254902,299.765,157.529)">
            <path d="M1150.5,582L1176,618L1125,618L1150.5,582Z" />
        </g>
    </svg>
  </a>
  <div x-show="open"
       x-on:click.away="open = false"
       x-transition:enter="transition ease-out duration-750"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-750"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="md:transform md:-translate-x-50% md:left-50% md:min-w-222 md:max-w-222 hidden dropdown-container md:absolute right-0 -mb-5 md:m-0 @isset($containerClasses) {{ $containerClasses }} @endisset origin-top-right z-999"
  >
    <svg class="hidden md:block transform rotate-180 -translate-x-50% translate-y-15 left-50% relative fill-current text-warmGrey" width="13px" height="9.2px" viewBox="0 0 13 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
        <g transform="matrix(-0.254902,-3.12165e-17,3.12165e-17,-0.254902,299.765,157.529)">
            <path d="M1150.5,582L1176,618L1125,618L1150.5,582Z" />
        </g>
    </svg>

    <div class="divide-y-1 divide-grey-200 ml-5 dropdown-content bg-warmGrey md:shadow-lg md:m-0 md:px-20 md:py-10 md:relative md:transform md:translate-y-15">
      {!! $slot !!}
    </div>
  </div>
</div>
