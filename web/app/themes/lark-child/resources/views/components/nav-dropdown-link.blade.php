<a href="{{ $link }}" {{ $attributes->merge(['class' => 'block text-16 hover:underline focus:underline py-18']) }}>
  {!! $slot !!}

  <span class="block">
    {{ $title }}
  </span>
</a>
