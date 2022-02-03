@component('components.container')
  'overridePaddingFieldValue' => ' ',
])

  <div class="max-w-1000 flex flex-col {{ $alignment }}">
    @include('flexible.partials.heading')

    @include('flexible.partials.text')

    @include('flexible.partials.links')
  </div>

@endcomponent
