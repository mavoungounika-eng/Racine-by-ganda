// paymentWindows.js — Fenêtres de paiement externes (main process).
//
// Gère la BrowserWindow Monetbil : le renderer demande l'ouverture via IPC
// (`payments:monetbil:open`), le main process ouvre une fenêtre sécurisée
// (contextIsolation, sandbox, pas de preload) vers l'URL de paiement Monetbil,
// surveille les redirections (will-redirect / did-navigate) vers l'URL de
// retour, extrait la référence de transaction, ferme la fenêtre et renvoie
// le résultat au renderer.
//
// Résultats possibles renvoyés au renderer :
//   { status: 'success',   monetbil_ref: '...' }
//   { status: 'failed',    error: 'CODE', monetbil_ref?: '...' }
//   { status: 'cancelled' }  — fermeture manuelle ou annulation utilisateur
//
// Module auto-enregistré : un simple `require('./paymentWindows')` dans
// electron/main.js suffit (les handlers ipcMain sont posés au chargement).

const { BrowserWindow, ipcMain } = require('electron');

// URL widget Monetbil v2.1 — utilisée UNIQUEMENT en fallback si le backend
// ne fournit pas déjà une `payment_url` complète dans la réponse d'init.
const MONETBIL_WIDGET_BASE = 'https://www.monetbil.com/widget/v2.1/';

// URL de retour synthétique par défaut : elle n'a pas besoin de résoudre,
// on intercepte la navigation AVANT qu'elle ne se charge (will-redirect).
const DEFAULT_RETURN_URL = 'https://pos.racinebyganda.local/monetbil/return';

let monetbilWindow = null;

/**
 * Construit l'URL du widget Monetbil côté main à partir des paramètres
 * passés par le renderer (fallback quand le backend n'a pas d'endpoint
 * d'init qui renvoie une payment_url).
 */
function buildMonetbilWidgetUrl(opts = {}) {
  const serviceKey = opts.serviceKey || null;
  if (!serviceKey) return null;

  const params = new URLSearchParams();
  if (opts.amount != null) params.set('amount', String(Math.round(Number(opts.amount))));
  params.set('currency', opts.currency || 'XAF');
  if (opts.phone) params.set('phone', String(opts.phone));
  if (opts.paymentRef) params.set('payment_ref', String(opts.paymentRef));
  if (opts.itemRef) params.set('item_ref', String(opts.itemRef));
  params.set('return_url', opts.returnUrl || DEFAULT_RETURN_URL);
  params.set('locale', opts.locale || 'fr');

  return `${MONETBIL_WIDGET_BASE}${encodeURIComponent(serviceKey)}?${params.toString()}`;
}

/**
 * Analyse une URL de retour Monetbil et en extrait statut + référence.
 * Monetbil renvoie typiquement : ?transaction_id=...&status=success|failed|cancelled
 * (selon configuration : payment_ref / monetbil_ref peuvent aussi être présents).
 */
function parseReturnUrl(urlString) {
  let url;
  try {
    url = new URL(urlString);
  } catch {
    return { status: 'failed', monetbil_ref: null };
  }

  const q = url.searchParams;
  const ref =
    q.get('transaction_id') ||
    q.get('monetbil_ref') ||
    q.get('payment_ref') ||
    q.get('txn_id') ||
    null;

  const rawStatus = (q.get('status') || '').toLowerCase();

  if (rawStatus === 'success' || rawStatus === 'successful' || rawStatus === '1') {
    return { status: 'success', monetbil_ref: ref };
  }
  if (rawStatus === 'cancelled' || rawStatus === 'canceled') {
    return { status: 'cancelled', monetbil_ref: ref };
  }
  if (rawStatus === 'failed' || rawStatus === 'failure' || rawStatus === '0') {
    return { status: 'failed', monetbil_ref: ref };
  }

  // Pas de paramètre status explicite : la présence d'une référence de
  // transaction sur l'URL de retour est traitée comme un succès (le backend
  // re-vérifiera de toute façon via checkPayment côté serveur).
  if (ref) return { status: 'success', monetbil_ref: ref };

  return { status: 'failed', monetbil_ref: null };
}

