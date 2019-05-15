{{--
  Template Name: contact
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')
     @include('partials.content-page')

     <p class="test">Here is some text</p>
     <!-- Facebook icon -->
     <i class="fab fa-facebook"></i>
     <!-- Twitter icon -->
     <i class="fab fa-twitter"></i>
     {{--  Pro Icon --}}
     <i class="far fa-acorn"></i>

  @endwhile
  @include('partials.google-maps')
  <script>
    document.addEventListener( 'wpcf7mailsent', function( event ) {
        location = '<?php echo the_field('contact_form_redirect_url'); ?>' ;
    }, false );
    </script>
@endsection
