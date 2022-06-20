@php global $h; @endphp

@if (is_null($h))
  @php $h++; @endphp
@endif 

@if ($size == 'h1' && $h > 1)
  @php $size = 'h2'; @endphp
@elseif($size == 'h1')
  @php $h++; @endphp
@elseif($size == 'h2' && $h === 1)
  @php $size = 'h1'; @endphp
  @php $h++; @endphp
@endif

<{{ $size }} class="{{ $classes }}">
  {{ $slot }}
</{{ $size }}>
