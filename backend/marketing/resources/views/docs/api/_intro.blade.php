@verbatim
<section id="overview" data-nav="Overview" data-group="Start">
  <h1>API documentation</h1>
  <p class="lead">Connect your website, Zapier, a CRM or any other system to FranchiseCare Marketing. Create an API key in <b>Settings → API keys</b>, then add people, look them up and unsubscribe them. Webhooks let us tell your system when something happens.</p>

  <div class="cards">
    <div class="card"><h3>API keys</h3><p>Created in <b>Settings → API keys</b>. For another system that adds or looks up people. Works on <code>/api/v1</code> only.</p></div>
    <div class="card"><h3>Webhooks</h3><p>Created under <b>Automation → Webhooks</b>. We POST an event (a person was added, a campaign finished…) to your address, signed.</p></div>
    <div class="card"><h3>Public endpoints</h3><p>No sign-in. A hosted form’s submit address, tracking and unsubscribe links.</p></div>
    <div class="card"><h3>Dashboard API</h3><p>The API the app itself uses: sign in, get a token, call everything. Documented at the end, with every endpoint.</p></div>
  </div>

  <h3>Which one do I need?</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>I want to…</th><th>Use</th></tr></thead>
    <tbody>
      <tr><td>Add or update a person from my website, a checkout or Zapier</td><td><a href="#v1-upsert">Integration API → create or update a person</a></td></tr>
      <tr><td>Look up a person’s lists, tags and subscription</td><td><a href="#v1-lookup">Integration API → look up</a></td></tr>
      <tr><td>Opt a person out from another system</td><td><a href="#v1-unsubscribe">Integration API → unsubscribe</a></td></tr>
      <tr><td>Be told when someone is added, unsubscribes, a form is submitted or a campaign finishes</td><td><a href="#webhooks">Webhooks</a></td></tr>
      <tr><td>Post a sign-up form from my own page</td><td><a href="#public-api">Public endpoints</a></td></tr>
      <tr><td>Build my own dashboard or automate everything the app can do</td><td><a href="#dashboard-api">Dashboard API</a></td></tr>
    </tbody>
  </table></div>

  <h3>Basics that apply everywhere</h3>
  <ul>
    <li>Send <code>Accept: application/json</code> and, for a body, <code>Content-Type: application/json</code>.</li>
    <li>Times are UTC. Failures use one <a href="#errors">error shape</a>.</li>
    <li>Everything a key or token does happens inside <b>one company</b>: yours. It can never see another company’s data.</li>
    <li>Over HTTPS in production. Never put a key or token in a web page that visitors can read.</li>
  </ul>
</section>
@endverbatim
