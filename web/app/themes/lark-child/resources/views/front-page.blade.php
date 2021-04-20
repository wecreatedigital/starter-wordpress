@extends('layouts.app')

@section('content')

  {{dd(headingSize(['size' => 'h1', 'mobile.size' => 'something']))}}
  @include('layouts.flexible')
@endsection
