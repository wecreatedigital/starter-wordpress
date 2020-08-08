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

  <div class="container">
    @while(have_posts()) @php(the_post())
      @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
    @endwhile
  </div>

  {!! get_the_posts_navigation() !!}
@endsection
