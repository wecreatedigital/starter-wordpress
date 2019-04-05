{{--
  Template Name: Fontpage
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')
    <h2 class="btn btn">TEST</h2>
    <p class="h1">Bootstrap test</p>

    ter
    @include('partials.content-page')
  @endwhile
@endsection
