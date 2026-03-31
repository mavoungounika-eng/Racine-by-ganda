<template>
  <div class="login-shell">
    <header class="topbar">
      <div class="brand">RACINE BY GANDA</div>
      <div class="status-pill" :class="{ ready: !initializingTerminal && !terminalError }">
        <span class="dot"></span>
        <span>{{ initializingTerminal ? t('login.initializing') : (terminalError ? terminalError : t('login.ready')) }}</span>
      </div>
    </header>

    <main class="canvas">
      <section class="auth-card">
        <p class="eyebrow">Acces securise</p>
        <h1>{{ t('login.title') }}</h1>
        <p class="subtitle">Point of Sale</p>

        <form class="form" @submit.prevent="login">
          <label>{{ t('login.email') }}</label>
          <input
            v-model="email"
            type="email"
            autocomplete="username"
            :placeholder="t('login.email')"
            :disabled="initializingTerminal"
          />

          <label>{{ t('login.password') }}</label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            :placeholder="t('login.password')"
            :disabled="initializingTerminal"
          />

          <button type="submit" :disabled="initializingTerminal || isPendingDevice || !email || !password">
            {{ t('login.button') }}
          </button>

          <p v-if="isPendingDevice" class="warn">Terminal en attente d'activation administrateur. Contactez l'admin avant de vous connecter.</p>
          <p v-if="loginError" class="error">{{ loginError }}</p>
          <p v-if="terminalError" class="error">{{ terminalError }}</p>
        </form>
      </section>
    </main>

    <footer class="footer">
      <div class="footer-left">
        <p>© 2026 <strong>RACINE BY GANDA</strong>. Tous droits reserves.</p>
        <p>
          Developpe par <strong>NIKA DIGITAL HUB</strong>
          <span class="sep">|</span>
          Solutions Web &amp; Communication
          <span class="sep">CG</span>
          Republique du Congo
        </p>
      </div>
      <div class="footer-right">
        <a href="#">CGV</a>
        <span>•</span>
        <a href="#">Confidentialite</a>
        <span>•</span>
        <a href="#">Cookies</a>
        <span class="sep">|</span>
        <span>Paiement securise</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const auth = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const loginError = ref('');
const terminalError = ref('');
const initializingTerminal = ref(true);
const isPendingDevice = computed(() => auth.device?.status === 'pending');

onMounted(async () => {
  auth.loadFromStorage();

  try {
    await auth.ensureTerminalRegistered();
  } catch (e) {
    terminalError.value = e.response?.data?.error?.message || 'Terminal initialization failed';
  } finally {
    initializingTerminal.value = false;
  }
});

const login = async () => {
  if (isPendingDevice.value) {
    return;
  }

  loginError.value = '';
  try {
    await auth.login(email.value, password.value);
    router.push('/session/open');
  } catch (e) {
    loginError.value = e.response?.data?.error?.message || e.message || 'Login failed';
  }
};
</script>

<style scoped>
.login-shell {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-rows: 72px 1fr 72px;
  background:
    radial-gradient(circle at 15% 15%, rgba(242, 202, 80, 0.16) 0%, transparent 35%),
    radial-gradient(circle at 90% 70%, rgba(212, 175, 55, 0.1) 0%, transparent 32%),
    #101010;
  color: var(--on-surface);
  overflow: hidden;
}

.topbar {
  padding: 0 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--outline-variant);
  background: rgba(16, 16, 16, 0.94);
}

.brand {
  font-weight: 800;
  letter-spacing: 0.08em;
  color: var(--primary);
  font-size: clamp(14px, 2vw, 18px);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #d0c5af;
  background: #1e1e1e;
  border: 1px solid var(--outline-variant);
  border-radius: 999px;
  padding: 6px 10px;
  max-width: 58vw;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-pill.ready {
  color: var(--primary);
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 10px rgba(242, 202, 80, 0.7);
  flex-shrink: 0;
}

.canvas {
  display: grid;
  place-items: center;
  padding: 20px;
  overflow: hidden;
}

.auth-card {
  width: min(560px, 100%);
  background: rgba(28, 27, 27, 0.96);
  border: 1px solid var(--outline-variant);
  border-radius: 16px;
  padding: clamp(18px, 3vw, 28px);
  box-shadow: 0 20px 44px rgba(0, 0, 0, 0.56);
}

.eyebrow {
  margin: 0;
  color: var(--primary);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.16em;
}

h1 {
  margin: 10px 0 0;
  color: #f5efe0;
  font-size: clamp(30px, 4vw, 46px);
  line-height: 1;
}

.subtitle {
  margin: 8px 0 18px;
  color: #bfb39a;
}

.form {
  display: grid;
  gap: 10px;
}

label {
  color: #d5cab1;
  font-size: 13px;
  font-weight: 700;
}

input {
  height: 48px;
  border: 1px solid #4b4431;
  background: #2a2a2a;
  color: #f5efe0;
  border-radius: 12px;
  padding: 0 14px;
}

input::placeholder {
  color: #8e8268;
}

input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(242, 202, 80, 0.18);
}

button {
  margin-top: 10px;
  height: 50px;
  border: none;
  border-radius: 12px;
  font-weight: 800;
  color: #3a2d00;
  background: linear-gradient(180deg, #f2ca50 0%, #d4af37 100%);
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.warn {
  margin: 4px 0 0;
  color: var(--primary);
  font-size: 12px;
}

.error {
  margin: 4px 0 0;
  color: #ffb4ab;
  font-size: 13px;
}

.footer {
  border-top: 1px solid var(--outline-variant);
  background: rgba(16, 16, 16, 0.95);
  color: #d0c5af;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 10px 18px;
  font-size: 11px;
  line-height: 1.35;
}

.footer-left p {
  margin: 0;
}

.footer strong {
  color: var(--primary);
}

.footer-right {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.footer a {
  color: inherit;
  text-decoration: none;
}

.footer a:hover {
  color: var(--primary);
}

.sep {
  opacity: 0.6;
}

@media (max-width: 900px) {
  .login-shell {
    grid-template-rows: 64px 1fr 64px;
  }

  .topbar {
    padding: 0 14px;
  }

  .status-pill {
    max-width: 52vw;
    font-size: 11px;
  }

  .footer {
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 2px;
    font-size: 9px;
    line-height: 1.1;
    padding: 4px 10px;
    overflow: hidden;
  }

  .footer-right {
    flex-wrap: nowrap;
    white-space: nowrap;
    width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>

