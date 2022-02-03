@component('components.container')

  <div class="max-w-1000 flex flex-col {{ $alignment }}">
    @include('flexible.partials.heading')

    @include('flexible.partials.text', [
      'classes' => 'text-30 -word-spacing-0.5',
    ])

    @include('flexible.partials.links')
  </div>

@endcomponent
