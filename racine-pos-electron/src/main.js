import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import router from './router';
import fr from './i18n/fr';
import en from './i18n/en';
import { useStockStore } from './stores/stock';

const pinia = createPinia();

const i18n = createI18n({
  legacy: false,
  locale: 'fr',
  fallbackLocale: 'en',
  messages: { fr, en },
});

import { useStockStore } from './stores/stock';

const app = createApp(App);
app.use(pinia);
app.use(router);
app.use(i18n);

// Initialisation Stock (si déjà connecté)
const stockStore = useStockStore();
if (localStorage.getItem('pos_token')) {
  stockStore.init();
}

app.mount('#app');
