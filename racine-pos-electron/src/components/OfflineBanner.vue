<template>
  <div class="offline-banner" v-if="offline.isOffline || offline.localPendingCount > 0">
    <div class="status-indicator">
      <span class="badge" :class="{ 'bg-red': offline.isOffline, 'bg-green': !offline.isOffline }">
        {{ offline.isOffline ? t('offline.status') : t('offline.online') }}
      </span>
      <span class="pending-text" v-if="offline.localPendingCount > 0">
        {{ t('offline.pending', { count: offline.localPendingCount }) }}
      </span>
      <span class="last-sync" v-if="offline.lastSync">
        {{ t('offline.lastSync', { time: formatTime(offline.lastSync) }) }}
      </span>
    </div>
    
    <div class="actions">
      <button 
        v-if="offline.localPendingCount > 0 && !offline.isOffline" 
        @click="sync" 
        :disabled="offline.isSyncing"
        class="sync-btn"
      >
        <span v-if="offline.isSyncing" class="spinner">↻</span>
        {{ offline.isSyncing ? t('offline.syncing') : t('offline.syncNow') }}
      </button>
      <span v-if="showSuccess" class="success-flash">✓</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useOfflineStore } from '../stores/offline';
import { useI18n } from 'vue-i18n';

const offline = useOfflineStore();
const { t } = useI18n();
const showSuccess = ref(false);

let timer = null;

const sync = async () => {
  await offline.syncNow();
  if (offline.lastSyncResult && offline.lastSyncResult.failed === 0) {
    showSuccess.value = true;
    setTimeout(() => { showSuccess.value = false; }, 3000);
  }
};

const formatTime = (isoString) => {
  if (!isoString) return '';
  const d = new Date(isoString);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  offline.checkStatus();
  timer = setInterval(() => offline.checkStatus(), 30000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>

<style scoped>
.offline-banner {
  background: #f59e0b;
  color: #111;
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 500;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.status-indicator {
  display: flex;
  align-items: center;
  gap: 16px;
}
.badge {
  padding: 4px 8px;
  border-radius: 4px;
  color: white;
  font-weight: bold;
  text-transform: uppercase;
  font-size: 0.85em;
  letter-spacing: 0.05em;
}
.bg-red { background: #dc2626; }
.bg-green { background: #16a34a; }
.pending-text { font-size: 0.95em; }
.last-sync { font-size: 0.85em; opacity: 0.8; }
.actions { display: flex; align-items: center; gap: 12px; }
.sync-btn {
  background: #111;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  transition: opacity 0.2s;
}
.sync-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.spinner { display: inline-block; animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
.success-flash { color: #16a34a; font-weight: bold; font-size: 1.2em; }
</style>
