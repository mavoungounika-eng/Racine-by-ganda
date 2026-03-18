<template>
  <div class="page">
    <h1>{{ t('session.open') }}</h1>
    <div class="card">
      <input v-model.number="openingCash" type="number" placeholder="Montant ouverture (XAF)" />
      <button @click="openSession">Confirmer</button>
      <p v-if="error" class="error">{{ error }}</p>
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

onMounted(async () => {
  try {
    const res = await session.getCurrentSession();
    if (res.data?.session) router.push('/terminal');
  } catch (_) {}
});

const openSession = async () => {
  error.value = '';
  try {
    await session.openSession(openingCash.value);
    router.push('/terminal');
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'SESSION_ALREADY_OPEN';
  }
};
</script>

<style scoped>
.page { padding: 24px; }
.card { background: var(--card); padding: 16px; border-radius: 12px; }
button { background: var(--accent); color: white; border: none; padding: 12px 16px; border-radius: 10px; }
.error { color: var(--danger); }
</style>
