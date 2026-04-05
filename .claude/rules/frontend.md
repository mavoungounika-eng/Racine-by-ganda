---
paths:
  - "resources/js/**"
  - "resources/views/**"
  - "vite.config.*"
---

# Règles — Frontend Vue 3 / Vite

## Stack frontend

- Vue 3 avec Composition API (script setup obligatoire)
- Vite 7 + laravel-vite-plugin
- Bootstrap 5 + Sass pour le style
- Axios pour les requêtes HTTP
- Laravel Echo + Pusher JS pour le temps réel

## Conventions Vue 3

- Toujours utiliser script setup — pas d'Options API
- Props typées avec defineProps<{ ... }>()
- Événements déclarés avec defineEmits
- Composables dans resources/js/composables/
- Un composant = un fichier, nommé en PascalCase
- Les appels API passent par des services dans resources/js/services/

## Conventions Axios

- Toujours inclure le token CSRF pour les requêtes mutantes
- axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
- Le token est récupéré depuis le meta tag csrf-token

## POS Electron

- Le code Electron est dans electron/ ou main.js à la racine
- Electron 28 — utiliser contextBridge et ipcRenderer pour la communication
- Ne JAMAIS utiliser nodeIntegration: true — utiliser preload.js
- Le POS doit fonctionner offline (pas de dépendance réseau pour les opérations de caisse)

## Conventions Vite

- Les assets Vue sont dans resources/js/
- Entry point principal : resources/js/app.js
- HMR activé en développement via composer run dev
