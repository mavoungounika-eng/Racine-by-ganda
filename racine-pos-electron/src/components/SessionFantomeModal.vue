<template>
  <Teleport to="body">
    <div v-if="visible" style="position:fixed;inset:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999">
      <div style="background:#1e1e1e;border:1px solid #333;border-radius:12px;padding:28px;width:480px;max-width:95vw;color:#f0f0f0">

        <!-- Header -->
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px">
          <svg style="color:#f59e0b;flex-shrink:0;margin-top:2px" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <div>
            <div style="font-size:18px;font-weight:600;color:#fff;margin-bottom:4px">Session déjà ouverte</div>
            <div style="font-size:13px;color:#999">Une session active a été détectée pour cet opérateur</div>
          </div>
        </div>

        <!-- Infos -->
        <div style="background:#2a2a2a;border-radius:8px;padding:16px;margin-bottom:20px">
          <div v-for="row in infoRows" :key="row.label" style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px">
            <span style="color:#888">{{ row.label }}</span>
            <span :style="row.style || 'color:#e0e0e0'">{{ row.value }}</span>
          </div>
          <div style="border-top:1px solid #333;margin:8px 0"/>
          <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px">
            <span style="color:#888">Total session</span>
            <span style="color:#fff;font-weight:600">{{ formatMontant(session.total_ventes) }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px">
            <span style="color:#888">Tickets émis</span>
            <span style="color:#e0e0e0">{{ session.nombre_tickets }}</span>
          </div>
          <div v-if="session.a_panier_en_cours" style="margin-top:10px;padding:6px 10px;background:#1d3a26;border:1px solid #2d6a3f;border-radius:6px;font-size:12px;color:#4ade80">
            Panier en cours disponible — sera restauré si vous reprenez
          </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;flex-direction:column;gap:8px">
          <button :disabled="!!loading" @click="handleReprendre"
            style="padding:10px 16px;border-radius:8px;border:none;font-size:14px;font-weight:500;cursor:pointer;background:#c2410c;color:#fff;display:flex;align-items:center;justify-content:center;gap:8px">
            <span v-if="loading === 'reprendre'">...</span>
            <span v-else>Reprendre cette session</span>
          </button>

          <button :disabled="!!loading" @click="showCashPrompt = !showCashPrompt"
            style="padding:10px 16px;border-radius:8px;border:none;font-size:14px;font-weight:500;cursor:pointer;background:#374151;color:#e0e0e0">
            Clôturer et ouvrir une nouvelle session
          </button>

          <!-- Fond de caisse prompt -->
          <div v-if="showCashPrompt" style="padding:16px;background:#2a2a2a;border-radius:8px;border:1px solid #444">
            <div style="font-size:13px;color:#ccc;margin-bottom:10px">Fond de caisse pour la nouvelle session (FCFA)</div>
            <input v-model.number="openingCash" type="number" min="0" step="500" placeholder="Ex: 50000"
              style="width:100%;padding:10px 12px;background:#1e1e1e;border:1px solid #555;border-radius:6px;color:#fff;font-size:16px;margin-bottom:12px;box-sizing:border-box"
              @keyup.enter="handleForceClose"/>
            <div style="display:flex;gap:8px">
              <button :disabled="!!loading" @click="handleForceClose"
                style="padding:7px 14px;border-radius:6px;border:none;font-size:13px;cursor:pointer;background:#c2410c;color:#fff">
                <span v-if="loading === 'forceClose'">...</span><span v-else>Confirmer</span>
              </button>
              <button @click="showCashPrompt = false"
                style="padding:7px 14px;border-radius:6px;border:none;font-size:13px;cursor:pointer;background:transparent;color:#666">
                Retour
              </button>
            </div>
          </div>

          <button :disabled="!!loading" @click="$emit('cancel')"
            style="padding:8px;border:none;background:transparent;color:#666;font-size:13px;cursor:pointer">
            Annuler — laisser la session active
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useSessionStore } from '../stores/session'

const props = defineProps({
  visible: Boolean,
  session: { type: Object, required: true },
  operateurId: { type: Number, required: true },
  machineId: { type: String, required: true },
  machineName: { type: String, default: '' },
})

const emit = defineEmits(['reprendre', 'nouvelle-session', 'cancel'])
const sessionStore = useSessionStore()

const loading       = ref(null)
const showCashPrompt = ref(false)
const openingCash   = ref(0)

const infoRows = computed(() => [
  { label: 'Opérateur',       value: props.session.operateur_nom, style: 'color:#fff;font-weight:600' },
  { label: 'Email',           value: props.session.operateur_email },
  { label: 'Ouverte le',      value: props.session.opened_at_human },
  { label: 'Durée écoulée',   value: props.session.duree_human,
    style: props.session.duree_minutes > 120 ? 'color:#f59e0b;font-weight:600' : 'color:#e0e0e0' },
  { label: 'Machine origine', value: props.session.machine_name, style: 'color:#aaa;font-family:monospace;font-size:12px' },
])

async function handleReprendre() {
  loading.value = 'reprendre'
  try {
    const payload = await sessionStore.resumeFantomeSession(
      props.session.session_id, props.operateurId, props.machineId, props.machineName
    )
    sessionStore.startHeartbeat(payload.session_id)
    emit('reprendre', payload)
  } catch (e) {
    console.error('Erreur reprise:', e)
  } finally {
    loading.value = null
  }
}

async function handleForceClose() {
  loading.value = 'forceClose'
  try {
    const newSession = await sessionStore.forceCloseAndNewSession(
      props.session.session_id, props.operateurId, props.machineId, props.machineName, openingCash.value
    )
    sessionStore.startHeartbeat(newSession.session_id)
    emit('nouvelle-session', newSession)
  } catch (e) {
    console.error('Erreur force-close:', e)
  } finally {
    loading.value = null
  }
}

function formatMontant(val) {
  return new Intl.NumberFormat('fr-FR').format(val ?? 0) + ' FCFA'
}
</script>
