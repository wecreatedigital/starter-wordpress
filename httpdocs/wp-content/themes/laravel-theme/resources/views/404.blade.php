@extends('layouts.app')

@section('content')
    <div class="row d-flex justify-content-center">

  <h1 class="display-1 jumbotron w-100 text-center">404</h1>
  </div>
  @if (!have_posts())
      <div class="row">
          <div class="col-md">
                <div class="h3 alert alert-warning">
                  {{ __('Sorry, an error has occured, Requested page not found!', 'sage') }}
                </div>
            </div>

    </div>
    <div class="row">
        <div class="col-md-4 offset-md-6 mt-3">
            {!! get_search_form(false) !!}
        </div>
    </div>
  @endif
@endsection
