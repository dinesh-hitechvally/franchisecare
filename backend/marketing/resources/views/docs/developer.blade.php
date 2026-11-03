@extends('docs.layout')

@section('title', 'Install & code')
@section('description', 'Install FranchiseCare Marketing and understand its code: requirements, setup, architecture, data model, extending, deployment.')

@section('content')
  <section id="overview" data-nav="Overview" data-group="Start">
    <h1>Install &amp; code</h1>
    <p class="lead">How to install FranchiseCare Marketing, how the code is organised, and how to extend and run it. For what each screen does, see the <a href="{{ url('/docs/manual') }}">project manual</a>; for connecting other systems, the <a href="{{ url('/docs/api') }}">API documentation</a>.</p>
    <div class="stats">
      <div class="stat"><b>{{ $endpointCount }}</b><span>API endpoints</span></div>
      <div class="stat"><b>{{ $triggerCount }}</b><span>automation triggers</span></div>
      <div class="stat"><b>{{ count($events) }}</b><span>webhook events</span></div>
      <div class="stat"><b>{{ count($tables['tables']) }}</b><span>data tables</span></div>
    </div>
    <p>Two applications live in one repository: a <b>Laravel {{ $env['laravel'] }}</b> API (PHP {{ $env['php'] }}, Sanctum tokens, MySQL, a database queue) in <code>backend/marketing</code>, and a <b>Vue 3</b> single-page app (Vite, Pinia, vue-router) in <code>frontend/marketing</code>. The API also serves these documentation pages and the hosted forms and pages.</p>
  </section>

  @include('docs.developer._install')
  @include('docs.developer._architecture')

  <section id="data" data-nav="Data model" data-group="Code">
    <h2>Data model <a class="anchor" href="#data">#</a></h2>
    <p>The app’s own tables, read from the database. Every table that holds a company’s data has a <code>company_id</code> and is only ever read through that company (see <a href="#tenancy">Multi-tenancy</a>). Dates are <code>DATETIME</code> columns holding UTC.</p>
    <div class="table-wrap"><table>
      <thead><tr><th>Table</th><th>What it holds</th></tr></thead>
      <tbody>
      @foreach ($tables['tables'] as $t)
        <tr><td><code>{{ $t['name'] }}</code></td><td>{{ $t['note'] }}</td></tr>
      @endforeach
      </tbody>
    </table></div>
    <h3>History mirrors</h3>
    <p>Each of {{ $tables['histories'] }} tables has a mirror called <code>&lt;table&gt;_history</code> (for example <code>people_history</code>). After every create, update, delete or restore a full copy of the row is written there with <code>history_id</code>, <code>change_action</code>, <code>changed_at</code>, <code>changed_by</code>, <code>changed_fields</code> and <code>record_label</code>. The <b>History</b> screens read these; the mirrors are never edited.</p>
    <p>Kinds of record that keep a history (<code>GET /api/history/{type}/{id}</code>): @foreach ($historyTypes as $t)<code>{{ $t }}</code>@if (! $loop->last), @endif @endforeach</p>
    <h3>Conventions</h3>
    <ul>
      <li><b>Soft deletes</b> on every business table: <code>deleted_at</code>; a deleted row stays and is hidden by the model scope.</li>
      <li><b>Naming:</b> the people you market to are <code>people</code> / <code>Person</code> (earlier called contacts): <code>person_id</code>, <code>people_lists</code>, <code>people_list_members</code>, <code>person_tag</code>, <code>person_fields</code>, <code>person_field_values</code>.</li>
      <li><b>Subscription</b> is one flag on <code>people</code>: <code>is_subscribed</code> and <code>subscribed_at</code>. Set it through <code>App\Services\Subscriptions::set()</code> so webhooks and workflows fire.</li>
      <li><b>Send logs</b> are two tables, <code>email_logs</code> and <code>sms_logs</code>, one row per message. People skipped for a missing address are counted on the campaign (<code>audience_skipped</code>, <code>audience_total</code>) and never written as log rows.</li>
    </ul>
  </section>

  @include('docs.developer._extend')
  @include('docs.developer._ops')
  @include('docs.developer._problems')
@endsection
