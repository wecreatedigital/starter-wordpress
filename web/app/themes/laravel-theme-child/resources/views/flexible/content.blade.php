@php
  if( $h == 1 ) {
    $heading = 'h1';
  } elseif( $h >= 2 && $h <= 4 ) {
    $heading = 'h2';
  } else {
    $heading = 'h3';
  }
@endphp

<div class="content @if( isset($class) ) {{ $class }} @else fcb-b20 @endif">
  @hassub('heading')
    <{{ $heading }} class="fcb-b10">
      @sub('heading')
    </{{ $heading }}>
  @endsub

  @hassub('sub_heading')
    <{{ $heading }} class="fcb-b10">
      @sub('sub_heading')
    </{{ $heading }}>
  @endsub

  @hassub('text')
    @sub('text')
  @endsub
</div>

@php
  $h++;
@endphp
