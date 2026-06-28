<template>
  <div class="login-shell">

    <!-- ── Topbar ──────────────────────────────────────────── -->
    <header class="topbar">
      <div class="brand">
        <span class="brand-mark">R</span>
        <span class="brand-text">RACINE <em>BY</em> GANDA</span>
      </div>
      <div class="status-pill" :class="{ ready: !initializingTerminal && !terminalError }">
        <span class="dot" :class="{ pulse: initializingTerminal }"></span>
        <span>{{ initializingTerminal ? t('login.initializing') : (terminalError ? terminalError : t('login.ready')) }}</span>
      </div>
    </header>

    <!-- ── Canvas ─────────────────────────────────────────── -->
    <main class="canvas">
      <div class="glow glow-1" aria-hidden="true"></div>
      <div class="glow glow-2" aria-hidden="true"></div>
      <div class="ring ring-1" aria-hidden="true"></div>
      <div class="ring ring-2" aria-hidden="true"></div>

      <section class="auth-card">

        <!-- En-tête carte -->
        <div class="card-head">
          <div class="monogram" aria-hidden="true">R</div>
          <div class="card-titles">
            <p class="eyebrow">Point of Sale</p>
            <h1>{{ t('login.title') }}</h1>
          </div>
        </div>

        <div class="divider" aria-hidden="true"></div>

        <!-- Formulaire -->
        <form class="form" @submit.prevent="login" novalidate>
          <div class="field">
            <label for="pos-email">{{ t('login.email') }}</label>
            <input
              id="pos-email"
              v-model="email"
              type="email"
              autocomplete="username"
              :placeholder="t('login.email')"
            />
          </div>

          <div class="field">
            <label for="pos-password">{{ t('login.password') }}</label>
            <input
              id="pos-password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              :placeholder="t('login.password')"
            />
          </div>

          <button
            type="submit"
            class="btn-login"
            :disabled="initializingTerminal || isPendingDevice || !email || !password || cooldownSeconds > 0 || isLoading"
          >
            <span class="btn-spinner" v-if="isLoading" aria-hidden="true"></span>
            <span class="btn-label">
              {{ isLoading ? 'Connexion…' : cooldownSeconds > 0 ? `Patientez ${cooldownSeconds}s…` : t('login.button') }}
            </span>
            <span class="btn-arrow" v-if="!isLoading" aria-hidden="true">→</span>
          </button>

          <p v-if="isPendingDevice" class="msg warn">
            Terminal en attente d'activation. Contactez l'administrateur.
          </p>
          <p v-if="loginError" class="msg error">{{ loginError }}</p>
          <p v-if="auth.offline" class="msg warn">⚠ Mode hors ligne — opérations limitées au cache local</p>
          <p v-if="terminalError" class="msg error">{{ terminalError }}</p>
        </form>

      </section>
    </main>

    <!-- ── Session fantôme modale -->
    <SessionFantomeModal
      v-if="showFantomeModal && fantomeSession"
      :visible="showFantomeModal"
      :session="fantomeSession"
      :operateur-id="auth.user?.id"
      :machine-id="auth.device?.uuid || auth.device?.id || 'unknown'"
      :machine-name="auth.device?.name || ''"
      @reprendre="onReprendre"
      @nouvelle-session="onNouvelleSession"
      @cancel="onAnnulerFantome"
    />

    <!-- ── Footer minimal ─────────────────────────────────── -->
    <footer class="footer">
      <span>© 2026 <strong>RACINE BY GANDA</strong></span>
      <span class="sep">·</span>
      <span>NIKA DIGITAL HUB</span>
      <span class="sep">·</span>
      <span class="version">v1.0.0</span>
    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useOfflineStore } from '../stores/offline';
import { useSessionStore } from '../stores/session';
import SessionFantomeModal from '../components/SessionFantomeModal.vue';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const auth = useAuthStore();
const offline = useOfflineStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const loginError = ref('');
const terminalError = ref('');
const initializingTerminal = ref(true);
const cooldownSeconds = ref(0);
const isLoading = ref(false);
let cooldownTimer = null;
const isPendingDevice = computed(() => auth.device?.status === 'pending');

const sessionStore = useSessionStore();
const fantomeSession = ref(null);
const showFantomeModal = ref(false);

function startCooldown(seconds) {
  if (cooldownTimer) clearInterval(cooldownTimer);
  cooldownSeconds.value = Math.max(1, seconds | 0);
  cooldownTimer = setInterval(() => {
    cooldownSeconds.value -= 1;
    if (cooldownSeconds.value <= 0) {
      clearInterval(cooldownTimer);
      cooldownTimer = null;
    }
  }, 1000);
}

