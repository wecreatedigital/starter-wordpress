@extends('layouts.app')

@section('content')
  <article>
    @while(have_posts()) @php the_post() @endphp
      @include('partials.page-header')
      @include('partials.content-page')
    @endwhile
    @include('flexible._main')
  </article>
@endsection
