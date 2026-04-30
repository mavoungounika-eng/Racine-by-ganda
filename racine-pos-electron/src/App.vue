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
/* ============================================================
   CHARTE GRAPHIQUE — RACINE BY GANDA
   Noir #160D0C | Orange #ED5F1E | Jaune #FFB800 | Blanc #FFFFFF
   Polices : Coco Gothic (corps) · Aileron (accent) · Aleppo (titres)
   ============================================================ */
:root {
  /* Surfaces sombres (dérivées du noir charte #160D0C) */
  --background:       #0D0907;
  --surface:          #1A1210;
  --surface-high:     #231815;
  --surface-overlay:  rgba(26, 18, 16, 0.97);

  /* Couleurs charte */
  --primary:          #ED5F1E;   /* Orange Racine — CTA principal */
  --primary-dim:      #C94E16;   /* Orange foncé — hover/pressed */
  --accent:           #FFB800;   /* Jaune Racine — highlights */
  --on-primary:       #FFFFFF;

  /* Textes */
  --on-surface:       #F5EFE8;   /* Blanc chaud lisible */
  --on-surface-muted: #B8A89A;   /* Texte secondaire */
  --on-surface-faint: #7A6A62;   /* Texte tertiaire */

  /* Bordures */
  --outline-variant:  rgba(237, 95, 30, 0.2);
  --border:           rgba(237, 95, 30, 0.15);

  /* États */
  --danger:           #FF6B6B;
  --success:          #34D399;
  --warning:          #FFB800;

  /* Alias legacy (compatibilité composants existants) */
  --bg:               var(--background);
  --card:             var(--surface);
  --text:             var(--on-surface);
  --text-muted:       var(--on-surface-muted);
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
  /* Coco Gothic > Century Gothic > system-ui comme fallback Electron */
  font-family: 'Coco Gothic', 'Century Gothic', 'Futura', system-ui, -apple-system, sans-serif;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
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
  font-family: inherit;
}

/* Focus global accessible */
:focus-visible {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
}
</style>

