@component('components.blocks.container')

  <div class="max-w-800 flex flex-col {{ $alignment }}">
    @include('flexible.partials.heading')

    @include('flexible.partials.text')

    @include('flexible.partials.links')
  </div>

@endcomponent
