<template>
  <div class="page">
    <h1>{{ t('session.open') }}</h1>
    <form class="card" @submit.prevent="openSession">
      <label for="opening-cash">Fond de caisse d ouverture (XAF)</label>
      <input
        id="opening-cash"
        v-model.number="openingCash"
        type="number"
        min="0"
        step="1"
        placeholder="Montant ouverture (XAF)"
        :disabled="submitting"
        autofocus
      />
      <button type="submit" :disabled="submitting">
        {{ submitting ? 'Ouverture…' : 'Confirmer' }}
      </button>
      <p v-if="error" class="error">{{ error }}</p>
    </form>
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
    // Si le backend remonte une session ouverte pour cette machine, on saute directement au terminal.
    if (res?.data?.session) router.push('/terminal');
  } catch (_) {
    // Pas de session ouverte — on reste sur l écran d ouverture.
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
    error.value = e.response?.data?.error?.message
      || e.message
      || 'SESSION_OPEN_FAILED';
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.page { padding: 24px; }
.card {
  background: var(--card);
  padding: 16px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 420px;
}
.card label {
  font-size: 0.9rem;
  color: var(--text-muted, #6b7280);
}
.card input {
  padding: 12px;
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 10px;
  font-size: 1rem;
}
.card input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
button {
  background: var(--accent);
  color: white;
  border: none;
  padding: 12px 16px;
  border-radius: 10px;
  cursor: pointer;
}
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.error { color: var(--danger); }
</style>
