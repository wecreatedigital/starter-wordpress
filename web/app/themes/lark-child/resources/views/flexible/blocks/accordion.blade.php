@component('components.blocks.container', [
  'classes' => 'fcb-accordion',
  'padding' => $default_padding,
])

  <div class="row no-gutters">
    <div class="fcb-col-center fcb-align-text col-md-8">
      @include('flexible.content', [
        'classes' => 'fcb-b30 fcb-x70'
      ])
    </div>
  </div>

  <div class="container">
    <div class="panel-group accordion" id="accordion-{{ $unique_id }}" role="tablist" aria-multiselectable="true">
      <div class="row no-gutters">
        <div class="col-md-10 offset-md-1">
          @php $i=1; while ( have_rows('accordion_item') ) : the_row() @endphp
            <div class="panel panel-default">
              <div class="panel-heading" role="tab" id="heading-{{ $unique_id }}-{{$i}}">
                <h3 class="panel-title">
                  <button class="fcb-y40" data-toggle="collapse" data-target="#collapse-{{ $unique_id }}-{{$i}}" href="#collapse-{{ $unique_id }}-{{$i}}" aria-expanded="false" aria-controls="collapse-{{ $unique_id }}-{{$i}}">
                    @sub('heading')
                    <svg xmlns="http://www.w3.org/2000/svg" class="plus @php if ($i==1) { echo 'minus'; } @endphp" width="15" height="15" viewBox="0 0 15 15">
                      <rect class="vertical-line" x="7" width="2" height="15"/>
                      <rect class="horizontal-line" y="7" width="15" height="2"/>
                    </svg>
                  </button>
                </h3>
              </div>
              <div id="collapse-{{ $unique_id }}-{{$i}}" data-parent="#accordion-{{ $unique_id }}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-{{ $unique_id }}-{{$i}}">
                <div class="panel-body fcb-a40">
                  @sub('text')
                </div>
              </div>
            </div>
          @php $i++; endwhile; @endphp
        </div>
      </div>
    </div>
  </div>

  @if( get_sub_field('call_to_action') )
    <div class="row no-gutters">
      <div class="fcb-col-center fcb-align-text col-md-8">
        <p class="lead fcb-t50">
          @hassub('call_to_action')
            <a target="@sub('call_to_action', 'target')" class="btn-link btn-lg" href="@sub('call_to_action', 'url')">
              @sub('call_to_action', 'title')
            </a>
          @endsub
        </p>
      </div>
    </div>
  @endif

@endcomponent
