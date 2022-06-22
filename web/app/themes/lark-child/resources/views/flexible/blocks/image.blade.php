@component('components.container', [
    'classes' => 'overflow-hidden relative echo w-full min-h-538 container px-20 bg-overlay',
  ])

  <div class="flex justify-center items-center {{ $alignment }}">
      <div class="text-center text-white py-200">
        @include('flexible.partials.heading', [
          'default' => 'h1',
          'classes' => 'text-center',
        ])

        @include('flexible.partials.heading', [
          'fieldName' => 'sub_heading',
          'default' => 'h4',
        ])

        @include('flexible.partials.text')

        @include('flexible.partials.links')
      </div>
  </div>

@endcomponent
