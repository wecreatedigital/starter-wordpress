{{--
  Template Name: contact
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')
     @include('partials.content-page')

     <p class="test">Here is some text</p>

     

  @endwhile
  <script>
    document.addEventListener( 'wpcf7mailsent', function( event ) {
        location = '<?php echo the_field('contact_form_redirect_url'); ?>' ;
    }, false );
    </script>
@endsection
