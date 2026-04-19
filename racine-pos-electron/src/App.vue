<template>
  <div class="app">
    <OfflineBanner />
    <div class="app-content">
      <ErrorBoundary>
        <router-view />
      </ErrorBoundary>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import OfflineBanner from './components/OfflineBanner.vue';
import ErrorBoundary from './components/ErrorBoundary.vue';
import { useOfflineStore } from './stores/offline';

const offline = useOfflineStore();

onMounted(() => {
  // Kick off offline system init (local DB counters + connectivity monitor).
  // Fire-and-forget: init is async but shouldn't block the UI. Any failure
  // during counter load is non-fatal; connectivity monitor is resilient.
  offline.init().catch((err) => {
    // eslint-disable-next-line no-console
    console.warn('offline.init() failed:', err);
  });
});
</script>

<style>
:root {
  --background: #101010;
  --surface: #1c1b1b;
  --surface-high: #2a2a2a;
  --primary: #f2ca50;
  --primary-dim: #d4af37;
  --on-surface: #e5e2e1;
  --outline-variant: #4d4635;

  --bg: var(--background);
  --card: var(--surface);
  --text: var(--on-surface);
  --accent: var(--primary-dim);
  --danger: #ffb4ab;
}

* {
  box-sizing: border-box;
}

html,
body,
#app {
  margin: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

body {
  font-family: 'Manrope', 'IBM Plex Sans', system-ui, -apple-system, sans-serif;
  background: var(--bg);
  color: var(--text);
}

.app {
  height: 100dvh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.app-content {
  flex: 1;
  min-height: 0;
}

button,
input,
select {
  font-size: 16px;
}
</style>

