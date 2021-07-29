@php
  global $h;
@endphp

@if ($size == 'h1')
  @php $h++; @endphp
@endif

@if ($size == 'h1' && $h > 1)
  @php $size = 'h2'; @endphp
@endif

<{{ $size }} class="{{ $classes }}">
  {{ $slot }}
</{{ $size }}>
