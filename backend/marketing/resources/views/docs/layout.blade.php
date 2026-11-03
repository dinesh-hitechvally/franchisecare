<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Documentation') · FranchiseCare Marketing</title>
<meta name="description" content="@yield('description', 'FranchiseCare Marketing documentation.')">
<style>
:root {
  --bg: #ffffff; --panel: #f6f7fb; --text: #1f2937; --muted: #5b6472; --line: #e3e6ee; --accent: #3f51b5; --accent-soft: #eceffc;
  --code-bg: #0f172a; --code-text: #e2e8f0; --inline-bg: #eef0f6; --ok: #2e7d32; --ok-bg: #e6f4ea; --warn: #8a5a00; --warn-bg: #fff4dc; --bad: #b3261e; --bad-bg: #fdeceb;
  --get: #1b7f4a; --post: #2f5fd0; --put: #a05a00; --del: #b3261e;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #11151c; --panel: #181e28; --text: #e5e9f0; --muted: #9aa4b2; --line: #2a3240; --accent: #8c9eff; --accent-soft: #1f2744;
    --code-bg: #0a0e14; --code-text: #dbe3ee; --inline-bg: #212a38; --ok: #7ad69a; --ok-bg: #16301f; --warn: #f0c36b; --warn-bg: #33290f; --bad: #ff9a92; --bad-bg: #3a1d1b;
    --get: #7ad69a; --post: #8fb0ff; --put: #f0c36b; --del: #ff9a92;
  }
}
:root[data-theme="dark"] {
  --bg: #11151c; --panel: #181e28; --text: #e5e9f0; --muted: #9aa4b2; --line: #2a3240; --accent: #8c9eff; --accent-soft: #1f2744;
  --code-bg: #0a0e14; --code-text: #dbe3ee; --inline-bg: #212a38; --ok: #7ad69a; --ok-bg: #16301f; --warn: #f0c36b; --warn-bg: #33290f; --bad: #ff9a92; --bad-bg: #3a1d1b;
  --get: #7ad69a; --post: #8fb0ff; --put: #f0c36b; --del: #ff9a92;
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: 76px; }
@media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
body { margin: 0; background: var(--bg); color: var(--text); font: 16px/1.65 system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
a { color: var(--accent); text-decoration: none; } a:hover { text-decoration: underline; }
code, pre, kbd { font-family: ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace; }
code { background: var(--inline-bg); padding: .12em .38em; border-radius: 5px; font-size: .88em; }
pre { position: relative; background: var(--code-bg); color: var(--code-text); padding: 16px 18px; border-radius: 10px; overflow-x: auto; font-size: 13.5px; line-height: 1.6; margin: 14px 0 20px; }
pre code { background: none; padding: 0; color: inherit; font-size: inherit; }
pre .copy { position: absolute; top: 8px; right: 8px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.08); color: #cbd5e1; border-radius: 6px; font: 12px system-ui, sans-serif; padding: 3px 9px; cursor: pointer; }
pre .copy:hover { background: rgba(255,255,255,.18); }
kbd { background: var(--panel); border: 1px solid var(--line); border-bottom-width: 2px; border-radius: 5px; padding: 1px 6px; font-size: .82em; }

/* top bar */
.top { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; gap: 14px; padding: 10px 22px; background: color-mix(in srgb, var(--bg) 92%, transparent); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
.brand { font-weight: 800; letter-spacing: .01em; white-space: nowrap; } .brand span { color: var(--accent); }
.top .sp { flex: 1; }
.search { position: relative; width: min(420px, 46vw); }
.search input { width: 100%; padding: 8px 34px 8px 12px; border-radius: 9px; border: 1px solid var(--line); background: var(--panel); color: var(--text); font: inherit; font-size: 14px; }
.search input:focus { outline: 2px solid color-mix(in srgb, var(--accent) 55%, transparent); border-color: var(--accent); }
.search kbd { position: absolute; right: 8px; top: 7px; }
.btn { border: 1px solid var(--line); background: var(--panel); color: var(--text); border-radius: 9px; padding: 7px 12px; font: inherit; font-size: 14px; cursor: pointer; }
.btn:hover { border-color: var(--accent); }
.menu-btn { display: none; }

/* layout */
.layout { display: grid; grid-template-columns: 280px minmax(0, 1fr); max-width: 1320px; margin: 0 auto; }
#toc { position: sticky; top: 58px; align-self: start; height: calc(100vh - 58px); overflow-y: auto; padding: 22px 14px 40px 22px; border-right: 1px solid var(--line); font-size: 14.5px; }
#toc .grp { margin: 18px 0 6px; font-size: 11.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
#toc .grp:first-child { margin-top: 0; }
#toc a { display: block; padding: 5px 10px; border-radius: 7px; color: var(--text); border-left: 2px solid transparent; }
#toc a:hover { background: var(--panel); text-decoration: none; }
#toc a.on { background: var(--accent-soft); color: var(--accent); font-weight: 600; border-left-color: var(--accent); }
#toc .none { color: var(--muted); padding: 6px 10px; }
main { padding: 34px clamp(20px, 4vw, 56px) 90px; min-width: 0; }
.wrap { max-width: 860px; }

/* content */
section { padding-bottom: 20px; margin-bottom: 30px; border-bottom: 1px solid var(--line); }
section:last-of-type { border-bottom: 0; }
h1 { font-size: clamp(30px, 4vw, 42px); line-height: 1.15; margin: 0 0 12px; letter-spacing: -.02em; }
h2 { font-size: 28px; line-height: 1.25; margin: 0 0 14px; letter-spacing: -.01em; }
h2 .anchor { opacity: 0; margin-left: 8px; font-size: .7em; } h2:hover .anchor { opacity: .6; }
h3 { font-size: 19px; margin: 28px 0 8px; }
h4 { font-size: 15px; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
p { margin: 0 0 14px; } ul, ol { margin: 0 0 16px; padding-left: 22px; } li { margin: 4px 0; }
.lead { font-size: 18px; color: var(--muted); max-width: 70ch; }
table { width: 100%; border-collapse: collapse; margin: 10px 0 22px; font-size: 14.5px; }
th, td { text-align: left; padding: 9px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
th { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); background: var(--panel); }
.table-wrap { overflow-x: auto; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin: 22px 0 8px; }
.stat { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; }
.stat b { display: block; font-size: 28px; line-height: 1.1; color: var(--accent); } .stat span { color: var(--muted); font-size: 13.5px; }
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin: 16px 0 22px; }
.card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 16px 18px; } .card h3 { margin: 0 0 6px; font-size: 16px; } .card p { margin: 0; color: var(--muted); font-size: 14.5px; }
.note, .tip, .warn { border-radius: 10px; padding: 12px 16px; margin: 16px 0; font-size: 15px; border: 1px solid transparent; }
.note { background: var(--accent-soft); border-color: color-mix(in srgb, var(--accent) 30%, transparent); }
.tip { background: var(--ok-bg); border-color: color-mix(in srgb, var(--ok) 35%, transparent); }
.warn { background: var(--warn-bg); border-color: color-mix(in srgb, var(--warn) 40%, transparent); }
.note b:first-child, .tip b:first-child, .warn b:first-child { margin-right: 4px; }
.chip { display: inline-block; padding: 1px 8px; border-radius: 99px; font-size: 12px; font-weight: 600; background: var(--inline-bg); color: var(--muted); white-space: nowrap; }
.chip.token { background: var(--accent-soft); color: var(--accent); } .chip.public { background: var(--ok-bg); color: var(--ok); } .chip.apikey { background: var(--warn-bg); color: var(--warn); }
.m { display: inline-block; min-width: 54px; text-align: center; padding: 1px 6px; border-radius: 6px; font: 700 11.5px ui-monospace, Menlo, Consolas, monospace; color: #fff; background: var(--muted); }
.m-GET { background: var(--get); } .m-POST { background: var(--post); } .m-PUT, .m-PATCH { background: var(--put); } .m-DELETE { background: var(--del); }
:root[data-theme="dark"] .m, :root:not([data-theme="light"]) .m { color: #0b0f14; }
@media (prefers-color-scheme: light) { :root:not([data-theme="dark"]) .m { color: #fff; } }
details.group { border: 1px solid var(--line); border-radius: 12px; margin: 12px 0; background: var(--bg); overflow: hidden; }
details.group > summary { cursor: pointer; padding: 12px 16px; display: flex; gap: 12px; align-items: baseline; list-style: none; background: var(--panel); }
details.group > summary::-webkit-details-marker { display: none; }
details.group > summary::before { content: "▸"; color: var(--muted); } details.group[open] > summary::before { content: "▾"; }
details.group > summary b { font-size: 16px; } details.group > summary small { color: var(--muted); }
details.group > summary .n { margin-left: auto; color: var(--muted); font-size: 13px; }
details.group table { margin: 0; table-layout: fixed; }
details.group td:nth-child(1) { width: 150px; white-space: nowrap; } details.group td:nth-child(2) { width: 38%; } details.group td:nth-child(4) { width: 150px; }
@media (max-width: 760px) { details.group table { table-layout: auto; min-width: 600px; } details.group td:nth-child(n) { width: auto; } .endpoints td code { word-break: normal; } }
.hidden { display: none !important; }
.endpoints td code { background: none; padding: 0; font-size: 13px; word-break: break-all; }
.legend { display: flex; flex-wrap: wrap; gap: 8px 18px; color: var(--muted); font-size: 14px; margin: 8px 0 4px; }
.back-top { position: fixed; right: 18px; bottom: 18px; z-index: 20; }
footer { color: var(--muted); font-size: 13.5px; padding-top: 6px; }

@media (max-width: 960px) {
  .layout { grid-template-columns: 1fr; }
  #toc { position: fixed; z-index: 40; left: 0; top: 58px; width: min(320px, 86vw); background: var(--bg); transform: translateX(-102%); transition: transform .2s; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
  body.toc-open #toc { transform: none; }
  .menu-btn { display: inline-block; }
  .search kbd { display: none; }
}
@media (max-width: 600px) {
  .top { padding: 8px 12px; gap: 8px; } .brand { font-size: 14px; } .top .sp { display: none; }
  .search { flex: 1; width: auto; min-width: 0; }
}
@media print { .top, #toc, .back-top, pre .copy { display: none !important; } .layout { display: block; } details.group { break-inside: avoid; } details.group:not([open]) > :not(summary) { display: block; } }

.docs-nav { display: flex; gap: 4px; margin-left: 10px; }
.docs-nav a { padding: 6px 12px; border-radius: 8px; color: var(--muted); font-size: 14.5px; font-weight: 600; white-space: nowrap; }
.docs-nav a:hover { background: var(--panel); text-decoration: none; color: var(--text); }
.docs-nav a.on { background: var(--accent-soft); color: var(--accent); }
.hub { max-width: 1040px; margin: 0 auto; padding: 56px clamp(20px, 4vw, 48px) 80px; }
.hub .docs { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 18px; margin: 30px 0; }
.hub .doc { display: flex; flex-direction: column; gap: 10px; background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 24px; color: var(--text); transition: border-color .15s, transform .15s; }
.hub .doc:hover { border-color: var(--accent); transform: translateY(-2px); text-decoration: none; }
.hub .doc small { text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: var(--accent); }
.hub .doc h2 { margin: 0; font-size: 22px; } .hub .doc p { margin: 0; color: var(--muted); font-size: 15px; } .hub .doc ul { margin: 6px 0 0; color: var(--muted); font-size: 14.5px; } .hub .doc .go { margin-top: auto; padding-top: 10px; color: var(--accent); font-weight: 700; }
@media (max-width: 960px) { .docs-nav { display: none; } .docs-nav.show { display: flex; } }
@media (max-width: 600px) { .docs-nav { display: none; } }
</style>
</head>
<body>

<header class="top">
  @unless (View::hasSection('hub'))<button class="btn menu-btn" id="menuBtn" aria-label="Open contents">☰</button>@endunless
  <a class="brand" href="{{ url('/') }}" style="color: inherit; text-decoration: none">FranchiseCare <span>Marketing</span></a>
  <nav class="docs-nav" aria-label="Documents">
    <a href="{{ url('/docs/manual') }}" @class(['on' => ($doc ?? '') === 'manual'])>Manual</a>
    <a href="{{ url('/docs/developer') }}" @class(['on' => ($doc ?? '') === 'developer'])>Install &amp; code</a>
    <a href="{{ url('/docs/api') }}" @class(['on' => ($doc ?? '') === 'api'])>API</a>
  </nav>
  <div class="sp"></div>
  @unless (View::hasSection('hub'))<div class="search"><input id="q" type="search" placeholder="Search this document…" autocomplete="off" aria-label="Search"><kbd>/</kbd></div>@endunless
  <button class="btn" id="theme" title="Switch light / dark" aria-label="Switch light or dark">◐</button>
</header>

@if (View::hasSection('hub'))
  @yield('content')
@else
<div class="layout">
  <nav id="toc" aria-label="Contents"><noscript>Use your browser’s search (Ctrl+F) to find a topic.</noscript></nav>
  <main><div class="wrap">
    @yield('content')
    <footer>
      Generated {{ $env['generated'] }} · Laravel {{ $env['laravel'] }} · PHP {{ $env['php'] }} ·
      <a href="{{ url('/docs/manual') }}">Manual</a> · <a href="{{ url('/docs/developer') }}">Install &amp; code</a> · <a href="{{ url('/docs/api') }}">API</a><br>
      Parts of this page are built from the running app, so they are never typed by hand.
    </footer>
  </div></main>
</div>
<a class="btn back-top hidden" id="top" href="#" aria-label="Back to the top">↑ Top</a>
@endif

<script>
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // theme: follows the system, a click overrides and is remembered
  var root = document.documentElement;
  try { var saved = localStorage.getItem('docs-theme'); if (saved) root.setAttribute('data-theme', saved); } catch (e) {}
  if ($('#theme')) $('#theme').addEventListener('click', function () {
    var dark = root.getAttribute('data-theme') ? root.getAttribute('data-theme') === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    var next = dark ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('docs-theme', next); } catch (e) {}
  });

  // contents, built from the sections so it can never disagree with the page
  var toc = $('#toc') || document.createElement('div'), sections = $$('section[data-nav]'), lastGroup = '';
  sections.forEach(function (s) {
    var g = s.getAttribute('data-group');
    if (g !== lastGroup) { var h = document.createElement('div'); h.className = 'grp'; h.textContent = g; toc.appendChild(h); lastGroup = g; }
    var a = document.createElement('a'); a.href = '#' + s.id; a.textContent = s.getAttribute('data-nav'); a.setAttribute('data-for', s.id); toc.appendChild(a);
    s.setAttribute('data-text', s.textContent.toLowerCase());
  });
  var none = document.createElement('div'); none.className = 'none hidden'; none.textContent = 'Nothing matches.'; toc.appendChild(none);
  var links = $$('#toc a');

  // phone: the contents slide in
  if ($('#menuBtn')) $('#menuBtn').addEventListener('click', function () { document.body.classList.toggle('toc-open'); });
  if (toc) toc.addEventListener('click', function (e) { if (e.target.tagName === 'A') document.body.classList.remove('toc-open'); });

  // the section being read is marked in the contents
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { links.forEach(function (l) { l.classList.toggle('on', l.getAttribute('data-for') === en.target.id); }); } });
    }, { rootMargin: '-72px 0px -70% 0px' });
    sections.forEach(function (s) { io.observe(s); });
  }
  var topBtn = $('#top');
  if (topBtn) addEventListener('scroll', function () { topBtn.classList.toggle('hidden', scrollY < 900); }, { passive: true });

  // copy buttons on code blocks
  $$('pre').forEach(function (pre) {
    var b = document.createElement('button'); b.className = 'copy'; b.type = 'button'; b.textContent = 'Copy';
    b.addEventListener('click', function () {
      var t = pre.querySelector('code') ? pre.querySelector('code').innerText : pre.innerText;
      var done = function () { b.textContent = 'Copied'; setTimeout(function () { b.textContent = 'Copy'; }, 1500); };
      if (navigator.clipboard) navigator.clipboard.writeText(t).then(done, done); else done();
    });
    pre.appendChild(b);
  });

  // headings get a link
  // endpoints: expand / collapse
  if ($('#openAll')) $('#openAll').addEventListener('click', function () { $('details.group').forEach(function (d) { d.open = true; }); });
  if ($('#closeAll')) $('#closeAll').addEventListener('click', function () { $('details.group').forEach(function (d) { d.open = false; }); });

  // search: filters the contents and the endpoint rows
  var q = $('#q') || document.createElement('input');
  function search() {
    var v = q.value.trim().toLowerCase(), shown = 0;
    sections.forEach(function (s) {
      var hit = !v || s.getAttribute('data-text').indexOf(v) !== -1;
      var link = $('#toc a[data-for="' + s.id + '"]'); if (link) link.classList.toggle('hidden', !hit);
      if (hit) shown++;
    });
    $$('#toc .grp').forEach(function (g) {
      var n = g.nextElementSibling, any = false;
      while (n && !n.classList.contains('grp') && !n.classList.contains('none')) { if (!n.classList.contains('hidden')) any = true; n = n.nextElementSibling; }
      g.classList.toggle('hidden', !any);
    });
    none.classList.toggle('hidden', shown > 0);
    var rows = 0;
    $$('details.group').forEach(function (d) {
      var any = 0;
      $$('tr[data-q]', d).forEach(function (r) { var m = !v || r.getAttribute('data-q').indexOf(v) !== -1; r.classList.toggle('hidden', !m); if (m) any++; });
      d.classList.toggle('hidden', v && !any && d.getAttribute('data-group').indexOf(v) === -1);
      if (v && any) d.open = true;
      rows += any;
    });
    if ($('#noEndpoints')) $('#noEndpoints').classList.toggle('hidden', !(v && rows === 0));
  }
  q.addEventListener('input', search);
  addEventListener('keydown', function (e) {
    if (e.key === '/' && document.activeElement !== q && !/input|textarea|select/i.test(document.activeElement.tagName)) { e.preventDefault(); q.focus(); }
    if (e.key === 'Escape' && document.activeElement === q) { q.value = ''; search(); q.blur(); }
  });

  // a link to an endpoint group opens it
  if (location.hash) { var t = $(location.hash); if (t && t.tagName === 'DETAILS') t.open = true; }
})();
</script>
</body>
</html>
