@extends('layouts.app')

@section('content')
  @include('layouts.flexible', [
    'post_id' => get_option('page_for_posts')
  ])

  @if (! have_posts())
    <x-alert type="warning">
      {!! __('Sorry, no results were found.', 'sage') !!}
    </x-alert>

    {!! get_search_form(false) !!}
  @endif

  <div class="container home-container fcb-y100">
    <div class="row">
      @php
        $counter = 1;
      @endphp

      @while(have_posts()) @php(the_post())
        @include('partials.content-' . get_post_type(), ['counter' => $counter])
        @php( $counter++ )
      @endwhile

    </div>
    <div class="row">
      <div class="col text-center">
        {!! get_the_posts_navigation() !!}
      </div>
    </div>
  </div>
@endsection
