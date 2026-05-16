import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';
import { saveOfflineProducts, loadOfflineProducts } from './offlineCache.js';

export const useProductsStore = defineStore('products', {
  state: () => ({
    items: [],
    categories: [],
    loading: false,
  }),
  actions: {
    client() {
      const auth = useAuthStore();
      return new PosApiClient(
        () => auth.token,
        (t) => { auth.token = t; auth.isAuthenticated = !!t; },
        (offline) => { auth.offline = offline; },
        () => auth.operatorToken,
      );
    },
    async fetchProducts(filters = {}) {
      this.loading = true;
      try {
        const res = await this.client().get('/api/pos/products', filters);
        this.items = res.data?.data || [];
        // Mise en cache pour usage offline
        if (this.items.length) await saveOfflineProducts(this.items);
      } catch (e) {
        const auth = useAuthStore();
        if (auth.offline || !e.response || e.response?.status >= 500) {
          const cached = await loadOfflineProducts();
          if (cached.length) {
            this.items = cached;
            console.warn('[POS] Produits chargés depuis cache offline');
          }
        } else {
          throw e;
        }
      } finally {
        this.loading = false;
      }
    },
    async searchProducts(query) {
      const res = await this.client().get('/api/pos/products/search', { q: query });
      return res.data?.results || [];
    },
    async fetchCategories() {
      const res = await this.client().get('/api/pos/products/categories');
      this.categories = res.data?.categories || [];
      return res;
    },
  },
});
