@extends('layouts.app')

@section('content')
    <div class="jumbotron">
        <h1 class="display-4">404</h1>

  @if (!have_posts())

      <p class="lead">This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
      <hr class="my-4">
      <p>It uses utility classes for typography and spacing to space content out within the larger container.</p>


      {!! get_search_form(false) !!}

      </div>

  @endif
  </div>
@endsection
