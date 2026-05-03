<template>
  <div class="close-shell">

    <!-- ── TOPBAR ── -->
    <header class="topbar">
      <button class="btn-back" @click="router.push('/terminal')">← Retour</button>
      <span class="topbar-title">Clôture de session</span>
      <span></span>
    </header>

    <!-- ── SCROLLABLE BODY ── -->
    <div class="close-scroll">
      <div class="close-canvas">

        <!-- Carte saisie -->
        <div class="close-card">
          <span class="card-eyebrow">Clôture</span>
          <h1 class="card-title">{{ t('session.close') }}</h1>
          <p class="card-subtitle">Comptez votre fond de caisse et renseignez le montant exact</p>

          <div v-if="summary" class="summary-block">
            <div class="summary-row">
              <span class="summary-label">Espèces attendues</span>
              <span class="summary-value">{{ fmtAmt(summary.expected_cash) }}</span>
            </div>
          </div>

          <div class="field-group">
            <label class="field-label" for="closing-cash">Espèces comptées (FCFA)</label>
            <div class="amount-row">
              <input
                id="closing-cash"
                v-model.number="closingCash"
                class="amount-input"
                type="number"
                min="0"
                step="100"
                placeholder="0"
              />
              <span class="amount-unit">FCFA</span>
            </div>

            <div v-if="discrepancy !== null" class="discrepancy" :class="discrepancy === 0 ? 'ok' : 'alert'">
              <span>{{ discrepancy === 0 ? '✓' : '⚠' }}</span>
              <span v-if="discrepancy === 0">Caisse équilibrée</span>
              <span v-else>Écart de {{ fmtAmt(Math.abs(discrepancy)) }} {{ discrepancy > 0 ? '(excédent)' : '(manquant)' }}</span>
            </div>
          </div>

          <button class="btn-close" @click="closeSession" :disabled="closing">
            <span v-if="closing">⏳</span><span v-else>🔒</span>
            {{ closing ? 'Clôture en cours…' : 'Clôturer la session' }}
          </button>

          <p v-if="error" class="error-msg">{{ error }}</p>
        </div>

        <!-- Z-Report -->
        <div v-if="zReport" class="zreport-container">
          <ZReport :report="zReport" />
          <div class="zreport-actions">
            <button class="btn-action" @click="showFullscreen = true">
              🔍 Plein écran
            </button>
            <button class="btn-action btn-action--primary" @click="printReport">
              🖨 Imprimer
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ── MODAL PLEIN ÉCRAN FACTURE ── -->
    <div v-if="showFullscreen" class="fullscreen-overlay" @click.self="showFullscreen = false">
      <div class="fullscreen-card">
        <div class="fullscreen-header">
          <h3 class="fullscreen-title">📄 Z-Report — Rapport complet</h3>
          <div class="fullscreen-actions">
            <button class="btn-action btn-action--primary" @click="printReport">🖨 Imprimer</button>
            <button class="btn-action" @click="showFullscreen = false">✕ Fermer</button>
          </div>
        </div>
        <div class="fullscreen-body">
          <ZReport :report="zReport" />
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from '../stores/session';
import { useRouter } from 'vue-router';
import ZReport from '../components/ZReport.vue';

const { t } = useI18n();
const session = useSessionStore();
const router = useRouter();
const closingCash = ref(0);
const summary = ref(null);
const error = ref('');
const closing = ref(false);
const showFullscreen = ref(false);

const zReport = computed(() => session.zReport);

const fmtAmt = (val) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(val || 0))) + ' FCFA';

const discrepancy = computed(() => {
  if (!summary.value) return null;
  const expected = summary.value.expected_cash ?? zReport.value?.expected_cash ?? 0;
  return closingCash.value - Number(expected);
});

onMounted(async () => {
  if (!session.currentSession) return;
  try { await session.buildLocalZReport(session.currentSession.id); } catch { /**/ }
  try {
    const res = await session.prepareClose(session.currentSession.id);
    summary.value = res.data || null;
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Impossible de préparer la clôture';
  }
});

const closeSession = async () => {
  if (!session.currentSession || closing.value) return;
  error.value = '';
  closing.value = true;
  try {
    await session.closeSession(session.currentSession.id, closingCash.value);
    await session.getZReport(session.currentSession.id);
  } catch (e) {
    try { await session.buildLocalZReport(session.currentSession.id); } catch { /**/ }
    error.value = e.response?.data?.error?.message || 'Échec de la clôture';
  } finally {
    closing.value = false;
  }
};

const printReport = () => window.print();
</script>

<style scoped>
/* ── Shell ────────────────────────────────────────────────── */
.close-shell {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: var(--background);
}

/* ── Topbar ───────────────────────────────────────────────── */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  height: 52px;
  background: var(--surface);
  border-bottom: 1px solid var(--outline-variant);
  flex-shrink: 0;
}

.btn-back {
  border: 1px solid var(--outline-variant);
  border-radius: 8px;
  background: transparent;
  color: var(--on-surface-muted);
  font-size: 13px;
  font-weight: 700;
  padding: 6px 14px;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-back:hover { border-color: var(--primary); color: var(--on-surface); }

.topbar-title {
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--on-surface-muted);
}

