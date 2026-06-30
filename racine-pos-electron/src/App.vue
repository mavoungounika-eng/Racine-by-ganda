<template>
  <div class="app">
    <!-- Auto-update notification -->
    <div v-if="updateStatus === 'available'" class="update-banner">
      <span>Mise à jour v{{ updateVersion }} disponible</span>
      <button class="update-btn" @click="downloadUpdate">Télécharger</button>
      <button class="update-dismiss" @click="updateStatus = ''">&#10005;</button>
    </div>
    <div v-else-if="updateStatus === 'downloading'" class="update-banner">
      <span>Téléchargement en cours… {{ updatePercent }}%</span>
    </div>
    <div v-else-if="updateStatus === 'ready'" class="update-banner update-banner--ready">
      <span>Mise à jour prête</span>
      <button class="update-btn" @click="installUpdate">Installer et redémarrer</button>
      <button class="update-dismiss" @click="updateStatus = ''">&#10005;</button>
    </div>

    <OfflineBanner />
    <div class="app-content">
      <ErrorBoundary>
        <router-view />
      </ErrorBoundary>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import OfflineBanner from './components/OfflineBanner.vue';
import ErrorBoundary from './components/ErrorBoundary.vue';
import { useOfflineStore } from './stores/offline';

const offline = useOfflineStore();

const updateStatus = ref('');
const updateVersion = ref('');
const updatePercent = ref(0);

const downloadUpdate = async () => {
  updateStatus.value = 'downloading';
  try {
    await window.electron?.updater?.download();
  } catch {
    updateStatus.value = '';
  }
};

const installUpdate = () => {
  window.electron?.updater?.install();
};

onMounted(() => {
  offline.init().catch((err) => {
    // eslint-disable-next-line no-console
    console.warn('offline.init() failed:', err);
  });

  // Listen for auto-update events from main process
  if (window.electron?.updater) {
    window.electron.updater.onAvailable((info) => {
      updateVersion.value = info.version;
      updateStatus.value = 'available';
    });
    window.electron.updater.onProgress((p) => {
      updatePercent.value = p.percent;
    });
    window.electron.updater.onDownloaded(() => {
      updateStatus.value = 'ready';
    });
  }
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

/* ── Auto-update banner ────────────────────────────────────── */
.update-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  background: var(--surface-high);
  border-bottom: 1px solid var(--outline-variant);
  font-size: 13px;
  color: var(--on-surface);
  flex-shrink: 0;
}

.update-banner--ready {
  background: rgba(52, 211, 153, 0.12);
  border-color: rgba(52, 211, 153, 0.3);
}

.update-btn {
  padding: 4px 12px;
  border: none;
  border-radius: 6px;
  background: var(--primary);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.update-btn:hover {
  opacity: 0.9;
}

.update-dismiss {
  margin-left: auto;
  background: none;
  border: none;
  color: var(--on-surface-muted);
  cursor: pointer;
  font-size: 14px;
  padding: 2px 6px;
}
</style>

