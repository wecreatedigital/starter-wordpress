@component('components.blocks.container')

  <div>
    @include('flexible.partials.heading', [
      'default' => 'h1',
    ])

    @include('flexible.partials.text')

    @include('flexible.partials.links')
  </div>

@endcomponent
