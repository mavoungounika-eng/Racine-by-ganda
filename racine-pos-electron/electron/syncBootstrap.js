// syncBootstrap — main process : détection de connectivité via net.isOnline()
// et notification du renderer pour déclencher la sync offline.
//
// Pattern respecté : AUCUNE modification du preload, AUCUN nodeIntegration.
// On se contente d'injecter un CustomEvent 'network:online' dans le renderer
// (window.dispatchEvent), écouté par src/services/syncService.js. Si le
// service n'est pas chargé côté renderer, l'événement est inoffensif.
//
// Déclencheurs :
//  - au démarrage, après did-finish-load, si net.isOnline()
//  - à chaque transition offline → online détectée par polling

const { net } = require('electron');

const POLL_INTERVAL_MS = 15000;

let pollTimer = null;
let wasOnline = null;

function resolveWindow(getWindow) {
  const win = typeof getWindow === 'function' ? getWindow() : getWindow;
  if (!win || win.isDestroyed()) return null;
  return win;
}

function notifyRendererOnline(getWindow) {
  const win = resolveWindow(getWindow);
  if (!win) return;
  win.webContents
    .executeJavaScript(
      "window.dispatchEvent(new CustomEvent('network:online'));",
      true,
    )
    .catch(() => {
      // Renderer pas prêt / fenêtre fermée — sans gravité, le polling
      // ou l'événement window 'online' du renderer prendra le relais.
    });
}

/**
 * Initialise la détection de connectivité main-process.
 * Idempotent — un seul polling actif.
 *
 * @param {Function|BrowserWindow} getWindow  Fenêtre principale (ou getter,
 *   recommandé car mainWindow peut être recréée sur 'activate').
 */
function init(getWindow) {
  if (pollTimer) return;

  // Déclenchement initial : attendre que le renderer soit chargé
  const win = resolveWindow(getWindow);
  if (win) {
    const fireIfOnline = () => {
      wasOnline = net.isOnline();
      if (wasOnline) notifyRendererOnline(getWindow);
    };
    if (win.webContents.isLoading()) {
      win.webContents.once('did-finish-load', fireIfOnline);
    } else {
      fireIfOnline();
    }
  }

  // Re-sync à la reconnexion : transition offline → online
  pollTimer = setInterval(() => {
    const online = net.isOnline();
    if (online && wasOnline === false) {
      notifyRendererOnline(getWindow);
    }
    wasOnline = online;
  }, POLL_INTERVAL_MS);
}

function stop() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
  wasOnline = null;
}

module.exports = { init, stop };
