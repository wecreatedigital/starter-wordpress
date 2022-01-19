<div @click.away="open = false"
     class="dropdown relative @isset($rootClasses) {{ $rootClasses }} @endisset" x-data="{ open: false }"
     x-init="() => {$el.querySelector('.dropdown-container').classList.remove('hidden')}"
>
  <a href="javascript:;"
     @click="open = !open"
     {{ $attributes->merge(['class' => 'text-16 flex flex-row items-center justify-between hover:underline focus:underline']) }}
  >
    <span class="mr-5">
      {{ $title }}
    </span>

    <svg :class="{ 'transform rotate-180' : open}" class="fill-current text-pink" width="9px" height="6px" viewBox="0 0 9 6" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
        <g transform="matrix(-0.0833333,0.0833333,-0.0833333,-0.0833333,895.417,306.583)">
            <path d="M3538.25,7155.75L3482,7161L3533,7212L3538.25,7155.75Z" />
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
    <svg class="hidden md:block transform -translate-x-50% translate-y-18 left-50% relative fill-current text-grey-light" width="13px" height="11.6px" viewBox="0 0 13 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
        <g transform="matrix(-1.11989,-1.52421e-16,-1.06597e-16,0.995159,3143.44,-1124.53)">
            <path d="M2801.13,1130L2806.93,1141.61L2795.32,1141.61L2801.13,1130Z" />
        </g>
    </svg>

    <div class="ml-5 dropdown-content md:bg-grey-light md:shadow-lg md:m-0 md:px-20 md:py-10 md:relative md:transform md:translate-y-15">
      {!! $slot !!}
    </div>
  </div>
</div>
