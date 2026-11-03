<script setup>
import { fmtDateTime, fmtStored } from '../format'
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import Icon from '../components/Icon.vue'

const d = ref(null)
onMounted(async () => { d.value = (await api.get('/dashboard')).data })

const bars = computed(() => {
  const max = Math.max(1, ...(d.value?.daily || [0]))
  return (d.value?.daily || []).map((n) => Math.max(8, Math.round((n / max) * 100)))
})
const fmt = (s) => fmtDateTime(s)
const ago = (s) => fmtDateTime(s)
</script>

<template>
  <p v-if="!d" class="empty">Loading…</p>
  <div v-else class="dash">
    <div class="col-left">
      <div class="card-p">
        <h3>Recent Campaigns</h3>
        <p v-if="!d.recent_campaigns.length" class="muted pad">No campaigns yet.</p>
        <router-link v-for="c in d.recent_campaigns" :key="c.id" :to="`/campaigns/${c.id}`" class="feed">
          <span class="ico" :class="c.type"><Icon :name="c.type === 'email' ? 'email' : 'sms'" :size="22" /></span>
          <span class="txt"><b>{{ c.name }}</b> <span class="badge" :class="c.status">{{ c.status }}</span><small>{{ ago(c.created_at) }}</small></span>
          <Icon name="chevron" :size="22" class="go" />
        </router-link>
      </div>
      <div class="card-p">
        <h3>Recent Activity</h3>
        <p v-if="!d.recent_logs.length" class="muted pad">No messages sent yet.</p>
        <router-link v-for="l in d.recent_logs" :key="l.channel + l.id" to="/message-logs" class="feed">
          <span class="ico" :class="l.channel"><Icon :name="l.channel === 'email' ? 'email' : 'sms'" :size="22" /></span>
          <span class="txt"><b>{{ l.recipient }}</b> <span class="badge" :class="l.status">{{ l.status === 'logged' ? 'logged only' : l.status }}</span><small>{{ ago(l.created_at) }}</small></span>
          <Icon name="chevron" :size="22" class="go" />
        </router-link>
      </div>
    </div>

    <div class="tiles">
      <div class="card-p attention">
        <div>
          <small class="up">Attention</small>
          <div class="big">{{ d.drafts }}</div>
          <div class="muted">Draft campaigns waiting to be sent</div>
        </div>
        <div class="chart" title="Messages sent, last 14 days"><i v-for="(h, i) in bars" :key="i" :style="{ height: h + '%' }"></i></div>
      </div>
      <div class="card-p diary">
        <Icon name="schedule" :size="46" />
        <div><b>Scheduled</b><small>{{ d.scheduled }} campaign(s) queued</small></div>
      </div>
      <router-link to="/forms" class="tile green" style="padding: 18px 24px"><b>Grow your list</b><small>Forms &amp; pages</small></router-link>

      <div class="tile indigo"><Icon name="people" :size="42" /><div><b>{{ d.contacts }}</b>Contacts</div></div>
      <div class="tile pink"><Icon name="campaign" :size="42" /><div><b>{{ d.campaigns }}</b>Campaigns</div></div>
      <div class="tile cyan"><Icon name="email" :size="42" /><div><b>{{ d.email.sent }}</b>Emails Sent</div></div>
      <div class="tile salmon"><Icon name="sms" :size="42" /><div><b>{{ d.sms.sent }}</b>SMS Sent</div></div>

      <div class="tile green"><Icon name="eye" :size="42" /><div><b>{{ d.email.open_rate }}%</b>Email Open Rate</div></div>
      <div class="tile dark"><Icon name="click" :size="42" /><div><b>{{ d.email.clicked }}</b>Emails Clicked</div></div>
      <div class="tile purple"><Icon name="error" :size="42" /><div><b>{{ d.email.failed + d.sms.failed }}</b>Failed Messages</div></div>
      <div class="tile orange"><Icon name="unsub" :size="42" /><div><b>{{ d.unsubscribed }}</b>Unsubscribed</div></div>

      <router-link to="/campaigns/new" class="tile black big-link"><Icon name="add" :size="34" /><div><b>New Campaign</b>Email or SMS</div></router-link>
      <router-link to="/templates" class="tile teal big-link"><Icon name="template" :size="34" /><div><b>Templates</b>{{ d.templates }} saved</div></router-link>

      <div class="card-p sched">
        <div class="sched-head"><h3>Scheduled Campaigns</h3><span class="tab">UPCOMING</span></div>
        <table>
          <thead><tr><th>Campaign</th><th>Channel</th><th>Audience</th><th>Send time</th><th></th></tr></thead>
          <tr v-for="c in d.upcoming" :key="c.id">
            <td>{{ c.name }}</td>
            <td><span class="badge" :class="c.type">{{ c.type }}</span></td>
            <td>{{ c.list?.name || 'All contacts' }}</td>
            <td>{{ fmtStored(c.scheduled_at) }} UTC</td>
            <td><router-link :to="`/campaigns/${c.id}`">View</router-link></td>
          </tr>
          <tr v-if="!d.upcoming.length"><td colspan="5" class="empty">Nothing scheduled.</td></tr>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dash { display: grid; grid-template-columns: 500px 1fr; gap: 30px; align-items: start; }
