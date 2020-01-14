<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  @include('layouts.favicons')
  @if (getenv('ENV') == 'local' || getenv('ENV') == 'dev')
    <meta name="robots" content="noindex, nofollow">
  @endif
  @wphead()
</head>
