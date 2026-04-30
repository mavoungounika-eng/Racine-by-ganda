<script setup>
/**
 * AdminStockAlerts — Dashboard admin : alertes stock temps réel
 *
 * Écoute le canal private-admin.stock via useStockSync.
 * Toast pour chaque nouvelle alerte + liste déroulante.
 */
import { ref, computed, onMounted } from 'vue'
import { useStockSync } from '@/composables/useStockSync'

const { isConnected, alerts, watchAdminStock } = useStockSync()

const showPanel = ref(false)
const toasts    = ref([])

const lowAlerts  = computed(() => alerts.value.filter(a => a.type === 'low'))
const anomalies  = computed(() => alerts.value.filter(a => a.type === 'anomaly'))
const totalCount = computed(() => alerts.value.length)

function addToast(message, type = 'warning') {
  const id = Date.now()
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 5000)
}

function dismissAlert(index) {
  alerts.value.splice(index, 1)
}

onMounted(() => {
  watchAdminStock({
    onLowAlert: (data) => {
      addToast(`⚠ Stock bas — ${data.product_name} : ${data.current_stock} restant(s)`, 'warning')
    },
    onAnomaly: (data) => {
      addToast(`🚨 Anomalie stock produit #${data.product_id}`, 'danger')
    },
  })
})
</script>

<template>
  <!-- Badge flottant -->
  <button
    class="alert-badge"
    :class="{ 'has-alerts': totalCount > 0, 'connected': isConnected }"
    :title="isConnected ? 'WebSocket connecté' : 'WebSocket déconnecté'"
    @click="showPanel = !showPanel"
  >
    🔔
    <span v-if="totalCount > 0" class="badge-count">{{ totalCount }}</span>
    <span class="ws-dot" :class="isConnected ? 'ws-on' : 'ws-off'" />
  </button>

  <!-- Panel alertes -->
  <Transition name="slide">
    <div v-if="showPanel" class="alerts-panel">
      <div class="panel-header">
        <h3>Alertes Stock <span class="count-chip">{{ totalCount }}</span></h3>
        <button class="close-btn" @click="showPanel = false">✕</button>
      </div>

      <div v-if="totalCount === 0" class="empty-state">
        ✅ Aucune alerte active
      </div>

      <!-- Anomalies critiques -->
      <div v-if="anomalies.length" class="section">
        <div class="section-title anomaly-title">🚨 Anomalies critiques ({{ anomalies.length }})</div>
        <div
          v-for="(alert, i) in anomalies"
          :key="i"
          class="alert-item anomaly"
        >
          <div class="alert-info">
            <strong>Produit #{{ alert.product_id }}</strong>
            <span class="alert-meta">
              Demandé: {{ alert.requested_qty }} / Disponible: {{ alert.available_stock }}
            </span>
          </div>
          <button class="dismiss" @click="dismissAlert(alerts.indexOf(alert))">✕</button>
        </div>
      </div>

      <!-- Stock bas -->
      <div v-if="lowAlerts.length" class="section">
        <div class="section-title">⚠ Stock bas ({{ lowAlerts.length }})</div>
        <div
          v-for="(alert, i) in lowAlerts"
          :key="i"
          class="alert-item low"
        >
          <div class="alert-info">
            <strong>{{ alert.product_name }}</strong>
            <span class="alert-meta">
              {{ alert.current_stock }} / {{ alert.threshold }} min
            </span>
          </div>
          <button class="dismiss" @click="dismissAlert(alerts.indexOf(alert))">✕</button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Toast notifications -->
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="`toast-${toast.type}`"
        >
          {{ toast.message }}
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.alert-badge {
  position: relative;
  background: none;
  border: none;
  font-size: 1.4rem;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background 0.2s;
}
.alert-badge:hover { background: rgba(0,0,0,0.06); }
.alert-badge.has-alerts { animation: ring 1.5s ease-in-out infinite; }

.badge-count {
  position: absolute;
  top: -2px; right: -2px;
  background: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: 9999px;
  padding: 1px 5px;
  min-width: 16px;
  text-align: center;
}

.ws-dot {
  position: absolute;
  bottom: 2px; right: 2px;
  width: 7px; height: 7px;
  border-radius: 50%;
}
.ws-on  { background: #22c55e; }
.ws-off { background: #9ca3af; }

.alerts-panel {
  position: absolute;
  top: 48px; right: 0;
  width: 340px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
  z-index: 1000;
  overflow: hidden;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f3f4f6;
  background: #fafafa;
}
.panel-header h3 { margin: 0; font-size: 0.95rem; }

.count-chip {
  background: #ef4444;
  color: white;
  font-size: 0.7rem;
  border-radius: 9999px;
  padding: 1px 6px;
  margin-left: 6px;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: #6b7280;
}

.empty-state {
  padding: 24px;
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.section { padding: 8px 0; }
.section-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 4px 16px;
  color: #f59e0b;
}
.anomaly-title { color: #ef4444; }

.alert-item {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  border-left: 3px solid transparent;
  transition: background 0.15s;
}
.alert-item:hover { background: #fafafa; }
.alert-item.low   { border-left-color: #f59e0b; }
.alert-item.anomaly { border-left-color: #ef4444; background: #fff5f5; }

.alert-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.alert-info strong { font-size: 0.875rem; }
.alert-meta { font-size: 0.75rem; color: #6b7280; }

.dismiss {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.75rem;
  color: #9ca3af;
  padding: 4px;
}
.dismiss:hover { color: #374151; }

/* Toast */
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
}
.toast {
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  pointer-events: auto;
}
.toast-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
.toast-danger  { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

/* Transitions */
.slide-enter-active, .slide-leave-active { transition: all 0.25s ease; }
.slide-enter-from { opacity: 0; transform: translateY(-8px); }
.slide-leave-to  { opacity: 0; transform: translateY(-8px); }

.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from { opacity: 0; transform: translateX(40px); }
.toast-leave-to  { opacity: 0; transform: translateX(40px); }

@keyframes ring {
  0%, 100% { transform: rotate(0); }
  10%, 30%  { transform: rotate(-10deg); }
  20%, 40%  { transform: rotate(10deg); }
  50%       { transform: rotate(0); }
}
</style>
