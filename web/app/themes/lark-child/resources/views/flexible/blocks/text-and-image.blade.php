@component('components.blocks.container', [
  'classes' => 'overflow-hidden relative',
  'overridePaddingFieldValue' => ' ',
])

  @php $imageUrl = wp_get_attachment_image_src(get_sub_field('image')['ID'], 'full')[0]; @endphp
  @php $imageTitle = get_the_title(get_sub_field('image')['ID']); @endphp


  <x-slot name="beforeSlot">
    <div class="md:absolute top-0 bottom-0 md:w-50% {{ get_sub_field('image_position') == 'left' ? 'left-0' : 'right-0' }}">
      <img src="{{ $imageUrl }}"
           alt="{{ $imageTitle }}"
           class="w-full h-full object-cover min-h-310 max-h-310 md:max-h-unset"
      />
    </div>
  </x-slot>

  <div class="md:grid md:grid-cols-2">
    <div class="relative {{ $defaultPadding }} {{ get_sub_field('image_position') == 'left' ? 'col-start-2' : 'col-start-1' }}">
      <div class="xl:max-w-580 {{ get_sub_field('image_position') == 'left' ? 'md:col-start-2 md:ml-50 md:mr-auto' : 'col-start-1 md:mr-50 md:ml-auto' }}">
        @include('flexible.partials.heading')

        @include('flexible.partials.text')

        @include('flexible.partials.links')
      </div>

      <svg class="{{ get_sub_field('image_position') == 'left' ? 'hidden' : 'block' }} left-auto right-1 transform translate-x-100% w-auto top-0 bottom-0 absolute fill-current text-{{ get_sub_field('background_colour') }}" preserveAspectRatio="none" width="100%" height="100%" viewBox="0 0 150 300" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
          <g transform="matrix(-1,0,0,1,150,0)">
              <path d="M0,-0L150,-0L150,300L0,300C82.787,300 150,232.787 150,150C150,67.378 83.056,0.269 0.485,0.001L0,-0Z"/>
          </g>
      </svg>

      <svg class="{{ get_sub_field('image_position') == 'left' ? 'block' : 'hidden' }} left-1 right-auto transform -translate-x-100% w-auto top-0 bottom-0 absolute fill-current text-{{ get_sub_field('background_colour') }}" preserveAspectRatio="none" width="100%" height="100%" viewBox="0 0 150 300" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
          <path d="M0,-0L150,-0L150,300L0,300C82.787,300 150,232.787 150,150C150,67.378 83.056,0.269 0.485,0.001L0,-0Z" />
      </svg>
    </div>
  </div>

@endcomponent
