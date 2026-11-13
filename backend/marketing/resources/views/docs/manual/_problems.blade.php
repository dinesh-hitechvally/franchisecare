@verbatim
<section id="problems" data-nav="Common problems" data-group="Help">
  <h2>Common problems <a class="anchor" href="#problems">#</a></h2>
  <div class="table-wrap"><table>
    <thead><tr><th>You see</th><th>Why</th><th>What to do</th></tr></thead>
    <tbody>
      <tr><td>Messages stay <b>Queued</b>, or an automation says “in progress” and nothing is sent</td><td>The background sender is not running.</td><td>Ask whoever runs the server to start it (<code>php artisan queue:work</code>). Nothing is lost; the messages go out when it runs.</td></tr>
      <tr><td>A message <b>Failed</b> with “connection refused” and a port number such as 1025</td><td>On a test setup the mail service is Mailpit and it is not running.</td><td>Start Mailpit (in Laragon: Menu → Mail → Start Mailpit) and send again. Test mail appears at <code>http://localhost:8025</code> and never reaches real inboxes.</td></tr>
      <tr><td>Everything is <b>Logged only</b></td><td>The email or SMS service in use is <i>Log only</i>.</td><td>Choose a real service in <b>Settings → Email / SMS sending</b>, then send again.</td></tr>
      <tr><td>A campaign shows a <b>Skipped</b> count</td><td>Those people have no email address (or phone number for SMS).</td><td>Open the campaign’s report: the Delivery Reports list names them. Add their details in <b>People</b> for next time.</td></tr>
      <tr><td>The campaign page shows fewer people than <b>People</b></td><td>A sent campaign shows its audience as it was when sent.</td><td>Nothing is wrong. People added later are not part of that send.</td></tr>
      <tr><td>Someone says they never got it</td><td>They may be unsubscribed, have no address, or the message failed.</td><td>Find them in <b>People</b> (the Subscription column) and in the campaign’s Delivery Reports; <b>Message logs</b> shows what happened to each message.</td></tr>
      <tr><td>A form says “The check has expired”</td><td>The captcha sum is older than 6 hours, was already used, or was answered wrongly 5 times.</td><td>Reload the page for a new sum.</td></tr>
      <tr><td>A form says “That answer is not right”</td><td>The captcha answer is wrong.</td><td>Answer the sum again.</td></tr>
      <tr><td>An integration stays <b>syncing…</b>, or its card says <b>needs attention</b></td><td>Imports run in the background: either the background sender is not running, or the store refused the connection (a changed password, a revoked key, a store that is offline).</td><td>Start the sender (see the first row). For <i>needs attention</i> read the reason on the card, then fix it in <b>Settings</b> on the card or press <b>Check connection</b>.</td></tr>
      <tr><td>Imported customers are not receiving campaigns</td><td>On <i>Only people who agreed</i> a platform that records no marketing permission (WooCommerce, WordPress) imports people as unsubscribed.</td><td>If you have their permission, change the card’s <b>Settings</b> to <i>Everyone</i> and run a <b>Full re-import</b> for new people, or subscribe them in <b>People</b>. Nobody who opted out is ever re-subscribed.</td></tr>
      <tr><td>A scheduled time seems off by hours</td><td>Times are stored in UTC and a schedule is kept exactly as typed.</td><td>Enter schedules in UTC (the editor shows the current UTC time).</td></tr>
      <tr><td>A page, form or template shows an old version</td><td>You are looking at a saved copy from History.</td><td>Use <b>Back to current version</b>. History is read-only; nothing is changed by looking.</td></tr>
    </tbody>
  </table></div>
  <p>Still stuck? Developers can find technical causes in the <a href="/docs/developer#troubleshooting">troubleshooting table</a> of the developer guide.</p>
</section>
@endverbatim
