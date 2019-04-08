{{--
  Template Name: Fontpage
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')
    {{App\siteName()}}
    {{App\title()}}
    @include('partials.content-page')
  @endwhile
@endsection
