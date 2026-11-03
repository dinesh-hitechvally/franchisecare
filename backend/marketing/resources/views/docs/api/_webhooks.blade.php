@verbatim
  <h3>The request we send</h3>
  <p>A <code>POST</code> with a JSON body and two headers. The body is always <code>{ "event", "created_at", "data" }</code>. For example <code>person.created</code>:</p>
<pre><code>POST https://your-server/hooks/marketing
Content-Type: application/json
X-Webhook-Event: person.created
X-Webhook-Signature: sha256=3c9f…e1
User-Agent: FranchiseCareMarketing-Webhook/1.0

{ "event": "person.created", "created_at": "2026-11-03T09:15:00+00:00",
  "data": { "id": 14, "first_name": "Ann", "last_name": "Lee", "email": "ann@example.com", "phone": null, "is_subscribed": true, "source": "form" } }</code></pre>
  <div class="table-wrap"><table>
    <thead><tr><th>Event</th><th><code>data</code> holds</th></tr></thead>
    <tbody>
      <tr><td><code>person.created</code>, <code>person.unsubscribed</code></td><td>The person: <code>id, first_name, last_name, email, phone, is_subscribed, source</code>.</td></tr>
      <tr><td><code>form.submitted</code></td><td>The form’s <code>name</code> and <code>type</code>, the <code>person</code> (or <code>null</code> when the form does not save people) and the <code>answers</code> as a list of <code>label, type, value</code>.</td></tr>
      <tr><td><code>campaign.sent</code></td><td>The campaign <code>id, name, type, status</code> and the counts <code>sent, failed, skipped, logged</code>.</td></tr>
      <tr><td><code>message.failed</code></td><td><code>channel</code> (<code>email</code> or <code>sms</code>), <code>recipient</code>, <code>campaign_id</code> (or <code>null</code>) and <code>error</code> (the reason).</td></tr>
      <tr><td><code>workflow.completed</code></td><td>The <code>workflow</code> name and the <code>person</code>.</td></tr>
    </tbody>
  </table></div>

  <h3>Verify the signature</h3>
  <p><code>X-Webhook-Signature</code> is <code>sha256=</code> followed by the hex HMAC-SHA256 of the <b>raw request body</b>, keyed with the webhook’s secret. Compute it yourself and compare in constant time before you trust the call:</p>
<pre><code>// PHP
$raw = file_get_contents('php://input');
$expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
if (! hash_equals($expected, $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '')) { http_response_code(401); exit; }

// Node (Express: keep the raw body, e.g. express.raw({ type: 'application/json' }))
const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(req.body).digest('hex')
const given = req.get('x-webhook-signature') || ''
if (given.length !== expected.length || !crypto.timingSafeEqual(Buffer.from(given), Buffer.from(expected))) return res.sendStatus(401)

# Python
expected = 'sha256=' + hmac.new(secret.encode(), raw_body, hashlib.sha256).hexdigest()
if not hmac.compare_digest(expected, request.headers.get('X-Webhook-Signature', '')): abort(401)</code></pre>

  <h3>Delivery rules</h3>
  <ul>
    <li>Answer with any <b>2xx within 10 seconds</b> to say it arrived. Redirects are not followed, and a failed call is <b>not retried</b>; the result is recorded.</li>
    <li><b>Automation → Webhooks → Webhook deliveries</b> lists every call with its status, the response and when it happened. A <i>test</i> button sends a sample.</li>
    <li>The address must be public. Addresses that point at private or local networks are refused unless the server runs with <code>OUTBOUND_ALLOW_PRIVATE=true</code> (development only).</li>
    <li>Because a call is not retried, make your handler quick and do the real work afterwards, and use <b>Webhook deliveries</b> to spot failures.</li>
  </ul>
@endverbatim
