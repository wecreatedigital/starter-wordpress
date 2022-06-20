@component('components.container', [
  'overridePaddingFieldValue' => 'py-50',
])

  <div class="max-w-1000 flex flex-col {{ $alignment }}">
    @include('flexible.partials.heading', [
      'default' => 'h1',
    ])
    @include('flexible.partials.text')
    @include('flexible.partials.links')
  </div>

@endcomponent
