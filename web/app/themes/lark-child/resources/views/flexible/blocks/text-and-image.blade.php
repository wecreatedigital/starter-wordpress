@component('components.container')
  'classes' => 'overflow-hidden relative mx-20',
])

  @php $imageUrl = wp_get_attachment_image_src(get_sub_field('image')['ID'], 'full')[0]; @endphp
  @php $imageTitle = get_the_title(get_sub_field('image')['ID']); @endphp

  <div class="max-w-1270 mx-auto items-start relative">

      <img src="{{ $imageUrl }}"
          alt="{{ $imageTitle }}"
          class="lg:max-h-900 lg:min-h-900 max-w-580 block lg:max-w-400 lg:min-w-400 xl:max-h-900 xl:min-h-900 xl:max-w-615 xl:min-w-615 object-cover mb-20 md:mb-75 lg:mb-0 {{ get_sub_field('image_position') === 'right' ? 'lg:order-2 lg:ml-75 float-none md:float-right lg:float-right' : 'lg:order-1 lg:mr-75 float-none md:float-left lg:float-left' }}"
      />

    <div class="rounded-0 md:rounded-lg lg:rounded-lg max-w-805 md:max-w-805 lg:max-w-805 static mt-20 md:absolute lg:absolute inset-center p-50 bg-{{ get_sub_field('background_text') }} order-1 {{ get_sub_field('image_position') === 'right' ? 'lg:order-1 lg:ml-auto left-0 md:left-0 lg:left-0' : 'lg:order-2 lg:mr-auto right-0 md:right-0 lg:right-0' }}">
      <div class="xl:max-w-580">
        @include('flexible.partials.heading')

        @include('flexible.partials.text')

        @include('flexible.partials.links')
      </div>
    </div>
  </div>

@endcomponent
