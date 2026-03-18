<template>
  <div class="page">
    <h1>{{ t('session.close') }}</h1>
    <div class="card">
      <div v-if="summary">
        <p>Expected: {{ summary.expected_cash }}</p>
      </div>
      <input v-model.number="closingCash" type="number" placeholder="Closing cash" />
      <p v-if="discrepancy" class="warn">Discrepancy: {{ discrepancy }}</p>
      <button @click="closeSession">{{ t('session.close') }}</button>
      <ZReport v-if="zReport" :report="zReport" />
      <button v-if="zReport" @click="printReport">Print</button>
      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from '../stores/session';
import ZReport from '../components/ZReport.vue';

const { t } = useI18n();
const session = useSessionStore();
const closingCash = ref(0);
const summary = ref(null);
const zReport = ref(null);
const error = ref('');

const discrepancy = computed(() => {
  if (!summary.value) return null;
  const expected = summary.value.expected_cash || 0;
  const diff = closingCash.value - expected;
  return Math.abs(diff) > 1000 ? diff : null;
});

onMounted(async () => {
  if (!session.currentSession) return;
  try {
    const res = await session.prepareClose(session.currentSession.id);
    summary.value = res.data || null;
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Prepare close failed';
  }
});

const closeSession = async () => {
  if (!session.currentSession) return;
  error.value = '';
  try {
    await session.closeSession(session.currentSession.id, closingCash.value);
    const zr = await session.getZReport(session.currentSession.id);
    zReport.value = zr.data?.z_report || zr.data || null;
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Close failed';
  }
};

const printReport = () => {
  window.print();
};
</script>

<style scoped>
.page { padding: 24px; }
.card { background: var(--card); padding: 16px; border-radius: 12px; display: grid; gap: 12px; }
button { background: var(--accent); color: white; border: none; padding: 12px 16px; border-radius: 10px; }
.warn { color: #b45309; }
.error { color: var(--danger); }
</style>
