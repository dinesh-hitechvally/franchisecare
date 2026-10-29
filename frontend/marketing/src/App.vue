<script setup>
import { computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from './stores/auth'
import Icon from './components/Icon.vue'

const auth = useAuth()
const route = useRoute()
const router = useRouter()
const isGuest = computed(() => route.meta.guest)
const open = reactive({})

// groups with `children` expand in place, like the main app's sidebar
const nav = [
  { label: 'Dashboard', icon: 'dashboard', to: '/dashboard' },
  { label: 'Campaigns', icon: 'campaign', children: [['All campaigns', '/campaigns'], ['New campaign', '/campaigns/new']] },
  { label: 'Templates', icon: 'template', children: [['Email templates', '/templates?tab=email'], ['SMS templates', '/templates?tab=sms']] },
  { label: 'Contacts', icon: 'people', children: [['All contacts', '/contacts'], ['Add contact', '/contacts?new=1']] },
  { label: 'Lists', icon: 'list', to: '/lists' },
  { label: 'Logs', icon: 'logs', children: [['All messages', '/logs'], ['Email logs', '/logs?channel=email'], ['SMS logs', '/logs?channel=sms']] },
  { label: 'Settings', icon: 'settings', to: '/settings' },
]

const top = [
  ['Campaigns', 'campaign', '/campaigns'],
  ['New Campaign', 'add', '/campaigns/new'],
  ['Templates', 'template', '/templates'],
  ['List Contacts', 'people', '/contacts'],
  ['Add Contact', 'add', '/contacts?new=1'],
]

const groupActive = (g) => g.children.some(([, to]) => route.fullPath.split('?')[0] === to.split('?')[0])
const isOpen = (g) => open[g.label] ?? groupActive(g)

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
        <div class="avatar">{{ (auth.business?.name || 'M')[0] }}</div>
        <div>
          <div class="u-name">{{ auth.business?.name }}</div>
          <small>{{ auth.user?.email }}</small>
        </div>
      </div>

      <div class="side-title">MAIN</div>
      <nav>
        <template v-for="item in nav" :key="item.label">
          <router-link v-if="item.to" :to="item.to" class="nav-item" active-class="active">
            <Icon :name="item.icon" :size="18" /><span>{{ item.label }}</span>
          </router-link>
          <template v-else>
            <a href="#" class="nav-item" :class="{ active: groupActive(item) }" @click.prevent="open[item.label] = !isOpen(item)">
              <Icon :name="item.icon" :size="18" /><span>{{ item.label }}</span>
              <Icon name="chevron" :size="18" class="chev" :class="{ rot: isOpen(item) }" />
            </a>
            <div v-show="isOpen(item)" class="sub">
              <router-link v-for="[label, to] in item.children" :key="to" :to="to" class="sub-item" :class="{ on: route.fullPath === to }">{{ label }}</router-link>
            </div>
          </template>
        </template>
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
  </div>
</template>

<style scoped>
.shell { display: flex; min-height: 100vh; background: #f4f5f7; }
.side { width: 250px; flex: none; background: #212121; color: #fff; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; border-radius: 0 0 0 12px; }
.side-user { display: flex; gap: 12px; align-items: center; padding: 22px 20px 18px; background: #1a1a1a; }
.avatar { width: 38px; height: 38px; border-radius: 50%; background: #3f51b5; display: grid; place-items: center; font-weight: 700; text-transform: uppercase; flex: none; }
.u-name { font-size: 14px; color: #e0e0e0; } .side-user small { color: #9e9e9e; font-size: 12px; word-break: break-all; }
.side-title { font-size: 11px; letter-spacing: .06em; color: #e0e0e0; padding: 26px 24px 10px; font-weight: 500; }
nav { flex: 1; padding-bottom: 12px; }
.nav-item { display: flex; align-items: center; gap: 16px; padding: 11px 24px; color: #fff; font-weight: 700; font-size: 14px; text-decoration: none; }
.nav-item:hover { background: #2b2b2b; } .nav-item.active { background: #171717; }
.chev { margin-left: auto; opacity: .8; transition: transform .15s; } .chev.rot { transform: rotate(90deg); }
.sub { background: #1a1a1a; padding: 4px 0; }
.sub-item { display: block; padding: 8px 24px 8px 58px; color: #bdbdbd; font-size: 13px; text-decoration: none; }
.sub-item:hover, .sub-item.on { color: #fff; }
.side-foot { padding: 0 14px 14px; }
.side-cta { display: flex; align-items: center; justify-content: center; background: #3f51b5; color: #fff; padding: 11px; border-radius: 6px; font-weight: 700; font-size: 14px; text-decoration: none; }
.side-cta:hover { background: #3949ab; }

.content { flex: 1; min-width: 0; }
.topbar { background: #3f51b5; color: #fff; min-height: 70px; padding-top: 8px; padding-bottom: 8px; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; gap: 16px; }
.tb-brand { font-size: 18px; font-weight: 400; white-space: nowrap; } .tb-brand b { font-weight: 700; } .tb-brand small { color: #c5cae9; margin-left: 14px; font-size: 13px; }
.tb-menu { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.tb-btn { display: inline-flex; align-items: center; gap: 6px; background: rgba(0, 0, 0, .18); color: #fff; border: 0; border-radius: 4px; padding: 7px 11px; font-size: 13px; cursor: pointer; white-space: nowrap; text-decoration: none; font-family: inherit; }
.tb-btn:hover { background: rgba(0, 0, 0, .3); }
.tb-ico { background: none; border: 0; color: #fff; cursor: pointer; padding: 4px; display: flex; }
.main { padding: 30px; }

@media (max-width: 980px) {
  .shell { flex-direction: column; } .side { width: 100%; height: auto; position: static; border-radius: 0; }
  .topbar { height: auto; padding: 12px 16px; flex-wrap: wrap; } .main { padding: 16px; }
}
</style>
