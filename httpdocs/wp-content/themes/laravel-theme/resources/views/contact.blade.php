{{--
  Template Name: contact
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')

    @if (the_field('map_image'))
    <img src="<?php the_field('map_image'); ?>" alt="Map Image" />
    @endif
     @include('partials.content-page')
  @endwhile
@endsection
