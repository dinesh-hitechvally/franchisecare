@verbatim
<section id="push" data-nav="Push notifications" data-group="Campaigns">
  <h2>Push notifications: browser and mobile app <a class="anchor" href="#push">#</a></h2>
  <p>Push is a third channel next to email and SMS. A <b>push campaign</b> is created, scheduled, tested, sent and reported on exactly like the others: choose <b>Push notification</b> as the channel on the campaign page. A notification has a <b>title</b>, a <b>message</b>, an optional <b>link</b> that opens when it is tapped, and an optional <b>picture</b>. <code>{{first_name}}</code> and the other merge tags work in the title and message.</p>

  <h3>Who receives it</h3>
  <p>People receive push on the <b>devices</b> that registered for them: a browser that allowed notifications on your website, or an Android or iOS app. A person with a phone and a laptop gets a notification on each. People with no registered device are <b>skipped</b> (counted in the report as “no device”), and people who are unsubscribed are never sent to, as with email and SMS.</p>

  <p><b>Choosing the audience.</b> With the Push channel selected, the Audience list shows only <b>people who have a device registered</b>, and the count next to each list, tag and segment counts only those people (the preview also shows how many devices each person has). A <b>Subscription</b> filter is available as for the other channels. Pick <b>All devices</b> to send to <b>every registered browser and app</b> (a <b>Devices</b> menu then lets you narrow it to browsers only, Android apps only or iOS apps only), including devices that are not linked to a person (for example a visitor who turned notifications on without giving an email). Then there is no audience and no Subscription filter; devices of people who unsubscribed are still left out. The campaign check and report count devices instead of people.</p>

  <h3>Set it up: Settings → Push notifications</h3>
  <ol>
    <li><b>Sending mode.</b> It starts in <b>Test mode</b>: notifications are only written to the message log (“logged only”), so you can try a campaign safely. Switch to <b>Live</b> to deliver them.</li>
    <li><b>Websites.</b> The easiest way is inside this app: add the <b>Push notifications</b> block to a landing page, or the <b>Push notifications</b> field to a form (both described below). To use it on your own website outside this app, download the service worker file from Settings and put it at the top level of your site (<code>/mate-push-sw.js</code>), then add the two lines of the snippet shown there (your site must use https). No third-party account is needed.</li>
    <li><b>Android and iOS apps.</b> Create a Firebase project for your app, generate a <b>service account</b> key (Firebase → Project settings → Service accounts) and paste the file into Settings → Push notifications. Your app then registers each installation with the <a href="/docs/api#push-devices">device API</a>.</li>
  </ol>
  <p>Every <b>registered device</b> is listed under <b>Audience → Devices</b>, with filters (browser or app, active or turned off, linked to a person or not), the <b>page where a browser turned notifications on</b> (for a browser registered from a page or form), and a “⋮” menu to send a test notification to that device, see its history, or remove it. <b>Device histories</b> (button at the top) lists every change to every device: when it registered, was linked to a person, turned notifications off or on again, or was removed. The device token and keys are never recorded. A device that is not linked to a person cannot be reached by a campaign.</p>

  <h3>Let people turn notifications on from a page or a form</h3>
  <p><b>Page builder.</b> In Content → Pages, add the <b>Notification</b> block. It looks like the alert boxes websites show: an icon, a bold title, a message, an optional button and an optional close (×) icon, in one of five styles: <b>Info</b> (blue), <b>Success</b> (green), <b>Error</b> (red), <b>Warning</b> (yellow) or <b>Basic</b> (white). It stays exactly where you place it in the page. In the block's settings choose:</p>
  <ul>
    <li><b>Show the close (×) icon</b> and <b>Show the button</b>: each can be switched on or off.</li>
    <li><b>When the button is clicked</b>: <b>Turn on push notifications</b> (the browser asks for permission, and once the visitor allows it the browser is registered as a device; no email is needed), <b>Redirect to another URL</b> (optionally in a new tab), or <b>Close the notification</b>.</li>
    <li>For push, <b>Let visitors turn notifications off again</b>: once notifications are on for that browser, the Allow button is replaced by a <b>Turn off notifications</b> button. Pressing it removes the browser from the devices (it shows as “turned off” under Audience → Devices, and in the device history) and the Allow button comes back, so it can be turned on again. You can change the texts of both buttons and the messages.</li>
    <li><b>Width</b>: <b>Container width</b> (as wide as the page content), <b>Full width</b> (edge to edge) or <b>Set my own width</b> (a width in pixels, aligned left, centre or right).</li>
    <li>Without a push action it can also <b>hide itself</b> after a number of seconds. The close (×) on a push notification hides it for a week.</li>
  </ul>
  <p>Add the block to a page and publish the page.</p>
  <p><b>Form builder.</b> In Content → Forms, <b>People fields → Push notifications</b> adds a tick box (“Send me notifications on this device”). When a visitor ticks it their browser asks for permission; after they send the form that browser is registered. If the form also asks for an email (or mobile) the device is <b>linked to that person</b>, so push campaigns to a list, tag or segment can reach them; without one the device is not linked and is reached by a campaign sent to <b>All devices</b>. The tick box is not saved on the person; the submission only records Yes or No.</p>
  <p class="note">It works on pages and forms opened on their own address on this app. One shown inside another website's frame cannot ask for notification permission (browsers do not allow it): link to the page or form instead.</p>

  <p><b>One device, however often it is turned off and on.</b> A browser gets a brand new push address every time notifications are turned on again, so each browser also keeps an id of its own (in its storage). Turning notifications off and on again therefore keeps <b>one row</b> in Audience → Devices, which shows as “turned off” and “active” as it changes; every turn off and on is listed in that device's history. Apps can do the same by sending a <code>device_id</code> (the app's own install id) with the <a href="/docs/api#push-devices">device API</a>: a new Firebase token for the same installation keeps one device. Two different browsers are always two devices, and clearing a browser's site data starts a new one.</p>

  <h3>Testing and reports</h3>
  <ul>
    <li><b>Send test</b> on the campaign page takes the email address of a person who has a device, and sends the notification to that person’s devices.</li>
    <li>The campaign report shows <b>Delivered, Skipped, Failed and Logged only</b>, and how many people <b>tapped</b> the notification (the link is counted before it goes on to your page). <b>Message logs</b> has a Push channel.</li>
    <li>When a browser or app says a device no longer exists (the person turned notifications off, or removed the app), that device is switched off and not used again.</li>
  </ul>
  <p class="note">Notifications work only where the browser or the app allows them. Safari and iOS need the website to be added to the home screen, or a native app, before they can receive push.</p>
</section>
@endverbatim
