@extends('docs.layout')

@section('title', 'Documentation')
@section('description', 'The FranchiseCare Marketing documentation: the project manual, installation and code, and the API.')
@section('hub', '1')

@section('content')
<div class="hub">
  <h1>FranchiseCare Marketing documentation</h1>
  <p class="lead">Three separate guides, one for each kind of reader. Pick the one you need.</p>

  <div class="docs">
    <a class="doc" href="{{ url('/docs/manual') }}">
      <small>For the people who use it</small>
      <h2>Project manual</h2>
      <p>What every screen does and how to get things done, in plain language.</p>
      <ul>
        <li>Your first campaign, step by step</li>
        <li>People, lists, tags, segments, subscriptions</li>
        <li>Forms, pages, templates, email and SMS</li>
        <li>Workflows, reports and analytics</li>
      </ul>
      <span class="go">Open the manual →</span>
    </a>

    <a class="doc" href="{{ url('/docs/developer') }}">
      <small>For the people who run and change it</small>
      <h2>Install &amp; code</h2>
      <p>Everything to install the project and understand its code.</p>
      <ul>
        <li>Requirements, installation, settings, tests</li>
        <li>Architecture and a map of the code</li>
        <li>The data model, jobs and the scheduler</li>
        <li>How to extend it, deployment, security</li>
      </ul>
      <span class="go">Open the developer guide →</span>
    </a>

    <a class="doc" href="{{ url('/docs/api') }}">
      <small>For the systems that connect to it</small>
      <h2>API</h2>
      <p>Create an API key in <b>Settings → API keys</b> and connect another system.</p>
      <ul>
        <li>Creating and revoking API keys</li>
        <li>The integration API, with examples</li>
        <li>Webhooks and signatures, public endpoints</li>
        <li>The dashboard API and every endpoint</li>
      </ul>
      <span class="go">Open the API docs →</span>
    </a>
  </div>

  <p style="color: var(--muted); font-size: 14px">Generated {{ $env['generated'] }} · Laravel {{ $env['laravel'] }} · PHP {{ $env['php'] }}. These pages are on everywhere except production; set <code>DOCS_ENABLED=true</code> to publish them there.</p>
</div>
@endsection