/* ── Scroll body ──────────────────────────────────────────── */
.close-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 32px 24px;
  background:
    radial-gradient(circle at 80% 15%, rgba(255, 184, 0, 0.07) 0%, transparent 35%),
    var(--background);
}

.close-canvas {
  max-width: 640px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding-bottom: 40px;
}

/* ── Carte ────────────────────────────────────────────────── */
.close-card {
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 20px;
  padding: clamp(20px, 3vw, 32px);
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
}

.card-eyebrow {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--accent, #f59e0b);
}

.card-title {
  margin: 0;
  font-size: clamp(24px, 3vw, 32px);
  font-weight: 900;
  color: var(--on-surface);
  line-height: 1;
}

.card-subtitle { margin: 0; font-size: 13px; color: var(--on-surface-muted); }

.summary-block {
  background: rgba(255, 184, 0, 0.08);
  border: 1px solid rgba(255, 184, 0, 0.2);
  border-radius: 12px;
  padding: 14px 16px;
}

.summary-row { display: flex; justify-content: space-between; align-items: center; }
.summary-label { font-size: 13px; color: var(--on-surface-muted); }
.summary-value { font-size: 20px; font-weight: 900; color: var(--accent, #f59e0b); font-feature-settings: "tnum"; }

.field-group { display: flex; flex-direction: column; gap: 10px; }

.field-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted);
}

.amount-row { display: flex; align-items: center; gap: 10px; }

.amount-input {
  flex: 1;
  height: 56px;
  font-size: 28px;
  font-weight: 900;
  text-align: right;
  background: var(--surface-high);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--on-surface);
  padding: 0 16px;
  font-feature-settings: "tnum";
  transition: border-color 0.15s, box-shadow 0.15s;
}

.amount-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

.amount-unit { font-size: 15px; font-weight: 700; color: var(--on-surface-muted); white-space: nowrap; }

.discrepancy {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; border-radius: 10px;
  font-size: 14px; font-weight: 700;
}

.discrepancy.ok { background: rgba(52, 211, 153, 0.1); color: var(--success, #34d399); border: 1px solid rgba(52, 211, 153, 0.2); }
.discrepancy.alert { background: rgba(255, 184, 0, 0.1); color: var(--warning, #f59e0b); border: 1px solid rgba(255, 184, 0, 0.2); }

.btn-close {
  height: 52px; border: none; border-radius: 14px;
  font-size: 15px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase;
  color: var(--on-primary);
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
  box-shadow: 0 4px 16px rgba(237, 95, 30, 0.3);
  transition: opacity 0.15s, transform 0.1s;
}

.btn-close:not(:disabled):hover { opacity: 0.92; transform: translateY(-1px); }
.btn-close:disabled { opacity: 0.4; cursor: not-allowed; box-shadow: none; }

.error-msg {
  margin: 0; color: var(--danger, #ff6b6b); font-size: 13px;
  padding: 10px 14px; background: rgba(255, 107, 107, 0.1);
  border-radius: 8px; border: 1px solid rgba(255, 107, 107, 0.2);
}

/* ── Z-Report ─────────────────────────────────────────────── */
.zreport-container { display: flex; flex-direction: column; align-items: center; gap: 14px; }

.zreport-actions { display: flex; gap: 10px; }

.btn-action {
  height: 40px; padding: 0 20px;
  border: 1px solid var(--outline-variant); border-radius: 10px;
  background: var(--surface); color: var(--on-surface-muted);
  font-size: 13px; font-weight: 700; cursor: pointer;
  transition: border-color 0.15s, color 0.15s;
}

.btn-action:hover { border-color: var(--primary); color: var(--on-surface); }

.btn-action--primary {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  border-color: transparent; color: var(--on-primary);
}

.btn-action--primary:hover { opacity: 0.9; color: var(--on-primary); }

/* ── Plein écran ──────────────────────────────────────────── */
.fullscreen-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  z-index: 200;
  display: flex;
  flex-direction: column;
  padding: 24px;
}

.fullscreen-card {
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-width: 900px;
  width: 100%;
  margin: 0 auto;
}

.fullscreen-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  border-bottom: 1px solid var(--outline-variant);
  flex-shrink: 0;
}

.fullscreen-title {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--on-surface);
}

.fullscreen-actions { display: flex; gap: 10px; }

.fullscreen-body {
  flex: 1;
  overflow-y: auto;
  padding: 32px;
  display: flex;
  justify-content: center;
  background:
    radial-gradient(circle at 50% 0%, rgba(237, 95, 30, 0.05) 0%, transparent 50%),
    var(--background);
}

/* ── Print ────────────────────────────────────────────────── */
@media print {
  .topbar, .zreport-actions, .fullscreen-header, .btn-back, .close-card { display: none !important; }
  .close-shell, .close-scroll { overflow: visible; height: auto; }
  .fullscreen-overlay { position: static; background: white; padding: 0; }
  .fullscreen-card { border: none; border-radius: 0; }
  .fullscreen-body { padding: 0; }
}
</style>