<!doctype html>
<html {!! get_language_attributes() !!}>
  @include('partials.head')
  <body @php body_class() @endphp>
    @php do_action('get_header') @endphp
    @include('partials.header')
    <div role="document">
      <main class="main">
        <div class="container">
          @include('layouts.breadcrumbs')
          @yield('content')
        </div>
        @include('flexible._main')
      </main>
      @if (App\display_sidebar())
        <aside class="sidebar">
          @include('partials.sidebar')
        </aside>
      @endif
    </div>
    @php do_action('get_footer') @endphp
    @include('partials.footer')
    @include('plugins.cf7')
    @wpfoot()
  </body>
</html>
