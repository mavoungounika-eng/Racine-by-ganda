import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { createApiService } from '../services/apiService';
import { PosApiClient } from '../api/posClient';
import { saveOfflineProducts, loadOfflineProducts } from './offlineCache.js';

export const useProductsStore = defineStore('products', {
  state: () => ({
    items: [],
    categories: [],
    loading: false,
    searchQuery: '',
    lastFetchedAt: null,
  }),
  getters: {
    /**
     * Client-side filtered products when a search query is active.
     * Falls back to full list when no query is set.
     */
    filteredItems: (state) => {
      if (!state.searchQuery) return state.items;
      const q = state.searchQuery.toLowerCase();
      return state.items.filter(
        (p) =>
          (p.name && p.name.toLowerCase().includes(q)) ||
          (p.sku && p.sku.toLowerCase().includes(q)) ||
          (p.barcode && p.barcode.toLowerCase().includes(q)),
      );
    },
    /** True if products have been fetched at least once this session. */
    hasCachedProducts: (state) => state.items.length > 0,
  },
  actions: {
    /** @deprecated Use _apiService() for new code. Kept for backward compat. */
    client() {
      const auth = useAuthStore();
      return new PosApiClient(
        () => auth.token,
        (t) => { auth.token = t; auth.isAuthenticated = !!t; },
        (offline) => { auth.offline = offline; },
        () => auth.operatorToken,
      );
    },
    _apiService() {
      const auth = useAuthStore();
      return createApiService(auth);
    },
    /**
     * Fetch products from the backend with optional filters.
     * On network failure, transparently falls back to IndexedDB cache.
     *
     * @param {object} [filters] — { search, page, category_id, ... }
     */
    async fetchProducts(filters = {}) {
      this.loading = true;
      try {
        const api = this._apiService();
        const res = await api.getProducts(filters);
        this.items = res.data?.data || [];
        this.lastFetchedAt = new Date().toISOString();
        // Persist to IndexedDB for offline usage
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
    /**
     * Server-side product search via the dedicated search endpoint.
     * Also applies the query as a client-side filter on cached items.
     *
     * @param {string} query — Search term
     * @returns {Array} Search results
     */
    async searchProducts(query) {
      this.searchQuery = query || '';

      // If offline or empty query, rely on client-side filtering
      const auth = useAuthStore();
      if (!query || auth.offline) {
        return this.filteredItems;
      }

      try {
        const api = this._apiService();
        const res = await api.searchProducts(query);
        return res.data?.results || [];
      } catch (e) {
        // On network failure, fall back to client-side filter
        if (!e.response || e.response?.status >= 500) {
          console.warn('[POS] Recherche produits en mode offline (filtre local)');
          return this.filteredItems;
        }
        throw e;
      }
    },
    /**
     * Clear the active search filter.
     */
    clearSearch() {
      this.searchQuery = '';
    },
    /**
     * Fetch product categories from the backend.
     */
    async fetchCategories() {
      const api = this._apiService();
      const res = await api.getCategories();
      this.categories = res.data?.categories || [];
      return res;
    },
    /**
     * Force-load products from IndexedDB offline cache.
     * Useful during app init when network status is unknown.
     */
    async loadFromCache() {
      const cached = await loadOfflineProducts();
      if (cached.length) {
        this.items = cached;
      }
      return cached;
    },
  },
});
