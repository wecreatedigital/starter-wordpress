@props([
  'href' => '',
  'target' => false,
  'button' => false,
  'colour' => 'black',
  'additional' => '',
  'fontSize' => 'text-20',
])

@switch($colour)
    @case('white')
        @php $typeClasses = 'bg-white text-grey-dark'; @endphp
        @break
    @case('black')
        @php $typeClasses = 'bg-black text-white'; @endphp
        @break
    @case('pink')
        @php $typeClasses = 'bg-pink text-white'; @endphp
        @break
    @case('dark-grey')
        @php $typeClasses = 'bg-grey-dark text-white'; @endphp
        @break
    @case('teal')
        @php $typeClasses = 'bg-teal text-white'; @endphp
        @break
    @default
        @php $typeClasses = 'bg-pink text-white'; @endphp
@endswitch

@if ($button == true)
  <button {{ $attributes->except(['target', 'style'])->merge([
    'class' => "button transition duration-300 cursor-pointer no-underline {$typeClasses} {$fontSize} {$additional}",
  ]) }}>
    {{ $slot }}
  </button>
@else
  <a href="{{ $href }}" {{ $attributes->except(['type', 'style'])->merge([
    'class' => "button transition duration-300  cursor-pointer no-underline {$typeClasses} {$fontSize} {$additional}",
  ]) }}
  >{{ $slot }}</a>
@endif
