<template>
  <div class="page">
    <div class="header">
      <h1>{{ t('offline.status') }}</h1>
      <span class="badge" :class="offline.isOffline ? 'bg-red' : 'bg-green'">
        {{ offline.isOffline ? t('offline.status') : t('offline.online') }}
      </span>
      <span class="last-checked" v-if="offline.networkMonitor?.lastChecked">
        Last checked: {{ formatTime(offline.networkMonitor.lastChecked) }}
      </span>
    </div>

    <div class="actions">
      <button @click="sync" class="btn btn-primary" :disabled="offline.isSyncing || offline.isOffline">
        <span v-if="offline.isSyncing" class="spinner">↻</span>
        {{ offline.isSyncing ? t('offline.syncing') : t('offline.syncNow') }}
      </button>
      <button @click="clearSynced" class="btn btn-secondary">Clear Synced Sales</button>
    </div>

    <div class="card my-4">
      <h2>Local Pending Sales ({{ offline.localPendingCount }})</h2>
      <ul class="sale-list" v-if="pendingSales.length">
        <li v-for="sale in pendingSales" :key="sale.localId" class="sale-item">
          <div class="sale-info">
            <strong>{{ formatTime(sale.createdAt) }}</strong> — 
            {{ sale.saleData.payment_method.toUpperCase() }}
          </div>
          <div class="sale-status">
            <span class="status-badge" :class="'status-' + sale.status">{{ sale.status }}</span>
          </div>
        </li>
      </ul>
      <p v-else class="empty">{{ t('offline.queueEmpty') }}</p>
    </div>

    <div class="card my-4">
      <h2>Recent Sync History</h2>
      <ul class="history-list" v-if="syncHistory.length">
        <li v-for="log in syncHistory" :key="log.id" class="history-item">
          {{ formatTime(log.syncedAt) }} - Status: {{ log.status }} - Processed: {{ log.processed }}, Failed: {{ log.failed }}
        </li>
      </ul>
      <p v-else class="empty">No sync history</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useOfflineStore } from '../stores/offline';
import LocalDb from '../services/localDb';

const { t } = useI18n();
const offline = useOfflineStore();
const pendingSales = ref([]);
const syncHistory = ref([]);
let intervalTimer = null;

const loadData = async () => {
  const allSales = await LocalDb.getPendingSales('pending');
  const failedSales = await LocalDb.getPendingSales('failed');
  pendingSales.value = [...allSales, ...failedSales].sort((a,b) => new Date(b.createdAt) - new Date(a.createdAt));
  
  syncHistory.value = await LocalDb.getRecentSyncs(10);
};

const sync = async () => {
  await offline.syncNow();
  await loadData();
};

const clearSynced = async () => {
  await LocalDb.clearSyncedSales();
  await loadData();
};

const formatTime = (val) => {
  if (!val) return '';
  const d = new Date(val);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

onMounted(async () => {
  await loadData();
  intervalTimer = setInterval(loadData, 5000);
});

onUnmounted(() => {
  if (intervalTimer) clearInterval(intervalTimer);
});
</script>

<style scoped>
.page { padding: 24px; max-width: 800px; margin: 0 auto; }
.header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.badge { padding: 6px 12px; border-radius: 6px; color: white; font-weight: bold; }
.bg-red { background: #dc2626; }
.bg-green { background: #16a34a; }
.last-checked { color: #666; font-size: 0.9em; }

.actions { display: flex; gap: 12px; margin-bottom: 24px; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
.btn-primary { background: var(--accent); color: white; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: #e5e7eb; color: #374151; }
.spinner { display: inline-block; animation: spin 1s linear infinite; margin-right: 8px; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.card { background: var(--card); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
h2 { margin-top: 0; margin-bottom: 16px; font-size: 1.25rem; }

.sale-list, .history-list { list-style: none; padding: 0; margin: 0; }
.sale-item, .history-item { display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid var(--border); }
.sale-item:last-child, .history-item:last-child { border-bottom: none; }
.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; text-transform: uppercase; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-synced { background: #dcfce3; color: #16a34a; }
.status-failed { background: #fee2e2; color: #dc2626; }
.empty { color: #6b7280; font-style: italic; }
</style>
