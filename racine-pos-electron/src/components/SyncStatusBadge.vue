<template>
  <div
    class="sync-badge"
    :class="badgeClass"
    :title="badgeTitle"
    @click="handleClick"
    role="status"
    :aria-label="badgeTitle"
  >
    <!-- Spinning icon while syncing -->
    <span v-if="offline.isSyncing" class="icon spin" aria-hidden="true">↻</span>

    <!-- Conflict indicator -->
    <span v-else-if="offline.hasConflicts" class="icon" aria-hidden="true">⚠</span>

    <!-- Offline pill -->
    <span v-else-if="offline.isOffline" class="icon" aria-hidden="true">✗</span>

    <!-- Online / pending sales to sync -->
    <span v-else class="icon" aria-hidden="true">✓</span>

    <!-- Counter badge -->
    <span v-if="offline.localPendingCount > 0" class="counter">
      {{ offline.localPendingCount > 99 ? '99+' : offline.localPendingCount }}
    </span>

    <!-- Conflict counter -->
    <span v-if="offline.localConflictCount > 0" class="counter conflict-counter">
      {{ offline.localConflictCount }}⚠
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useOfflineStore } from '../stores/offline';

const offline = useOfflineStore();

const emit = defineEmits(['click']);

const badgeClass = computed(() => ({
  'badge--syncing': offline.isSyncing,
  'badge--conflict': !offline.isSyncing && offline.hasConflicts,
  'badge--offline': !offline.isSyncing && !offline.hasConflicts && offline.isOffline,
  'badge--pending': !offline.isSyncing && !offline.hasConflicts && !offline.isOffline && offline.localPendingCount > 0,
  'badge--online': !offline.isSyncing && !offline.hasConflicts && !offline.isOffline && offline.localPendingCount === 0,
}));

const badgeTitle = computed(() => {
  if (offline.isSyncing) return 'Synchronisation en cours…';
  if (offline.hasConflicts) return `${offline.localConflictCount} conflit(s) à résoudre`;
  if (offline.isOffline) return 'Mode hors-ligne — ventes en attente';
  if (offline.localPendingCount > 0)
    return `${offline.localPendingCount} vente(s) en attente de sync`;
  return 'En ligne — tout synchronisé';
});

function handleClick() {
  if (!offline.isSyncing && !offline.isOffline && offline.localPendingCount > 0) {
    offline.syncNow();
  }
  emit('click');
}
</script>

<style scoped>
.sync-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  user-select: none;
  transition: background 0.25s, transform 0.15s;
  letter-spacing: 0.03em;
  border: 2px solid transparent;
}

.sync-badge:hover {
  transform: scale(1.05);
}

/* States */
.badge--online   { background: #16a34a22; color: #16a34a; border-color: #16a34a44; }
.badge--pending  { background: #2563eb22; color: #2563eb; border-color: #2563eb44; }
.badge--offline  { background: #dc262622; color: #dc2626; border-color: #dc262644; }
.badge--conflict { background: #f59e0b22; color: #b45309; border-color: #f59e0b66; }
.badge--syncing  { background: #7c3aed22; color: #7c3aed; border-color: #7c3aed44; }

.icon {
  font-size: 1em;
  line-height: 1;
}

.spin {
  display: inline-block;
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  to { transform: rotate(360deg); }
}

.counter {
  background: currentColor;
  color: white;
  border-radius: 999px;
  padding: 1px 6px;
  font-size: 0.75em;
  line-height: 1.4;
  min-width: 18px;
  text-align: center;
}

.conflict-counter {
  background: #b45309;
}
</style>
