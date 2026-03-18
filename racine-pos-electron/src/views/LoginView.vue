<template>
  <div class="page">
    <h1>{{ t('login.title') }}</h1>

    <section class="card">
      <h2>{{ t('login.device') }}</h2>
      <input v-model="machineId" placeholder="Machine ID" />
      <input v-model="deviceName" placeholder="Nom du terminal" />
      <button @click="register">{{ t('login.button') }}</button>
      <p v-if="deviceError" class="error">{{ deviceError }}</p>
    </section>

    <section class="card">
      <h2>{{ t('login.operator') }}</h2>
      <input v-model="username" placeholder="Username" />
      <input v-model="password" type="password" placeholder="Password" />
      <button @click="login">{{ t('login.button') }}</button>
      <p v-if="loginError" class="error">{{ loginError }}</p>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const auth = useAuthStore();
const router = useRouter();

const machineId = ref('');
const deviceName = ref('');
const username = ref('');
const password = ref('');
const deviceError = ref('');
const loginError = ref('');

onMounted(() => {
  auth.loadFromStorage();
});

const register = async () => {
  deviceError.value = '';
  try {
    await auth.register(machineId.value, deviceName.value);
  } catch (e) {
    deviceError.value = e.response?.data?.error?.message || 'Device registration failed';
  }
};

const login = async () => {
  loginError.value = '';
  try {
    await auth.login(username.value, password.value);
    router.push('/session/open');
  } catch (e) {
    loginError.value = e.response?.data?.error?.message || 'Login failed';
  }
};
</script>

<style scoped>
.page { padding: 24px; display: grid; gap: 20px; }
.card { background: var(--card); padding: 16px; border-radius: 12px; }
button { background: var(--accent); color: white; border: none; padding: 12px 16px; border-radius: 10px; }
input { width: 100%; margin: 6px 0; padding: 10px; }
.error { color: var(--danger); }
</style>
