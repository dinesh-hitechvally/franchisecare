@verbatim
<section id="settings" data-nav="Settings" data-group="Setup">
  <h2>Settings <a class="anchor" href="#settings">#</a></h2>
  <p>Everything under <b>Settings</b> applies to your company.</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Section</th><th>What you set</th></tr></thead>
    <tbody>
      <tr><td><b>My account</b></td><td>Your name, sign-in email and password.</td></tr>
      <tr><td><b>Company profile</b></td><td>Business name, phone, website, support email, postal address and the email footer. The company logo is chosen in <b>Media</b>.</td></tr>
      <tr><td><b>Email sending</b></td><td>The sender name and address, the reply-to address, and the mail service that actually delivers your email (below).</td></tr>
      <tr><td><b>SMS sending</b></td><td>The SMS provider and the sender.</td></tr>
      <tr><td><b>Users</b></td><td>Who can sign in to your company. Everyone you add has full access; there are no roles. A new user always joins your company.</td></tr>
      <tr><td><b>API keys</b></td><td>Keys that let other systems (your website, Zapier, a CRM) add and look up people. See the <a href="/docs/api">API documentation</a>.</td></tr>
      <tr><td><b>My activity</b></td><td>A list of your own actions, with a <i>View</i> button for the details of each.</td></tr>
    </tbody>
  </table></div>

  <h3>Email services</h3>
  <p>Under <b>Email sending → Mail services</b> you can keep several connections and mark one as <b>in use</b>:</p>
  <ul>
    <li><b>Google</b> or <b>Microsoft</b>: sign in with one click, no passwords to copy.</li>
    <li><b>SMTP</b>: your own mail server or provider (host, port, user, password).</li>
    <li><b>Log only</b>: nothing is sent; each message is written to a log. Perfect for trying things safely.</li>
  </ul>
  <p>Use <b>Send a test email</b> to check it works. If a message fails, <b>Message logs</b> explains why in plain words (wrong password, a blocked port, a service that is not running…).</p>

  <h3>SMS services</h3>
  <p>Choose one of Twilio, Vonage, MessageBird, MessageMedia, ClickSend, Sinch, Plivo or Telnyx, enter its details and use <b>Send a test SMS</b>. <i>Log only</i> works here too. Keep texts short: one message is 160 characters of plain text, fewer with emoji or accents.</p>

  <h3>Sending speed</h3>
  <p>Messages are sent at your company’s sending speed (messages per minute), so your mail account’s limits are not hit. A big campaign therefore takes a little while to finish, and its status stays <i>sending</i> until the last message has gone.</p>
</section>
@endverbatim
