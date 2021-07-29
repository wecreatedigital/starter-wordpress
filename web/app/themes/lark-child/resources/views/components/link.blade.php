@props([
  'href' => '',
  'type' => 'primary',
  'target' => false,
  'button' => false,
])

@if ($type == 'primary')
  @php $typeClasses = ''; @endphp
@else
  @php $typeClasses = ''; @endphp
@endif

@if ($button == true)
  <button type="button"
          {{ $attributes->except(['class', 'href', 'type', 'target']) }}
          {{ $attributes->merge(['class' => $typeClasses]) }}
  >
  {{ $slot }}
  </button>
@else
  <a href="{{ $href }}"
     {{ $attributes->except(['class', 'href', 'type', 'target']) }}
     {{ $attributes->merge(['class' => $typeClasses]) }}
     @if($target) target="{{ $target }}" @endif
  >
    {{ $slot }}
  </a>
@endif
