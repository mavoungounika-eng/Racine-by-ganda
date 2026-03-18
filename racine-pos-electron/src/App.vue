<template>
  <div class="app">
    <OfflineBanner />
    <ErrorBoundary>
      <router-view />
    </ErrorBoundary>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import OfflineBanner from './components/OfflineBanner.vue';
import ErrorBoundary from './components/ErrorBoundary.vue';
import { useOfflineStore } from './stores/offline';

const offline = useOfflineStore();

onMounted(() => {
  offline.initOfflineSystem(offline.client());
});
</script>

<style>
:root {
  --bg: #f5f3ee;
  --accent: #0f766e;
  --danger: #b91c1c;
  --text: #1f2937;
  --card: #ffffff;
}

body {
  margin: 0;
  font-family: 'IBM Plex Sans', system-ui, -apple-system, sans-serif;
  background: var(--bg);
  color: var(--text);
}

.app {
  min-height: 100vh;
}

button, input, select {
  font-size: 16px;
}
</style>
