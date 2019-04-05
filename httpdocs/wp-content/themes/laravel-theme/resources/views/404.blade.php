@extends('layouts.app')

@section('content')
    <div class="row d-flex justify-content-center">

  <h1 class="display-1 ">404</h1>
  </div>
  @if (!have_posts())
      <div class="row">
          <div class="col-md-8">
                <div class="h3 alert alert-warning">
                  {{ __('Sorry, an error has occured, Requested page not found!', 'sage') }}
                </div>
            </div>
            <div class="col-md-4 mt-3">
                {!! get_search_form(false) !!}
            </div>
    </div>
  @endif
@endsection
