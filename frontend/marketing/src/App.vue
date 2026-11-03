<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from './stores/auth'
import { useUi } from './stores/ui'
import Icon from './components/Icon.vue'

const auth = useAuth()
const ui = useUi()
const route = useRoute()
const router = useRouter()
const isGuest = computed(() => route.meta.guest)
const open = reactive({})
const logoBroken = ref(false) // a logo address that doesn't load falls back to the letter
watch(() => auth.company?.logo_url, () => (logoBroken.value = false))

// Sidebar sections. Every user of a company has full access, so nothing is hidden.
const sections = [
  {
    title: 'Audience', icon: 'people',
    items: [['People', '/people'], ['Lists', '/lists'], ['Tags', '/tags'], ['Segments', '/segments'], ['Subscriptions', '/subscriptions'], ['Custom Fields', '/custom-fields']],
  },
  {
    title: 'Campaigns', icon: 'campaign',
    items: [['Campaigns', '/campaigns'], ['Reports', '/reports']],
  },
  {
    title: 'Content', icon: 'template',
    items: [['Forms', '/forms'], ['Pages', '/pages'], ['Templates', '/templates']],
  },
  {
    title: 'Automation', icon: 'bolt',
    items: [['Workflows', '/workflows'], ['Triggers', '/automation/triggers'], ['Webhooks', '/automation/webhooks'], ['Automation History', '/automation/history']],
  },
  {
    title: 'Analytics', icon: 'chart',
    items: [['Overview', '/analytics/overview'], ['Email', '/analytics/email'], ['SMS', '/analytics/sms'], ['Delivery & errors', '/analytics/delivery'], ['Message logs', '/message-logs'], ['People', '/analytics/people'], ['Forms & pages', '/analytics/conversions'], ['Automation', '/analytics/automation']],
  },
]

const top = [
  ['Campaigns', 'campaign', '/campaigns'],
  ['New Campaign', 'add', '/campaigns/new'],
  ['Templates', 'template', '/templates'],
  ['People', 'people', '/people'],
  ['Add Person', 'add', '/people?new=1'],
]

// An item is active when the path matches and every query key it names matches too;
// a plain link (no query) is only active when none of the filter keys are set.
function active(to) {
  const [path, q] = to.split('?')
  if (route.path !== path && route.meta.parent !== path) return false // a sub-screen (Page histories) keeps its parent's menu item lit
  const want = Object.fromEntries(new URLSearchParams(q || ''))
  if (!q) return !['type', 'status', 'tab', 'channel'].some((k) => route.query[k])
  return Object.entries(want).every(([k, v]) => route.query[k] === v)
}
const sectionActive = (s) => s.items.some(([, to]) => active(to))
const isOpen = (s) => open[s.title] ?? sectionActive(s)
// accordion: opening a section closes the others
function toggle(s) {
  const wasOpen = isOpen(s)
  sections.forEach((x) => (open[x.title] = false))
  open[s.title] = !wasOpen
}
// moving to another page: the section that holds it opens, the rest close
watch(() => route.fullPath, () => Object.keys(open).forEach((k) => delete open[k]))

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <router-view v-if="isGuest" />
  <div v-else class="shell">
    <aside class="side">
      <div class="side-user">
        <img v-if="auth.company?.logo_url && !logoBroken" :src="auth.company.logo_url" class="avatar logo" alt="" referrerpolicy="no-referrer" @error="logoBroken = true" />
        <div v-else class="avatar">{{ (auth.company?.name || 'M')[0] }}</div>
        <div>
          <div class="u-name">{{ auth.company?.name }}</div>
          <small>{{ auth.user?.email }}</small>
        </div>
      </div>

      <div class="side-title">MAIN</div>
      <nav>
        <router-link to="/dashboard" class="nav-item" active-class="active"><Icon name="dashboard" :size="18" /><span>Dashboard</span></router-link>
        <router-link to="/media" class="nav-item" active-class="active"><Icon name="image" :size="18" /><span>Media</span></router-link>

        <template v-for="s in sections" :key="s.title">
          <a href="#" class="nav-item" :class="{ active: sectionActive(s) }" @click.prevent="toggle(s)">
            <Icon :name="s.icon" :size="18" /><span>{{ s.title }}</span>
            <Icon name="chevron" :size="18" class="chev" :class="{ rot: isOpen(s) }" />
          </a>
          <div v-show="isOpen(s)" class="sub">
            <router-link v-for="[label, to] in s.items" :key="to" :to="to" class="sub-item" :class="{ on: active(to) }">{{ label }}</router-link>
          </div>
        </template>

        <router-link to="/settings" class="nav-item" :class="{ active: route.path.startsWith('/settings') }"><Icon name="settings" :size="18" /><span>Settings</span></router-link>
      </nav>

      <div class="side-foot">
        <router-link to="/campaigns/new" class="side-cta"><Icon name="campaign" :size="18" />&nbsp; New Campaign</router-link>
      </div>
    </aside>

    <div class="content">
      <header class="topbar">
        <div class="tb-brand">FranchiseCare <b>Marketing</b><small>Ver: 1.0.0</small></div>
        <div class="tb-menu">
          <router-link v-for="[label, icon, to] in top" :key="label" :to="to" class="tb-btn"><Icon :name="icon" :size="16" />{{ label }}</router-link>
          <button class="tb-ico" title="Notifications"><Icon name="bell" :size="20" /></button>
          <button class="tb-ico" title="Reload" @click="router.go(0)"><Icon name="refresh" :size="20" /></button>
          <button class="tb-btn" @click="logout">Log out</button>
        </div>
      </header>
      <main class="main"><router-view :key="route.fullPath" /></main>
    </div>

    <transition name="fade">
      <div v-if="ui.toast" class="toast" :class="ui.toast.kind" @click="ui.toast = null">{{ ui.toast.text }}</div>
    </transition>
  </div>
