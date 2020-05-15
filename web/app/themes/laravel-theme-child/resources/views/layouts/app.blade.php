<!doctype html>
@if (getenv('WP_ENV') == 'local')
  <html class="development-mode" {!! get_language_attributes() !!}>
@else
  <html {!! get_language_attributes() !!}>
@endif
  @include('layouts.head')
  <body @php body_class(getenv('WP_ENV')) @endphp>
    @php do_action('get_header') @endphp
    @include('layouts.header')
    <div class="document" role="document">
      <main class="main">
        @include('layouts.breadcrumbs')
        @yield('content')
      </main>
      @if (App\display_sidebar())
        <aside class="sidebar">
          @include('partials.sidebar')
        </aside>
      @endif
    </div>
    @php do_action('get_footer') @endphp
    @include('layouts.footer')
    @include('plugins.cf7')
    @wpfoot()
  </body>
</html>
