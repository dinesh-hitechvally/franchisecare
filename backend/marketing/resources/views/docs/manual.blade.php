@extends('docs.layout')

@section('title', 'Project manual')
@section('description', 'How to use FranchiseCare Marketing: people, campaigns, forms, templates, automation and reports.')

@section('content')
  @include('docs.manual._overview')
  @include('docs.manual._start')
  @include('docs.manual._guide', ['tags' => $tags, 'fieldTypes' => $fieldTypes, 'formTypes' => $formTypes])

  <section id="triggers" data-nav="Triggers" data-group="Automation">
    <h2>Triggers <a class="anchor" href="#triggers">#</a></h2>
    <p>A workflow starts when its trigger happens. <b>Instant</b> triggers fire the moment something happens; <b>Time</b> triggers are checked once a day. Some triggers need a choice (a list, a tag, a form, a date field, a number of days); the last column says which.</p>
    @foreach ($triggers as $group => $items)
      <h4>{{ $group }}</h4>
      <div class="table-wrap"><table>
        <thead><tr><th>Trigger</th><th>What it does</th><th>You choose</th></tr></thead>
        <tbody>
        @foreach ($items as $key => $t)
          <tr><td><b>{{ $t['label'] }}</b></td><td>{{ $t['description'] }}</td><td>{{ $t['config'] ? ucfirst($t['config']) : '—' }}</td></tr>
        @endforeach
        </tbody>
      </table></div>
    @endforeach
  </section>

  @include('docs.manual._reports')
  @include('docs.manual._settings')
  @include('docs.manual._problems')
@endsection