/**
 * Ouvre la fenêtre de paiement Monetbil et résout quand :
 *  - la navigation atteint l'URL de retour (succès / échec / annulation), ou
 *  - l'utilisateur ferme la fenêtre manuellement (=> cancelled).
 */
function openMonetbilWindow(opts = {}) {
  return new Promise((resolve) => {
    // Une seule fenêtre de paiement à la fois.
    if (monetbilWindow && !monetbilWindow.isDestroyed()) {
      monetbilWindow.focus();
      resolve({ status: 'failed', error: 'MONETBIL_WINDOW_ALREADY_OPEN' });
      return;
    }

    const paymentUrl = opts.paymentUrl || buildMonetbilWidgetUrl(opts);
    if (!paymentUrl) {
      resolve({ status: 'failed', error: 'MONETBIL_URL_MISSING' });
      return;
    }

    const returnUrl = opts.returnUrl || DEFAULT_RETURN_URL;

    let settled = false;
    const settle = (result) => {
      if (settled) return;
      settled = true;
      resolve(result);
    };

    const parent = BrowserWindow.getFocusedWindow() || BrowserWindow.getAllWindows()[0] || null;

    monetbilWindow = new BrowserWindow({
      width: 480,
      height: 760,
      parent: parent || undefined,
      modal: !!parent,
      resizable: false,
      autoHideMenuBar: true,
      backgroundColor: '#160D0C',
      title: 'Paiement Monetbil — Racine POS',
      webPreferences: {
        contextIsolation: true,
        nodeIntegration: false,
        sandbox: true,
        // Aucun preload : la page Monetbil est un contenu distant non fiable.
      },
    });

    const wc = monetbilWindow.webContents;

    const isReturnUrl = (navUrl) => {
      if (!navUrl) return false;
      if (navUrl.startsWith(returnUrl)) return true;
      // Tolérance : même host+path que returnUrl mais query différente.
      try {
        const a = new URL(navUrl);
        const b = new URL(returnUrl);
        return a.host === b.host && a.pathname === b.pathname;
      } catch {
        return false;
      }
    };

    const handleNavigation = (navUrl) => {
      if (!isReturnUrl(navUrl)) return;
      const result = parseReturnUrl(navUrl);
      settle(result);
      if (monetbilWindow && !monetbilWindow.isDestroyed()) {
        monetbilWindow.close();
      }
    };

    wc.on('will-redirect', (_event, navUrl) => handleNavigation(navUrl));
    wc.on('did-navigate', (_event, navUrl) => handleNavigation(navUrl));
    wc.on('did-navigate-in-page', (_event, navUrl) => handleNavigation(navUrl));

    // Sécurité : aucune ouverture de fenêtre enfant depuis la page Monetbil.
    wc.setWindowOpenHandler(() => ({ action: 'deny' }));

    wc.on('did-fail-load', (_event, errorCode, _desc, failedUrl) => {
      // -3 (ABORTED) arrive quand on close() pendant une navigation — ignorer.
      if (errorCode === -3) return;
      // Si le chargement qui échoue est l'URL de retour synthétique, le
      // résultat a déjà été capté par will-redirect ; sinon, vraie erreur.
      if (isReturnUrl(failedUrl)) return;
      settle({ status: 'failed', error: 'MONETBIL_PAGE_LOAD_FAILED' });
      if (monetbilWindow && !monetbilWindow.isDestroyed()) {
        monetbilWindow.close();
      }
    });

    monetbilWindow.on('closed', () => {
      monetbilWindow = null;
      // Fermeture manuelle sans passage par l'URL de retour => annulation.
      settle({ status: 'cancelled' });
    });

    monetbilWindow.loadURL(paymentUrl);
  });
}

function closeMonetbilWindow() {
  if (monetbilWindow && !monetbilWindow.isDestroyed()) {
    monetbilWindow.close();
  }
  return true;
}

// ── Enregistrement IPC (au chargement du module) ───────────────────────────
ipcMain.handle('payments:monetbil:open', (_event, opts) => openMonetbilWindow(opts || {}));
ipcMain.handle('payments:monetbil:close', () => closeMonetbilWindow());

module.exports = { openMonetbilWindow, closeMonetbilWindow, parseReturnUrl, buildMonetbilWidgetUrl };