.col-left { display: flex; flex-direction: column; gap: 30px; }
.card-p { background: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .12); padding: 30px 30px 14px; }
h3 { font-size: 18px; font-weight: 400; margin: 0 0 18px; color: #212121; }
.muted { color: #6b6b6b; font-size: 14px; } .pad { padding: 20px 0; }
.feed { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-top: 1px solid #e0e0e0; text-decoration: none; color: #212121; font-size: 14px; }
.ico { width: 50px; height: 50px; border-radius: 50%; display: grid; place-items: center; flex: none; background: #e8eaf6; color: #3f51b5; }
.ico.sms { background: #e3f2fd; color: #1565c0; }
.txt { flex: 1; line-height: 1.4; } .txt b { font-weight: 500; color: #3f51b5; } .txt small { display: block; color: #6b6b6b; font-size: 12px; margin-top: 4px; }
.go { color: #757575; }

.tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px 30px; }
.attention { grid-column: span 2; display: flex; justify-content: space-between; align-items: center; padding: 24px 30px; }
.up { text-transform: uppercase; font-size: 14px; color: #424242; letter-spacing: .02em; } .big { font-size: 28px; font-weight: 700; margin: 4px 0; }
.chart { display: flex; align-items: flex-end; gap: 7px; height: 70px; } .chart i { width: 4px; background: #4caf50; border-radius: 1px; }
.diary { display: flex; align-items: center; gap: 14px; padding: 24px 20px; } .diary b { display: block; font-size: 16px; font-weight: 500; } .diary small { color: #9e9e9e; }
.tile { border-radius: 4px; color: #fff; padding: 22px 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; box-shadow: 0 2px 6px rgba(0, 0, 0, .15); font-size: 16px; min-height: 97px; }
.tile b { display: block; font-size: 18px; font-weight: 500; } .tile small { display: block; font-size: 12px; margin-top: 2px; }
.tile.green { flex-direction: column; align-items: flex-start; justify-content: center; }
.tile > div b { font-size: 18px; }
.indigo { background: #3f51b5; } .pink { background: #e91e63; } .cyan { background: #00bcd4; } .salmon { background: #ff9e9e; }
.green { background: #4caf50; } .dark { background: #343a40; } .purple { background: #9c27b0; } .orange { background: #ff9800; }
.black { background: #000; } .teal { background: #009688; }
.big-link { grid-column: span 2; min-height: 110px; } .big-link b { font-size: 20px; font-weight: 700; }
.sched { grid-column: 1 / -1; padding: 30px; }
.sched-head { display: flex; justify-content: space-between; border-bottom: 1px solid #e0e0e0; margin-bottom: 8px; }
.sched-head h3 { margin: 0 0 14px; } .tab { color: #3f51b5; font-size: 14px; padding: 4px 14px 14px; border-bottom: 2px solid #e91e63; margin-bottom: -1px; letter-spacing: .03em; }
.sched table th { color: #212121; font-weight: 700; font-size: 14px; text-transform: none; letter-spacing: 0; text-align: left; padding: 14px 8px; }
.sched td { padding: 16px 8px; font-size: 14px; }

@media (max-width: 1250px) { .dash { grid-template-columns: 1fr; } }
@media (max-width: 700px) { .tiles { grid-template-columns: 1fr 1fr; gap: 14px; } .attention, .big-link { grid-column: 1 / -1; } }
</style>
