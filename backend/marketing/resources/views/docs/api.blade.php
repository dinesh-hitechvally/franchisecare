@extends('docs.layout')

@section('title', 'API documentation')
@section('description', 'Create an API key in Settings and connect another system: the integration API, webhooks, public endpoints and the dashboard API.')

@section('content')
  @include('docs.api._intro')
  @include('docs.api._keys')
  @include('docs.api._integration')

  <section id="webhooks" data-nav="Webhooks" data-group="Events and public endpoints">
    <h2>Webhooks <a class="anchor" href="#webhooks">#</a></h2>
    <p>Webhooks are the other direction: instead of you calling us, we call your address when something happens. Create one under <b>Automation → Webhooks</b>: enter the address, choose events (or all), and copy the secret that is shown once. These are the events:</p>
    <div class="table-wrap"><table>
      <thead><tr><th>Event</th><th>Sent when</th></tr></thead>
      <tbody>
      @foreach ($events as $event => $label)
        <tr><td><code>{{ $event }}</code></td><td>{{ $label }}</td></tr>
      @endforeach
      </tbody>
    </table></div>
    @include('docs.api._webhooks')
  </section>

  @include('docs.api._public')
  @include('docs.api._dashboard')

  <section id="endpoints" data-nav="Endpoint reference" data-group="Dashboard API">
    <h2>Endpoint reference <a class="anchor" href="#endpoints">#</a></h2>
    <p>All <b>{{ $endpointCount }}</b> routes, read from the running app. Use the search box above to filter by path, method or name.</p>
    <div class="legend">
      <span><span class="chip token">token</span> a signed-in user: <code>Authorization: Bearer …</code></span>
      <span><span class="chip apikey">api key</span> an API key from Settings, <code>/api/v1</code> only</span>
      <span><span class="chip public">public</span> no sign-in</span>
      <span><span class="chip">120/min</span> rate limit per minute, where one is set</span>
    </div>
    <p><button class="btn" id="openAll" type="button">Expand all</button> <button class="btn" id="closeAll" type="button">Collapse all</button></p>
    <div class="endpoints">
    @foreach ($endpoints as $g)
      <details class="group" data-group="{{ $g['name'] }}">
        <summary><b>{{ $g['area'] }} · {{ $g['title'] }}</b><small>{{ $g['about'] }}</small><span class="n">{{ count($g['routes']) }}</span></summary>
        <div class="table-wrap"><table>
          <tbody>
          @foreach ($g['routes'] as $r)
            <tr data-q="{{ strtolower($r['method'].' '.$r['uri'].' '.$r['what'].' '.$r['auth']) }}">
              <td>@foreach (explode('|', $r['method']) as $m)<span class="m m-{{ $m }}">{{ $m }}</span> @endforeach</td>
              <td><code>{{ $r['uri'] }}</code></td>
              <td>{{ $r['what'] }}</td>
              <td><span class="chip {{ str_replace(' ', '', $r['auth']) }}">{{ $r['auth'] }}</span>@if ($r['limit']) <span class="chip">{{ $r['limit'] }}</span>@endif</td>
            </tr>
          @endforeach
          </tbody>
        </table></div>
      </details>
    @endforeach
    </div>
    <p id="noEndpoints" class="hidden" style="color: var(--muted)">No endpoint matches your search.</p>
  </section>
@endsection