</template>

<style scoped>
.shell { display: flex; min-height: 100vh; background: #f4f5f7; }
.side { width: 250px; flex: none; background: #212121; color: #fff; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; border-radius: 0 0 0 12px; }
.side-user { display: flex; gap: 12px; align-items: center; padding: 22px 20px 18px; background: #1a1a1a; }
.avatar.logo { object-fit: cover; background: #fff; }
.avatar { width: 38px; height: 38px; border-radius: 50%; background: #3f51b5; display: grid; place-items: center; font-weight: 700; text-transform: uppercase; flex: none; }
.u-name { font-size: 14px; color: #e0e0e0; } .side-user small { color: #9e9e9e; font-size: 12px; word-break: break-all; display: block; }
.side-title { font-size: 11px; letter-spacing: .06em; color: #e0e0e0; padding: 22px 24px 8px; font-weight: 500; }
nav { flex: 1; padding-bottom: 12px; }
.nav-item { display: flex; align-items: center; gap: 16px; padding: 11px 24px; color: #fff; font-weight: 700; font-size: 14px; text-decoration: none; }
.nav-item:hover { background: #2b2b2b; } .nav-item.active { background: #171717; }
.chev { margin-left: auto; opacity: .8; transition: transform .15s; } .chev.rot { transform: rotate(90deg); }
.sub { background: #1a1a1a; padding: 4px 0; }
.sub-item { display: block; padding: 8px 24px 8px 58px; color: #bdbdbd; font-size: 13px; text-decoration: none; }
.sub-item:hover, .sub-item.on { color: #fff; } .sub-item.on { font-weight: 700; box-shadow: inset 3px 0 0 #7986cb; }
.side-foot { padding: 0 14px 14px; }
.side-cta { display: flex; align-items: center; justify-content: center; background: #3f51b5; color: #fff; padding: 11px; border-radius: 6px; font-weight: 700; font-size: 14px; text-decoration: none; }
.side-cta:hover { background: #3949ab; }

.content { flex: 1; min-width: 0; }
.topbar { background: #3f51b5; color: #fff; min-height: 70px; padding-top: 8px; padding-bottom: 8px; display: flex; align-items: center; justify-content: space-between; padding-left: 28px; padding-right: 28px; gap: 16px; }
.tb-brand { font-size: 18px; font-weight: 400; white-space: nowrap; } .tb-brand b { font-weight: 700; } .tb-brand small { color: #c5cae9; margin-left: 14px; font-size: 13px; }
.tb-menu { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.tb-btn { display: inline-flex; align-items: center; gap: 6px; background: rgba(0, 0, 0, .18); color: #fff; border: 0; border-radius: 4px; padding: 7px 11px; font-size: 13px; cursor: pointer; white-space: nowrap; text-decoration: none; font-family: inherit; }
.tb-btn:hover { background: rgba(0, 0, 0, .3); }
.tb-ico { background: none; border: 0; color: #fff; cursor: pointer; padding: 4px; display: flex; }
.main { padding: 30px; }

.toast { position: fixed; bottom: 24px; right: 24px; z-index: 100; max-width: 360px; padding: 12px 18px; border-radius: 6px; color: #fff; font-size: 14px; box-shadow: 0 4px 16px rgba(0, 0, 0, .3); cursor: pointer; }
.toast.err { background: #c62828; } .toast.ok { background: #2e7d32; }
.fade-enter-active, .fade-leave-active { transition: opacity .25s; } .fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 980px) {
  .shell { flex-direction: column; } .side { width: 100%; height: auto; position: static; border-radius: 0; }
  .topbar { height: auto; padding: 12px 16px; flex-wrap: wrap; } .main { padding: 16px; }
}
</style>