onMounted(async () => {
  auth.loadFromStorage();
  try {
    await offline.checkStatus();
    const isOffline = offline.isOffline || !navigator.onLine;
    await auth.ensureTerminalRegistered({ isOffline });
    if (isOffline) terminalError.value = '';
  } catch (e) {
    terminalError.value = e.response?.data?.error?.message || 'Terminal indisponible. Mode hors ligne disponible si ce terminal a deja ete synchronise.';
  } finally {
    initializingTerminal.value = false;
  }
});

const login = async () => {
  if (isPendingDevice.value || cooldownSeconds.value > 0) return;

  loginError.value = '';
  isLoading.value = true;
  try {
    await auth.login(email.value, password.value, {
      isOffline: offline.isOffline || auth.offline || !navigator.onLine,
    });
    // Vérifier session fantôme avant navigation
    const machineId = auth.device?.uuid || auth.device?.id || 'unknown';
    const machineName = auth.device?.name || window.navigator.userAgent.slice(0, 40);
    const check = await sessionStore.checkFantomeSession(auth.user?.id, machineId, machineName);
    if (check?.has_session) {
      fantomeSession.value = check.session;
      showFantomeModal.value = true;
    } else {
      router.push('/session/open');
    }
  } catch (e) {
    if (e.response?.status === 429) {
      const retryAfter =
        Math.min(parseInt(e.response?.headers?.['retry-after'], 10) ||
        e.response?.data?.error?.retry_after ||
        30, 60);
      loginError.value =
        e.response?.data?.error?.message ||
        `Trop de tentatives. Réessayez dans ${retryAfter}s.`;
      startCooldown(retryAfter);
      return;
    }
    if (e.response?.data?.error?.code === 'FORBIDDEN') {
      loginError.value = 'Acces POS reserve a l\'equipe interne ou aux createurs avec un abonnement Signature actif.';
      return;
    }
    loginError.value = e.response?.data?.error?.message || e.message || 'Login failed';
  } finally {
    isLoading.value = false;
  }
};

function onReprendre(payload) {
  showFantomeModal.value = false;
  router.push('/terminal');
}

function onNouvelleSession(payload) {
  showFantomeModal.value = false;
  router.push('/terminal');
}

function onAnnulerFantome() {
  showFantomeModal.value = false;
  loginError.value = 'Connexion annulée — session active sur une autre machine.';
}
</script>

<style scoped>
/* ── Shell ────────────────────────────────────────────────── */
/* App-shell fix: the root keeps overflow hidden, so the canvas scrolls when
   status/error messages make the login card taller than the viewport. */
.login-shell {
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: var(--background);
  color: var(--on-surface);
  overflow: hidden;
  position: relative;
}

/* ── Topbar ───────────────────────────────────────────────── */
.topbar {
  flex-shrink: 0;
  height: 60px;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-width: 0;
  background: rgba(13, 9, 7, 0.92);
  border-bottom: 1px solid var(--outline-variant);
  backdrop-filter: blur(8px);
  z-index: 10;
}

.brand {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: 10px;
}

.brand-mark {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  color: #fff;
  font-size: 15px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 0;
  flex-shrink: 0;
}

.brand-text {
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: var(--on-surface);
}

.brand-text em {
  font-style: normal;
  font-weight: 400;
  color: var(--on-surface-muted);
  font-size: 12px;
  margin: 0 2px;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--on-surface-muted);
  background: var(--surface-high);
  border: 1px solid var(--outline-variant);
  border-radius: 999px;
  padding: 5px 12px;
  max-width: 52vw;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-pill.ready {
  color: var(--primary);
  border-color: rgba(237, 95, 30, 0.3);
}

.dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--on-surface-faint);
  flex-shrink: 0;
  transition: background 0.3s;
}

.status-pill.ready .dot {
  background: var(--primary);
  box-shadow: 0 0 8px rgba(237, 95, 30, 0.7);
}

.dot.pulse {
  animation: blink 1.2s ease-in-out infinite;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.25; }
}

/* ── Canvas + décos background ───────────────────────────── */
.canvas {
  flex: 1;
  min-height: 0;
  min-width: 0;
  display: grid;
  place-items: center;
  padding: 24px 20px;
  overflow-y: auto;
  overflow-x: hidden;
  position: relative;
}

.glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  z-index: 0;
}

