<template>
  <div class="session-shell">
    <div class="session-canvas">
      <div class="session-card">
        <span class="card-eyebrow">Caisse</span>
        <h1 class="card-title">{{ t('session.open') }}</h1>
        <p class="card-subtitle">Renseignez le fond de caisse pour démarrer la session</p>

        <form class="session-form" @submit.prevent="openSession">
          <label class="field-label" for="opening-cash">Fond de caisse d'ouverture (FCFA)</label>
          <div class="amount-row">
            <input
              id="opening-cash"
              v-model.number="openingCash"
              class="amount-input"
              type="number"
              min="0"
              step="100"
              placeholder="0"
              :disabled="submitting"
              autofocus
            />
            <span class="amount-unit">FCFA</span>
          </div>

          <button type="submit" class="btn-submit" :disabled="submitting">
            <span v-if="submitting" class="spinner">↻</span>
            <span v-else>🔓</span>
            {{ submitting ? 'Ouverture…' : 'Ouvrir la caisse' }}
          </button>

          <p v-if="error" class="error-msg">{{ error }}</p>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from '../stores/session';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const session = useSessionStore();
const router = useRouter();
const openingCash = ref(0);
const error = ref('');
const submitting = ref(false);

onMounted(async () => {
  try {
    const res = await session.getCurrentSession();
    if (res?.data?.session) router.push('/terminal');
  } catch (_) {
    // Pas de session ouverte — on reste sur cet écran.
  }
});

const openSession = async () => {
  if (submitting.value) return;
  error.value = '';
  submitting.value = true;
  try {
    await session.openSession(openingCash.value);
    router.push('/terminal');
  } catch (e) {
    error.value = e.response?.data?.error?.message || e.message || 'SESSION_OPEN_FAILED';
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.session-shell {
  height: 100%;
  min-height: 0;
  min-width: 0;
  display: grid;
  place-items: center;
  background:
    radial-gradient(circle at 20% 20%, rgba(237, 95, 30, 0.1) 0%, transparent 40%),
    var(--background);
  padding: 24px;
  overflow-y: auto;
}

/* App-shell fix: this view has no inner list, so the shell itself scrolls
   if the centered card exceeds the available height. */
.session-canvas {
  width: 100%;
  min-height: 0;
  min-width: 0;
  display: grid;
  place-items: center;
}

.session-card {
  width: min(480px, 100%);
  min-width: 0;
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 20px;
  padding: clamp(24px, 4vw, 36px);
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.5);
}

.card-eyebrow {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--primary);
  margin-bottom: 8px;
}

.card-title {
  margin: 0 0 6px;
  font-size: clamp(26px, 3.5vw, 36px);
  font-weight: 900;
  color: var(--on-surface);
  line-height: 1;
}

.card-subtitle {
  margin: 0 0 24px;
  font-size: 14px;
  color: var(--on-surface-muted);
  line-height: 1.4;
}

.session-form {
  display: flex;
  flex-direction: column;
  min-width: 0;
  gap: 12px;
}

.field-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted);
}

.amount-row {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: 10px;
}

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

.amount-input:disabled { opacity: 0.5; cursor: not-allowed; }

.amount-unit {
  font-size: 15px;
  font-weight: 700;
  color: var(--on-surface-muted);
  white-space: nowrap;
}

.btn-submit {
  margin-top: 6px;
  height: 54px;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--on-primary);
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 4px 18px rgba(237, 95, 30, 0.3);
  transition: opacity 0.15s, transform 0.1s;
}

.btn-submit:not(:disabled):hover {
  opacity: 0.92;
  transform: translateY(-1px);
}

.btn-submit:disabled { opacity: 0.4; cursor: not-allowed; box-shadow: none; }

.error-msg {
  margin: 0;
  color: var(--danger);
  font-size: 13px;
  padding: 10px 14px;
  background: rgba(255, 107, 107, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(255, 107, 107, 0.2);
}

.spinner {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
