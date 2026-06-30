<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="visible" class="modal-overlay" @click.self="close" role="dialog" aria-modal="true" aria-labelledby="conflict-title">
        <div class="modal-content">
          <!-- Header -->
          <div class="modal-header">
            <span class="modal-icon">⚠</span>
            <h2 id="conflict-title">Conflits de stock détectés</h2>
            <button class="close-btn" @click="close" aria-label="Fermer">✕</button>
          </div>

          <!-- Body -->
          <div class="modal-body">
            <p class="description">
              Les ventes suivantes ne peuvent pas être synchronisées car le stock est insuffisant.
              Choisissez une action pour chaque vente.
            </p>

            <div v-if="loading" class="loading-state">
              <span class="spin">↻</span> Chargement des conflits…
            </div>

            <div
              v-for="conflict in conflicts"
              :key="conflict.uuid"
              class="conflict-card"
            >
              <div class="conflict-info">
                <div class="conflict-uuid">Vente #{{ conflict.uuid.slice(0, 8) }}…</div>
                <div class="conflict-time">{{ formatDate(conflict.conflictedAt || conflict.createdAt) }}</div>
                <div v-if="conflict.conflictData" class="conflict-detail">
                  <strong>{{ conflict.conflictData.product_name }}</strong> —
                  demandé : {{ conflict.conflictData.requested_qty }},
                  disponible : {{ conflict.conflictData.available_stock }}
                </div>
              </div>

              <div class="conflict-actions">
                <button
                  class="btn btn-force"
                  :disabled="resolving === conflict.uuid"
                  @click="resolve(conflict.uuid, 'force_apply')"
                  title="Forcer l'application même si le stock est négatif"
                >
                  <span v-if="resolving === conflict.uuid" class="spin">↻</span>
                  Forcer
                </button>
                <button
                  class="btn btn-discard"
                  :disabled="resolving === conflict.uuid"
                  @click="resolve(conflict.uuid, 'discard')"
                  title="Ignorer cette vente — elle sera annulée"
                >
                  Annuler
                </button>
              </div>
            </div>

            <div v-if="!loading && conflicts.length === 0" class="no-conflicts">
              ✓ Aucun conflit en attente.
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <button class="btn btn-close-all" @click="close">Fermer</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useOfflineStore } from '../stores/offline';
import LocalDb from '../services/localDb';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const offline = useOfflineStore();
const conflicts = ref([]);
const loading = ref(false);
const resolving = ref(null);

watch(
  () => props.visible,
  async (isVisible) => {
    if (isVisible) {
      loading.value = true;
      conflicts.value = await LocalDb.getAllSales('conflict');
      loading.value = false;
    }
  },
);

async function resolve(uuid, resolution) {
  resolving.value = uuid;
  try {
    await offline.resolveConflict(uuid, resolution);
    conflicts.value = conflicts.value.filter((c) => c.uuid !== uuid);
  } catch (err) {
    console.error('Failed to resolve conflict', err);
  } finally {
    resolving.value = null;
  }
}

function close() {
  emit('close');
}

function formatDate(isoString) {
  if (!isoString) return '';
  return new Date(isoString).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style scoped>
/* Overlay */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: #1e1e2f;
  border: 1px solid #f59e0b66;
  border-radius: 16px;
  max-width: 540px;
  width: 90%;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
  color: #f3f4f6;
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 24px 0;
}
.modal-icon {
  font-size: 1.5rem;
  color: #f59e0b;
}
h2 {
  margin: 0;
  flex: 1;
  font-size: 1.1rem;
  font-weight: 700;
  color: #fef3c7;
}
.close-btn {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: color 0.2s;
}
.close-btn:hover { color: #f3f4f6; }

/* Body */
.modal-body {
  padding: 20px 24px;
}
.description {
  font-size: 0.9rem;
  color: #9ca3af;
  margin: 0 0 20px;
}

/* Conflict cards */
.conflict-card {
  background: #2a2a40;
  border: 1px solid #374151;
  border-left: 4px solid #f59e0b;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 14px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}
.conflict-info { flex: 1; }
.conflict-uuid {
  font-size: 0.82rem;
  color: #9ca3af;
  font-family: monospace;
  margin-bottom: 4px;
}
.conflict-time {
  font-size: 0.78rem;
  color: #6b7280;
  margin-bottom: 8px;
}
.conflict-detail {
  font-size: 0.88rem;
  color: #fde68a;
}

/* Action buttons */
.conflict-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 90px;
}

.btn {
  border: none;
  border-radius: 8px;
  padding: 7px 14px;
  font-weight: 600;
  font-size: 0.82rem;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.1s;
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn:not(:disabled):hover { transform: scale(1.04); }

.btn-force   { background: #dc2626; color: white; }
.btn-discard { background: #374151; color: #d1d5db; }

/* Footer */
.modal-footer {
  padding: 16px 24px;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #374151;
}
.btn-close-all {
  background: #4b5563;
  color: #f3f4f6;
}

/* States */
.loading-state, .no-conflicts {
  text-align: center;
  padding: 32px;
  color: #6b7280;
  font-size: 0.9rem;
}
.no-conflicts { color: #16a34a; }

.spin {
  display: inline-block;
  animation: rotate 1s linear infinite;
}
@keyframes rotate { to { transform: rotate(360deg); } }

/* Transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
