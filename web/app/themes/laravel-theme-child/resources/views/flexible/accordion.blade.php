@include('flexible._start', [
  'classes' => 'fcb-accordion pb-0',
  'padding' => $default_padding,
])

@php
  $tab = get_sub_field('heading_background_colour');
  $tabbody = get_sub_field('text_background_colour');
@endphp

<div class="row">
  <div class="offset-lg-2 col-lg-8 text-center">
    @include('flexible.content')
  </div>
</div>
<div class="container">
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        @php $i=1; while ( have_rows('accordion_item') ) : the_row() @endphp
            <div class="panel panel-default {{$tab}}">
                <div class="panel-heading col-md-12 py-3 mb-1" role="tab" id="heading-{{$i}}">
                  <h3 class="panel-title">
                    <a data-toggle="collapse" data-target="#collapse-{{$i}}"  href="#collapse-{{$i}}" aria-expanded="false" aria-controls="collapse-{{$i}}">
                    @sub('heading')
                  </a>
                    <a class="accordion-button" data-toggle="collapse" data-target="#collapse-{{$i}}"  href="#collapse-{{$i}}" aria-expanded="false" aria-controls="collapse-{{$i}}">
                      <i class="fas fa-chevron-right fa-xs fa-rotate-90"></i>
                    </a>
                  </h3>
                </div>
                <div id="collapse-{{$i}}" data-parent="#accordion" class="panel-collapse collapse @php if ($i==1) { echo 'in'; } @endphp" role="tabpanel" aria-labelledby="heading-{{$i}}">
                  <div class="panel-body py-3 {{$tabbody}}">
                    @sub('text')
                  </div>
                </div>
            </div>
        @php $i++; endwhile; @endphp
    </div>
  </div>
</div>


@include('flexible._end')
