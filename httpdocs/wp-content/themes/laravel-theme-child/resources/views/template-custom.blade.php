{{--
  Template Name: Custom Template
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')
    @include('partials.content-page')
  @endwhile



  <script>
    document.addEventListener( 'wpcf7mailsent', function( event ) {
        location = '<?php echo the_field('contact_form_redirect_url'); ?>' ;
    }, false );
</script>


@endsection
