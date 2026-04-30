/**
 * Plugin Vite — réécriture dynamique de la CSP de index.html.
 *
 * Motivation :
 *  - L'index.html porte une CSP meta restrictive, utile en prod pour
 *    durcir le renderer Electron.
 *  - Mais les origines autorisées (backend API, Reverb WS) dépendent
 *    de l'environnement de déploiement (dev → localhost, prod → domaine
 *    réel). Hardcoder localhost en prod = app cassée.
 *
 * Ce que fait le plugin :
 *  1. Lit VITE_API_URL / VITE_REVERB_* via import.meta.env
 *  2. Remplace dans index.html les tokens connus :
 *       - `http://127.0.0.1:8000 http://localhost:8000` → origine API
 *       - `ws://localhost:5173 ws://127.0.0.1:5173`     → HMR (dev) / vide (prod)
 *       - `https://*.pusher.com wss://*.pusher.com`     → origine Reverb
 *
 * Le plugin tourne en dev ET en build — ça évite que les devs voient
 * une CSP différente de celle qui sera en prod.
 */
import { loadEnv } from 'vite';

export function cspTransform() {
  let apiOrigin = 'http://127.0.0.1:8000 http://localhost:8000';
  let reverbHttp = '';
  let reverbWs = '';
  let isDev = true;

  return {
    name: 'racine-pos:csp-transform',

    config(_userConfig, { mode }) {
      const env = loadEnv(mode, process.cwd(), '');

      if (env.VITE_API_URL) {
        apiOrigin = env.VITE_API_URL.replace(/\/+$/, '');
      }

      const reverbHost = env.VITE_REVERB_HOST;
      const reverbPort = env.VITE_REVERB_PORT;
      const reverbScheme = env.VITE_REVERB_SCHEME || 'http';

      if (reverbHost && reverbPort) {
        const httpScheme = reverbScheme === 'https' ? 'https' : 'http';
        const wsScheme = reverbScheme === 'https' ? 'wss' : 'ws';
        reverbHttp = `${httpScheme}://${reverbHost}:${reverbPort}`;
        reverbWs = `${wsScheme}://${reverbHost}:${reverbPort}`;
      }
    },

    configResolved(resolved) {
      isDev = resolved.command === 'serve';
    },

    transformIndexHtml(html) {
      let out = html;

      // 1. API origin
      out = out.replace(
        /http:\/\/127\.0\.0\.1:8000 http:\/\/localhost:8000/g,
        apiOrigin
      );

      // 2. HMR (ws Vite) — uniquement en dev, strippé en build
      const hmrTokens = 'ws://localhost:5173 ws://127.0.0.1:5173';
      if (!isDev) {
        // Suppression + normalisation des espaces doubles qui peuvent rester
        out = out.replace(new RegExp(`\\s*${escapeRegex(hmrTokens)}`, 'g'), '');
      }

      // 3. Reverb (remplace les placeholders Pusher cloud qui ne sont pas utilisés)
      const reverbAllowlist = [reverbHttp, reverbWs].filter(Boolean).join(' ');
      if (reverbAllowlist) {
        out = out.replace(
          /https:\/\/\*\.pusher\.com wss:\/\/\*\.pusher\.com/g,
          reverbAllowlist
        );
      }
      // Si pas d'env Reverb → on laisse les wildcards pusher.com (cas où
      // l'équipe basculerait sur Pusher Cloud plus tard, sans refaire
      // un build custom).

      return out;
    },
  };
}

function escapeRegex(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
