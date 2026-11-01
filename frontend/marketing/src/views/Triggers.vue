<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import Icon from '../components/Icon.vue'

const triggers = ref([])
const ICONS = { contact_created: 'people', list_joined: 'list', tag_added: 'coupon', form_submitted: 'template' }

onMounted(async () => { triggers.value = (await api.get('/automation/triggers')).data })
</script>

<template>
  <div class="head"><h1>Triggers</h1></div>
  <p class="intro">A trigger is the event that starts a workflow. Pick one below to build a workflow around it, or see which workflows already listen for it.</p>

  <div class="grid g2">
    <div v-for="t in triggers" :key="t.type" class="card trig">
      <div class="row" style="align-items: flex-start; flex-wrap: nowrap">
        <span class="ico"><Icon :name="ICONS[t.type]" :size="24" /></span>
        <div style="flex: 1">
          <h2 style="margin: 0 0 4px">{{ t.label }}</h2>
          <p class="d">{{ t.description }}</p>
        </div>
      </div>
      <div class="used">
        <small v-if="!t.workflows.length">No workflows use this trigger yet.</small>
        <template v-else>
          <small>Used by:</small>
          <router-link v-for="w in t.workflows" :key="w.id" to="/workflows" class="pill">{{ w.name }} <span class="badge" :class="w.status === 'active' ? 'sent' : ''">{{ w.status }}</span></router-link>
        </template>
      </div>
      <router-link :to="`/workflows?trigger=${t.type}`" class="btn sm">Create workflow with this trigger</router-link>
    </div>
  </div>
</template>

<style scoped>
.intro { color: #6b7280; margin: -8px 0 18px; max-width: 760px; }
.ico { width: 46px; height: 46px; border-radius: 50%; background: #e8eaf6; color: #3f51b5; display: grid; place-items: center; flex: none; margin-right: 14px; }
.d { margin: 0 0 12px; color: #555; font-size: 14px; }
.used { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 14px; min-height: 26px; }
.pill { background: #f4f5f7; border-radius: 99px; padding: 3px 10px; font-size: 13px; color: #333; text-decoration: none; }
</style>
