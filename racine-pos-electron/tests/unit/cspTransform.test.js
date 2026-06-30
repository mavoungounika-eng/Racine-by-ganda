// Tests unitaires pour le plugin Vite cspTransform.
//
// Objectif : garantir que les tokens localhost de index.html sont
// réécrits selon les variables d'env au build. Une régression silencieuse
// (regex ne matche plus) = CSP prod cassée = app inutilisable.

import { beforeEach, describe, expect, it, vi } from 'vitest';

// On mocke `vite.loadEnv` pour contrôler EXACTEMENT les vars vues par
// le plugin. Sans ce mock, loadEnv lit le fichier .env du projet, et
// les tests « pas de var définie » seraient pollués par la config dev.
let mockedEnv = {};
vi.mock('vite', () => ({
  loadEnv: () => mockedEnv,
}));

const { cspTransform } = await import('../../vite/plugins/cspTransform.js');

const SAMPLE_HTML = `<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Security-Policy" content="
    default-src 'self';
    img-src 'self' data: blob: http://127.0.0.1:8000 http://localhost:8000;
    connect-src 'self' http://127.0.0.1:8000 http://localhost:8000 ws://localhost:5173 ws://127.0.0.1:5173 https://*.pusher.com wss://*.pusher.com;
  " />
</head>
<body></body>
</html>`;

/**
 * Invoque les hooks plugin dans l'ordre que Vite utilise,
 * avec un env injecté via le mock vi.mock('vite').
 */
function runPlugin({ env = {}, mode = 'production', command = 'build' } = {}) {
  mockedEnv = env;
  const plugin = cspTransform();
  plugin.config({}, { mode });
  plugin.configResolved({ command });
  return plugin.transformIndexHtml(SAMPLE_HTML);
}

describe('cspTransform', () => {
  beforeEach(() => {
    mockedEnv = {};
  });

  it('remplace les tokens localhost par VITE_API_URL', () => {
    const out = runPlugin({
      env: { VITE_API_URL: 'https://api.racinebyganda.cm' },
    });
    expect(out).toContain('https://api.racinebyganda.cm');
    expect(out).not.toContain('http://127.0.0.1:8000');
    expect(out).not.toContain('http://localhost:8000');
  });

  it("strippe la trailing slash de VITE_API_URL", () => {
    const out = runPlugin({
      env: { VITE_API_URL: 'https://api.racinebyganda.cm/' },
    });
    expect(out).toContain('https://api.racinebyganda.cm');
    expect(out).not.toContain('https://api.racinebyganda.cm/;');
    expect(out).not.toContain('https://api.racinebyganda.cm/ ');
  });

  it('strippe les tokens HMR Vite en build', () => {
    const out = runPlugin({ command: 'build' });
    expect(out).not.toContain('ws://localhost:5173');
    expect(out).not.toContain('ws://127.0.0.1:5173');
  });

  it('conserve les tokens HMR Vite en dev/serve', () => {
    const out = runPlugin({ command: 'serve' });
    expect(out).toContain('ws://localhost:5173');
    expect(out).toContain('ws://127.0.0.1:5173');
  });

  it('remplace la wildcard Pusher cloud par le host Reverb (https)', () => {
    const out = runPlugin({
      env: {
        VITE_REVERB_HOST: 'ws.racinebyganda.cm',
        VITE_REVERB_PORT: '443',
        VITE_REVERB_SCHEME: 'https',
      },
    });
    expect(out).toContain('https://ws.racinebyganda.cm:443');
    expect(out).toContain('wss://ws.racinebyganda.cm:443');
    expect(out).not.toContain('*.pusher.com');
  });

  it('remplace la wildcard Pusher cloud par le host Reverb (http)', () => {
    const out = runPlugin({
      env: {
        VITE_REVERB_HOST: 'localhost',
        VITE_REVERB_PORT: '8080',
        VITE_REVERB_SCHEME: 'http',
      },
    });
    expect(out).toContain('http://localhost:8080');
    expect(out).toContain('ws://localhost:8080');
    expect(out).not.toContain('*.pusher.com');
  });

  it("laisse les wildcards Pusher si VITE_REVERB_HOST n'est pas défini", () => {
    const out = runPlugin({
      env: { VITE_API_URL: 'https://api.racinebyganda.cm' },
    });
    expect(out).toContain('*.pusher.com');
  });

  it("laisse le HTML inchangé si aucune var d'env n'est définie en serve", () => {
    const out = runPlugin({ command: 'serve' });
    // API origin default
    expect(out).toContain('http://127.0.0.1:8000 http://localhost:8000');
    // HMR conservée
    expect(out).toContain('ws://localhost:5173 ws://127.0.0.1:5173');
    // Pusher wildcards conservés (pas d'env Reverb)
    expect(out).toContain('*.pusher.com');
  });

  it("ne laisse aucun reliquat localhost dans une build prod complète", () => {
    const out = runPlugin({
      env: {
        VITE_API_URL: 'https://api.racinebyganda.cm',
        VITE_REVERB_HOST: 'ws.racinebyganda.cm',
        VITE_REVERB_PORT: '443',
        VITE_REVERB_SCHEME: 'https',
      },
      command: 'build',
    });
    // Aucune trace de localhost ni 127.0.0.1
    expect(out).not.toMatch(/localhost:(8000|5173)/);
    expect(out).not.toMatch(/127\.0\.0\.1:(8000|5173)/);
    expect(out).not.toContain('*.pusher.com');
  });
});
