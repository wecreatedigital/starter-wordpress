@props([
  'href' => '',
  'target' => false,
  'button' => false,
  'colour' => 'black',
  'additional' => '',
  'fontSize' => 'text-20',
  'paddingY' => 'py-15',
  'bordered' => false,
])

@php $bordered = filter_var($bordered, FILTER_VALIDATE_BOOLEAN); @endphp

@php
  $options = [
    'white' => [
      'textColour' => 'black',
      'bgColour' => 'white',
    ],
    'black' => [
      'textColour' => 'white',
      'bgColour' => 'black',
    ],
    'teal' => [
      'textColour' => 'white',
      'bgColour' => 'teal',
    ],
  ];
@endphp

@php $typeClasses = "rounded-full px-30 {$paddingY} cursor-pointer hover:no-underline focus:no-underline active:no-underline"; @endphp

@if($bordered)
  @php $optionColourClasses = 'text-' . $options[$colour]['bgColour'] . ' ' . 'border-2 border-' . $options[$colour]['bgColour']; @endphp
@else
  @php $optionColourClasses = 'text-' . $options[$colour]['textColour'] . ' ' . 'bg-' . $options[$colour]['bgColour']; @endphp
@endif

@if ($button == true)
  <button {{ $attributes->except(['target'])->merge([
    'class' => "{$typeClasses} {$fontSize} {$additional} {$optionColourClasses}",
  ]) }}>
    {{ $slot }}
  </button>
@else
  <a href="{{ $href }}" {{ $attributes->except(['type'])->merge([
    'class' => "{$typeClasses} {$fontSize} {$additional} {$optionColourClasses}",
  ]) }}
  >{{ $slot }}</a>
@endif

