<template>
  <div>
    <div v-if="error" class="error-boundary">
      <h3>{{ errorTitle }}</h3>
      <p>{{ errorMessage }}</p>
      <button @click="retry">Retry</button>
    </div>
    <slot v-else />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const error = ref(null);
const errorTitle = ref('Error');
const errorMessage = ref('');

const retry = () => {
  error.value = null;
};

const onErrorCaptured = (err) => {
  error.value = err;
  errorTitle.value = 'Erreur';
  errorMessage.value = err?.message || 'Unexpected error';
  return false;
};
</script>

<style scoped>
.error-boundary { padding: 16px; background: #fee2e2; border-radius: 10px; }
button { background: var(--accent); color: white; border: none; padding: 8px 12px; border-radius: 8px; }
</style>
