<template>
  <div class="offline-page">
    <!-- En-tête statut réseau -->
    <div class="offline-header">
      <div class="header-left">
        <h1 class="page-title">{{ t('offline.status') }}</h1>
        <span class="status-badge" :class="offline.isOffline ? 'badge-offline' : 'badge-online'">
          <span class="badge-dot"></span>
          {{ offline.isOffline ? t('offline.status') : t('offline.online') }}
        </span>
      </div>
      <span v-if="offline.lastSync" class="last-checked">
        Dernière synchronisation : {{ formatTime(offline.lastSync) }}
      </span>
    </div>

    <!-- Actions -->
    <div class="actions-bar">
      <button v-if="!offline.isOffline" class="btn btn-success" @click="goToTerminal">
        ✓ Retour au terminal
      </button>
      <button
        class="btn btn-primary"
        :disabled="offline.isSyncing || offline.isOffline"
        @click="sync"
      >
        <span v-if="offline.isSyncing" class="spinner">↻</span>
        {{ offline.isSyncing ? t('offline.syncing') : t('offline.syncNow') }}
      </button>
      <button class="btn btn-secondary" @click="clearSynced">
        Effacer les ventes synchronisées
      </button>
    </div>

    <!-- Ventes en attente -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Ventes en attente (hors ligne)</h2>
        <span class="count-badge">{{ offline.localPendingCount }}</span>
      </div>

      <ul v-if="pendingSales.length" class="sale-list">
        <li v-for="sale in pendingSales" :key="sale.uuid" class="sale-item">
          <div class="sale-info">
            <strong>{{ formatTime(sale.createdAt) }}</strong>
            <span class="method-tag">{{ (sale.payment_method || '—').toUpperCase() }}</span>
          </div>
          <span class="status-pill" :class="'status-' + sale.status">{{ translateStatus(sale.status) }}</span>
        </li>
      </ul>
      <p v-else class="empty-msg">{{ t('offline.queueEmpty') }}</p>
    </div>

    <!-- Historique de synchronisation -->
    <div class="card">
      <h2 class="card-title">Historique des synchronisations</h2>

      <ul v-if="syncHistory.length" class="history-list">
        <li v-for="log in syncHistory" :key="log.id" class="history-item">
          <span class="history-time">{{ formatTime(log.syncedAt) }}</span>
          <span class="history-detail">
            Statut : <strong>{{ translateStatus(log.status) }}</strong>
            &nbsp;· Traités : <strong>{{ log.processed }}</strong>
            &nbsp;· Échoués : <strong>{{ log.failed }}</strong>
          </span>
        </li>
      </ul>
      <p v-else class="empty-msg">Aucun historique de synchronisation</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useOfflineStore } from '../stores/offline';
import LocalDb from '../services/localDb';

const { t } = useI18n();
const offline = useOfflineStore();
const router = useRouter();
const goToTerminal = () => router.push('/terminal');
watch(() => offline.isOffline, async (isOffline) => {
  if (!isOffline) {
    await offline.syncNow();
    await loadData();
    setTimeout(() => router.push('/terminal'), 1500);
  }
});
const pendingSales = ref([]);
const syncHistory = ref([]);
let intervalTimer = null;

const statusLabels = {
  pending: 'En attente',
  synced:  'Synchronisé',
  failed:  'Échoué',
};
const translateStatus = (s) => statusLabels[s] || s;

const loadData = async () => {
  const allSales    = await LocalDb.getPendingSales('pending');
  const failedSales = await LocalDb.getPendingSales('failed');
  pendingSales.value = [...allSales, ...failedSales]
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
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
  if (!val) return '—';
  return new Date(val).toLocaleTimeString('fr-FR', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  });
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
.offline-page {
  padding: 24px;
  max-width: 840px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
  overflow-y: auto;
  height: 100%;
}

/* ── En-tête ──────────────────────────────────────────────── */
.offline-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.page-title {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--on-surface);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.badge-online  { background: rgba(52, 211, 153, 0.14); color: var(--success); }
.badge-online .badge-dot { background: var(--success); box-shadow: 0 0 6px rgba(52,211,153,0.6); }
.badge-offline { background: rgba(255, 107, 107, 0.14); color: var(--danger); }
.badge-offline .badge-dot { background: var(--danger); box-shadow: 0 0 6px rgba(255,107,107,0.6); }

.last-checked {
  font-size: 12px;
  color: var(--on-surface-faint);
}

/* ── Actions ─────────────────────────────────────────────── */
.actions-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.btn {
  height: 44px;
  padding: 0 22px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: opacity 0.15s;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-dim));
  color: var(--on-primary);
}

.btn-primary:disabled { opacity: 0.4; cursor: not-allowed; }

.btn-secondary {
  background: var(--surface-high);
  color: var(--on-surface-muted);
  border: 1px solid var(--border);
}

.btn-secondary:hover { border-color: var(--primary); color: var(--on-surface); }
.btn-success {
  background: linear-gradient(135deg, #34d399, #059669);
  color: #fff;
  animation: pulse-green 1.5s infinite;
}
@keyframes pulse-green {
  0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0.4); }
  50%       { box-shadow: 0 0 0 8px rgba(52,211,153,0); }
}

/* ── Cartes ───────────────────────────────────────────────── */
.card {
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 14px;
  padding: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.card-title {
  margin: 0 0 16px;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--on-surface-muted);
}

.card-header .card-title { margin: 0; }

.count-badge {
  background: rgba(237, 95, 30, 0.15);
  color: var(--primary);
  border-radius: 999px;
  padding: 3px 12px;
  font-size: 13px;
  font-weight: 800;
}

/* ── Liste ventes ─────────────────────────────────────────── */
.sale-list,
.history-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
}

.sale-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.sale-item:last-child { border-bottom: none; }

.sale-info {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  color: var(--on-surface);
}

.method-tag {
  font-size: 11px;
  font-weight: 700;
  background: var(--surface-high);
  color: var(--on-surface-muted);
  border-radius: 6px;
  padding: 2px 8px;
  letter-spacing: 0.06em;
}

.status-pill {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.status-pending  { background: rgba(255,184,0,0.14); color: var(--warning); }
.status-synced   { background: rgba(52,211,153,0.14); color: var(--success); }
.status-failed   { background: rgba(255,107,107,0.14); color: var(--danger); }

/* ── Historique ───────────────────────────────────────────── */
.history-item {
  display: flex;
  align-items: baseline;
  gap: 14px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
  font-size: 13px;
  color: var(--on-surface);
}

.history-item:last-child { border-bottom: none; }

.history-time {
  color: var(--on-surface-muted);
  white-space: nowrap;
  font-weight: 600;
}

.history-detail { flex: 1; }
.history-detail strong { color: var(--on-surface); }

/* ── Vide ─────────────────────────────────────────────────── */
.empty-msg {
  color: var(--on-surface-faint);
  font-style: italic;
  font-size: 14px;
  margin: 0;
  padding: 8px 0;
}

/* ── Spinner ──────────────────────────────────────────────── */
.spinner {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
