<script setup>
// The Settings hub: one place, a section list on the left, the section on the right.
const GROUPS = [
  { title: 'Account', items: [['My account', '/settings/account', 'Your name, sign-in email and password']] },
  { title: 'Company', items: [
    ['Company profile', '/settings/company', 'Name, address, contact details, email footer'],
    ['Email sending', '/settings/email', 'Sender, reply-to and SMTP delivery'],
    ['SMS sending', '/settings/sms', 'SMS provider and sender'],
  ] },
  { title: 'Team', items: [['Users', '/settings/users', 'Who can sign in to this company']] },
  { title: 'Developers', items: [['API keys', '/settings/api', 'Connect websites, Zapier and other systems']] },
  { title: 'Security', items: [['My activity', '/settings/activity', 'Your own actions and changes']] },
]
</script>

<template>
  <div class="settings">
    <div class="layout">
      <nav class="snav" aria-label="Settings sections">
        <template v-for="g in GROUPS" :key="g.title">
          <div class="grp">{{ g.title }}</div>
          <router-link v-for="[label, to, hint] in g.items" :key="to" :to="to" class="item" active-class="on" :title="hint">{{ label }}</router-link>
        </template>
      </nav>
      <section class="pane"><router-view /></section>
    </div>
  </div>
</template>

<style scoped>
.layout { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 28px; align-items: start; }
.snav { position: sticky; top: 16px; background: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .1); padding: 10px 0; }
.grp { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9ca3af; padding: 12px 20px 4px; }
.item { display: block; padding: 9px 20px; color: #374151; text-decoration: none; font-size: 14px; border-left: 3px solid transparent; }
.item:hover { background: #f4f5fb; }
.item.on { color: #3f51b5; background: #eef0fb; border-left-color: #3f51b5; font-weight: 600; }
.pane { min-width: 0; }
@media (max-width: 860px) {
  .layout { grid-template-columns: 1fr; }
  .snav { position: static; display: flex; flex-wrap: wrap; padding: 6px; }
  .grp { display: none; }
  .item { border-left: 0; border-radius: 4px; padding: 8px 12px; }
}
</style>
