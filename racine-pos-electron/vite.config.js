/// <reference types="vitest" />
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { cspTransform } from './vite/plugins/cspTransform.js';

export default defineConfig({
  plugins: [vue(), cspTransform()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  base: './',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
  server: {
    port: 8080,
    strictPort: true,
  },
  // ── Vitest ──────────────────────────────────────────────────────────
  // On ne spawn pas jsdom : les tests unitaires ciblent des modules
  // qui travaillent sur IndexedDB (remplacé par fake-indexeddb) et des
  // helpers purs. Garder l'environnement `node` réduit le temps de boot.
  //
  // Vitest 4 : poolOptions a été dé-nesté → options directes.
  test: {
    environment: 'node',
    setupFiles: ['./tests/setup.js'],
    include: ['tests/**/*.test.js'],
    // IndexedDB fake utilise un factory global : un seul fork évite les races.
    pool: 'forks',
    fileParallelism: false,
    testTimeout: 10000,
  },
});
