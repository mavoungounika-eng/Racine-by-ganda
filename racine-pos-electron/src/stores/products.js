import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';

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
      const res = await this.client().get('/api/pos/products', filters);
      this.items = res.data?.data || [];
      this.loading = false;
      return res;
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
