@verbatim
<section id="integrations" data-nav="Integrations" data-group="Integrations">
  <h2>Integrations: bring your store’s customers in <a class="anchor" href="#integrations">#</a></h2>
  <p>Open <b>Integrations → Connections</b> to connect an online store, website, CRM, email-marketing tool or payments account. The customers (contacts, subscribers, registered users) of that platform are imported into <b>People</b>, and kept up to date automatically.</p>
  <p>Your connected platforms are listed, with filters, under <b>Integrations → All integrations</b>: status, direction, people imported and last sync. Each row has a “⋮” menu to sync, change settings, check the connection, re-import, see its sync history or disconnect. Disconnected integrations stay in the list (filter by status “Disconnected”) and can be connected again from the same menu.</p>
  <p><b>Several stores or accounts of the same platform</b> can be connected: for example three Shopify stores, or two WordPress sites and a Mailchimp account. Each connection has its own <b>name</b>, list, tags, permission choice and schedule. The same store or account cannot be connected twice; the app tells you which connection already uses it.</p>

  <div class="table-wrap"><table>
    <thead><tr><th>Platform</th><th>What is imported</th><th>Marketing permission</th></tr></thead>
    <tbody>
      <tr><td><b>Shopify</b></td><td>Customers of the store: name, email, phone.</td><td>Shopify records each customer’s email marketing state. Customers who agreed are subscribed; customers who unsubscribed in Shopify are unsubscribed here.</td></tr>
      <tr><td><b>WooCommerce</b></td><td>Customers who have an account on the shop: name, email, billing phone. Guests who checked out without an account are not listed by WooCommerce.</td><td>WooCommerce has no marketing permission, so nobody is subscribed by default (see below).</td></tr>
      <tr><td><b>WordPress</b></td><td>Registered users with the roles you choose (subscribers, customers…): name and email.</td><td>None recorded, so nobody is subscribed by default.</td></tr>
      <tr><td><b>Magento</b></td><td>Customers of the store: name, email, phone from their addresses.</td><td>Magento records newsletter subscription. Subscribed customers are subscribed here.</td></tr>
      <tr><td><b>HubSpot</b> (CRM)</td><td>Contacts: name, email, phone or mobile.</td><td>Contacts who opted out of email in HubSpot stay unsubscribed here. HubSpot has no general marketing permission, so nobody else is subscribed by default.</td></tr>
      <tr><td><b>Mailchimp</b></td><td>Every audience, or the one audience you choose: name, email, phone.</td><td>Subscribed members are subscribed here. Unsubscribed, cleaned and pending members are imported but not subscribed.</td></tr>
      <tr><td><b>Klaviyo</b></td><td>Profiles: name, email, phone.</td><td>Profiles with email marketing consent are subscribed; unsubscribed profiles stay unsubscribed.</td></tr>
      <tr><td><b>Brevo</b></td><td>Contacts: name, email, SMS number.</td><td>Blocklisted contacts stay unsubscribed. Brevo does not otherwise record permission here.</td></tr>
      <tr><td><b>Stripe</b> (payments)</td><td>Customers: name, email, phone.</td><td>Stripe records no marketing permission, so nobody is subscribed by default. Paying customers have not necessarily agreed to marketing.</td></tr>
      <tr><td><b>BigCommerce</b></td><td>Customers of the store: name, email, phone. Needs the store hash and an API account token.</td><td>No marketing permission is recorded, so nobody is subscribed by default.</td></tr>
      <tr><td><b>Ecwid</b></td><td>Customers: name, email, phone. Needs the store ID and a secret token.</td><td>Customers who accepted marketing at checkout are subscribed.</td></tr>
      <tr><td><b>Ghost</b> (website)</td><td>Members of your site: name and email.</td><td>Members subscribed to the newsletter are subscribed; members who turned it off are unsubscribed.</td></tr>
      <tr><td><b>Squarespace</b> (website)</td><td>Profiles (customers, subscribers, account holders): name, email.</td><td>Profiles that accept marketing are subscribed.</td></tr>
      <tr><td><b>Webflow</b> (website)</td><td>Users of a site that uses Memberships: name and email. Needs the site ID.</td><td>None recorded, so nobody is subscribed by default.</td></tr>
      <tr><td><b>Wix</b> (website)</td><td>Contacts: name, email, phone. Needs the site ID.</td><td>The contact’s email subscription status is used: subscribed, unsubscribed or not set.</td></tr>
      <tr><td><b>Pipedrive</b> (CRM)</td><td>People: name, email, phone.</td><td>The marketing status is used (subscribed, unsubscribed, no consent).</td></tr>
      <tr><td><b>Salesforce</b> (CRM)</td><td>Contacts: name, email, phone or mobile. Needs your instance address and an access token, which expires: paste a fresh one when the connection needs attention.</td><td>“Email Opt Out” contacts stay unsubscribed.</td></tr>
      <tr><td><b>ActiveCampaign</b> (CRM)</td><td>Contacts: name, email, phone.</td><td>The subscription status is not read, so contacts who unsubscribed there are imported too. Nobody is subscribed by default.</td></tr>
      <tr><td><b>Close</b> (CRM)</td><td>Contacts: name, email, phone.</td><td>None recorded, so nobody is subscribed by default.</td></tr>
      <tr><td><b>MailerLite</b></td><td>Subscribers: name, email, phone.</td><td>Active subscribers are subscribed; unsubscribed, bounced and junk stay unsubscribed.</td></tr>
      <tr><td><b>Kit (ConvertKit)</b></td><td>Subscribers: first name, email.</td><td>Active subscribers are subscribed; the others stay unsubscribed.</td></tr>
      <tr><td><b>Square</b> (payments)</td><td>Customer directory: name, email, phone.</td><td>Customers who unsubscribed from email stay unsubscribed.</td></tr>
      <tr><td><b>Paddle</b> (payments)</td><td>Customers: name, email.</td><td>Customers who agreed to marketing are subscribed.</td></tr>
      <tr><td><b>Chargebee</b> (payments)</td><td>Customers: name, email, phone. Needs the site name and a read-only key.</td><td>None recorded, so nobody is subscribed by default.</td></tr>
      <tr><td><b>Facebook Lead Ads</b> (social media)</td><td>People who filled in one Facebook lead form: name, email, phone. Needs a Page access token and the form ID.</td><td>A lead form records no marketing permission, so leads are not subscribed by default.</td></tr>
      <tr><td><b>Instagram Lead Ads</b> (social media)</td><td>People who filled in one Instagram lead form (same Meta forms). Needs a Page access token and the form ID.</td><td>Same as Facebook: not subscribed by default.</td></tr>
      <tr><td><b>LinkedIn Lead Gen Forms</b> (social media)</td><td>People who filled in the Lead Gen Forms of one LinkedIn ad account: name, email, phone. Needs an access token with r_marketing_leadgen_automation and the ad account ID. Tokens last about 60 days.</td><td>Not subscribed by default.</td></tr>
      <tr><td><b>WhatsApp Business</b> (messaging channel)</td><td>Nothing is imported. It becomes a campaign channel that sends to people's phone numbers (approved template, opt-in needed).</td><td>Only subscribed people are messaged.</td></tr>
      <tr><td><b>Facebook Messenger</b> (messaging channel)</td><td>Nothing is imported. Sends from your Page to people whose Messenger ID is on their profile; Facebook only delivers inside its 24-hour window.</td><td>Only subscribed people are messaged.</td></tr>
      <tr><td><b>X (Twitter) messages</b> (messaging channel)</td><td>Nothing is imported. Sends direct messages to people whose X user ID is on their profile (followers or open DMs; paid X API).</td><td>Only subscribed people are messaged.</td></tr>
      <tr><td><b>GoCardless</b> (payments)</td><td>Customers: name, email, phone.</td><td>None recorded, so nobody is subscribed by default.</td></tr>
    </tbody>
  </table></div>
  <p>The platforms are grouped as <b>Online stores</b>, <b>Websites</b>, <b>CRM</b>, <b>Email marketing</b> and <b>Payments</b>. Some imports read only what changed since the last sync; platforms that cannot filter by date (for example Close, Squarespace and MailerLite) read everyone each time, which is safe: people already here are only completed.</p>

  <h3>Which way people move (Mailchimp)</h3>
  <p>A Mailchimp connection can work in any of three ways, chosen under <b>Which way should people move?</b> when you connect or in the connection’s Settings:</p>
  <ul>
    <li><b>Import only</b> (default): Mailchimp contacts become People.</li>
    <li><b>Send only</b>: your People are added to a Mailchimp audience. Nothing is read from Mailchimp.</li>
    <li><b>Both ways</b>: import first, then send.</li>
  </ul>
  <p>Sending needs the <b>Audience ID</b> (in Mailchimp: Audience → Settings → Audience name and defaults). You can send everyone with an email address, or only the people in one list. <b>Only people who are subscribed here are sent as subscribed.</b> A person who unsubscribed here is marked unsubscribed in Mailchimp if they are already there. Someone who unsubscribed in Mailchimp is never re-subscribed by this app, and people Mailchimp refuses (an invalid email, for example) are counted as “refused” in <b>Sync history</b>. Names and phone numbers you hold here are copied to Mailchimp. After the first sync only people changed since the last one are sent.</p>

  <h3>One-click connect</h3>
  <p>Platforms that offer it show a <b>One-click</b> label and a <b>Connect with …</b> button. Press it, approve read-only access on the platform’s own page, and you are returned here with the connection ready: there is nothing to copy and paste, and your password is never shared with this app. Available for Shopify (you type your store address first), WooCommerce and WordPress (you type your site address), HubSpot, Mailchimp, Klaviyo, Kit, Pipedrive, Salesforce, Square and Stripe. Where the button is not shown, the platform has not been set up for one-click connect on this server yet: use <b>Paste the details instead</b>, which is always available.</p>
  <p>If a connection expires or access is removed, open its <b>Settings</b> and press <b>Reconnect with …</b>. Connecting the same account again renews the existing connection instead of adding a second one.</p>

  <h3>Connect a platform</h3>
  <ol>
    <li>Press <b>Connect</b> on the platform’s card. The window opens with <b>How to get these details</b>: a few steps to follow in your store’s admin (create a read-only key or token). Nothing is changed in your store, and the key only needs permission to <i>read</i> customers.</li>
    <li>Give the connection a <b>name</b> if you like (helpful with several of one platform), then paste your <b>store address</b> (stores and websites only) and the key or token. The address can be typed loosely (<code>my-shop</code> is enough for Shopify).</li>
    <li>Choose <b>what to import</b> (below) and press <b>Connect</b>. The app first checks the connection with your store; if the details are wrong you see the store’s own explanation and nothing is saved.</li>
    <li>With <i>Import the customers now</i> ticked, the import starts at once. The card shows <i>syncing…</i> and fills in when it finishes.</li>
  </ol>
  <div class="note"><b>Your store must use https</b>, because the key is sent with every request. Details are stored encrypted, are never shown again (only the last four characters), and can be replaced in <i>Settings</i> on the card.</div>

  <h3>What to import</h3>
  <ul>
    <li><b>Add them to this list</b> and <b>give them these tags</b>: optional. Everyone imported also gets a tag named after the platform (for example <i>Shopify</i>), so you can always find them.</li>
    <li><b>Who should be subscribed to marketing?</b> The safe default is <b>only people who agreed</b>. People the platform says have not agreed are still imported, but unsubscribed, so campaigns skip them. <b>Everyone, except people who unsubscribed</b> subscribes all of them; you must tick that you have permission to email them.</li>
    <li><b>Keep them up to date</b>: how often new and changed customers are brought in automatically (every 15 minutes, hour, 6 hours, day), or only when you press <b>Sync now</b>.</li>
    <li><b>Start workflows for imported people</b>: off by default, so importing a whole store does not send a welcome email to every customer at once. Turn it on if you want imported people to start workflows such as <i>Welcome new people</i>.</li>
  </ul>

  <h3>What happens to each customer</h3>
  <ul>
    <li>A customer is matched to a person by <b>email</b> (or by phone when there is no email). Nobody is duplicated, however many times you sync.</li>
    <li><b>A new person</b> is created, subscribed according to your choice above, with the list and tags.</li>
    <li><b>An existing person</b> is only <i>completed</i>: blank details (name, phone) are filled in, nothing you already know is overwritten, and a subscription is <b>never switched on</b>.</li>
    <li>The one exception: a customer who <b>unsubscribed on the platform</b> is unsubscribed here too, so you never email someone who opted out in your store.</li>
    <li>A customer with no usable email address or phone number cannot be reached, so is skipped (counted in the run).</li>
  </ul>

  <h3>The connection card</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Button</th><th>What it does</th></tr></thead>
    <tbody>
      <tr><td><b>Sync now</b></td><td>Imports now. After the first import it asks the platform only for customers that changed since the last sync.</td></tr>
      <tr><td><b>Settings</b></td><td>Change the list, tags, subscription choice, how often it syncs, or replace the credentials (leave a credential empty to keep it).</td></tr>
      <tr><td><b>Check connection</b></td><td>Tests the connection again, for example after you changed a password or key in your store.</td></tr>
      <tr><td><b>Full re-import</b></td><td>Reads every customer again. Safe: people who are already here are only completed, never duplicated or overwritten.</td></tr>
      <tr><td><b>History</b></td><td>The import runs of this connection (see below).</td></tr>
      <tr><td><b>Disconnect</b></td><td>Stops importing and deletes the stored credentials. The people already imported <b>stay</b> in People.</td></tr>
    </tbody>
  </table></div>
  <p>A card says <b>needs attention</b> when the last check or import failed; the reason is shown on the card (a changed password, a revoked key, a store that is offline). Fix it in <i>Settings</i> or press <i>Check connection</i>; automatic syncing pauses until the connection works again.</p>

  <h3>Sync history</h3>
  <p><b>Integrations → Sync history</b> lists every import: when it ran, which connection, whether it read every customer or only the changes, and the numbers: <i>read</i>, <i>new people</i>, <i>updated</i> and <i>unchanged / skipped</i>, how long it took, and the error if it failed. A running import updates by itself. Imported people show their platform as the <b>source</b> and in <b>Analytics → People → Where people come from</b>.</p>

  <h3>Good to know</h3>
  <ul>
    <li>Importing is <b>one way</b>: customers come in; nothing is sent back to your store.</li>
    <li>Imports run in the background, so the background sender must be running (see <a href="#problems">Common problems</a>). A big store is imported in chunks and may take a few minutes.</li>
    <li>You enter the key or token you create in your own store admin. There is no sign-in pop-up from the platform, which keeps the access limited to what you chose to give.</li>
    <li>Connecting another kind of system? It can add and update people itself with an API key: see <b>Settings → API keys</b> and the <a href="/docs/api">API documentation</a>.</li>
  </ul>
</section>
@endverbatim