.glow-1 {
  width: 480px;
  height: 480px;
  top: -120px;
  left: -100px;
  background: radial-gradient(circle, rgba(237, 95, 30, 0.13) 0%, transparent 70%);
}

.glow-2 {
  width: 360px;
  height: 360px;
  bottom: -60px;
  right: -60px;
  background: radial-gradient(circle, rgba(255, 184, 0, 0.08) 0%, transparent 70%);
}

.ring {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  z-index: 0;
}

.ring-1 {
  width: 320px;
  height: 320px;
  top: -80px;
  left: -80px;
  border: 1px solid rgba(237, 95, 30, 0.1);
}

.ring-2 {
  width: 200px;
  height: 200px;
  bottom: 20px;
  right: 40px;
  border: 1px solid rgba(255, 184, 0, 0.08);
}

/* ── Auth card ────────────────────────────────────────────── */
.auth-card {
  position: relative;
  z-index: 1;
  width: min(520px, 100%);
  min-width: 0;
  background: rgba(22, 13, 12, 0.85);
  border: 1px solid rgba(237, 95, 30, 0.2);
  border-radius: 20px;
  padding: clamp(24px, 4vw, 40px);
  box-shadow:
    0 0 0 1px rgba(237, 95, 30, 0.06),
    0 24px 60px rgba(0, 0, 0, 0.65),
    0 4px 16px rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(12px);
}

/* ── En-tête carte ────────────────────────────────────────── */
.card-head {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.monogram {
  width: 56px;
  height: 56px;
  flex-shrink: 0;
  border-radius: 14px;
  background: linear-gradient(145deg, var(--primary) 0%, var(--primary-dim) 100%);
  color: #fff;
  font-size: 28px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(237, 95, 30, 0.4);
  letter-spacing: -0.01em;
}

.card-titles {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.eyebrow {
  margin: 0;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  color: var(--primary);
}

h1 {
  margin: 0;
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  color: var(--on-surface);
  line-height: 1;
  letter-spacing: -0.01em;
}

/* ── Divider dégradé ──────────────────────────────────────── */
.divider {
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, rgba(237, 95, 30, 0.35) 40%, rgba(237, 95, 30, 0.35) 60%, transparent 100%);
  margin-bottom: 24px;
}

/* ── Formulaire ───────────────────────────────────────────── */
.form {
  display: flex;
  flex-direction: column;
  min-width: 0;
  gap: 14px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--on-surface-muted);
}

input {
  height: 48px;
  background: var(--surface-high);
  border: 1px solid rgba(237, 95, 30, 0.18);
  border-radius: 10px;
  color: var(--on-surface);
  padding: 0 16px;
  font-size: 15px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

input::placeholder {
  color: var(--on-surface-faint);
}

input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

/* ── Bouton login ─────────────────────────────────────────── */
.btn-login {
  margin-top: 6px;
  height: 52px;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #fff;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  box-shadow: 0 4px 20px rgba(237, 95, 30, 0.38);
  transition: opacity 0.15s, transform 0.1s, box-shadow 0.15s;
}

.btn-login:not(:disabled):hover {
  opacity: 0.93;
  transform: translateY(-1px);
  box-shadow: 0 6px 26px rgba(237, 95, 30, 0.5);
}

.btn-login:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.btn-label {
  flex: 1;
  text-align: center;
}

.btn-arrow {
  font-size: 18px;
  font-weight: 400;
  opacity: 0.8;
}

/* ── Messages ─────────────────────────────────────────────── */
.msg {
  margin: 0;
  font-size: 12px;
  padding: 10px 14px;
  border-radius: 8px;
  line-height: 1.4;
}

.warn {
  color: var(--primary);
  background: rgba(237, 95, 30, 0.1);
  border: 1px solid rgba(237, 95, 30, 0.2);
}

.error {
  color: #ffb4ab;
  background: rgba(255, 107, 107, 0.1);
  border: 1px solid rgba(255, 107, 107, 0.2);
}

/* ── Footer minimal ───────────────────────────────────────── */
.footer {
  flex-shrink: 0;
  min-width: 0;
  height: 36px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-top: 1px solid rgba(237, 95, 30, 0.1);
  background: rgba(13, 9, 7, 0.8);
  font-size: 10px;
  color: var(--on-surface-faint);
  letter-spacing: 0.05em;
}

.footer strong {
  color: var(--on-surface-muted);
  font-weight: 700;
}

.sep {
  opacity: 0.4;
}

.version {
  font-family: monospace;
  font-size: 10px;
  color: rgba(237, 95, 30, 0.5);
}
</style>
