import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';
import { useSessionStore } from './session';

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    total: 0,
    paymentMethod: 'cash',
    lastSale: null,
    lastPayment: null,
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
    addItem(product) {
      const existing = this.items.find(i => i.product_id === product.id);
      if (existing) existing.quantity += 1;
      else this.items.push({ product_id: product.id, name: product.name, price: product.price, quantity: 1 });
      this.recalculate();
    },
    removeItem(productId) {
      this.items = this.items.filter(i => i.product_id !== productId);
      this.recalculate();
    },
    updateQuantity(productId, qty) {
      const item = this.items.find(i => i.product_id === productId);
      if (item) item.quantity = qty;
      this.recalculate();
    },
    clearCart() {
      this.items = [];
      this.total = 0;
    },
    recalculate() {
      this.total = this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
    },
    async createSale(paymentMethod = null) {
      if (paymentMethod) this.paymentMethod = paymentMethod;
      const idempotencyKey = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      const sessionStore = useSessionStore();
      const saleData = {
        items: this.items.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
        payment_method: this.paymentMethod,
        total_amount: this.total,
        session_id: sessionStore.currentSession?.id || null,
      };

      try {
        const res = await this.client().post('/api/pos/sales', saleData, idempotencyKey);
        this.lastSale = res.data?.sale || null;
        this.lastPayment = res.data?.sale?.payment || null;
        this.clearCart();
        return { success: true, offline: false, sale: res.data?.sale };
      } catch (error) {
        if (error.isOffline || !error.response || error.response.status >= 500) {
          const { useOfflineStore } = await import('./offline.js');
          const offlineStore = useOfflineStore();
          await offlineStore.saveLocalSale(saleData, idempotencyKey);
          this.clearCart();
          return { success: true, offline: true, queued: true };
        }
        throw error;
      }
    },
    async confirmCardPayment(paymentId, transactionId = null, receiptNumber = null) {
      return this.client().post(`/api/pos/payments/${paymentId}/confirm-card`, {
        transaction_id: transactionId,
        receipt_number: receiptNumber,
      });
    },
    async pollPaymentStatus(paymentId, maxAttempts = 20, intervalMs = 3000) {
      for (let i = 0; i < maxAttempts; i += 1) {
        const res = await this.client().get(`/api/pos/payments/${paymentId}/status`);
        const status = res.data?.payment?.status;
        if (status && status !== 'pending') return res;
        await new Promise(r => setTimeout(r, intervalMs));
      }
      throw new Error('PAYMENT_TIMEOUT');
    },
    async cancelSale(saleId, reason) {
      return this.client().post(`/api/pos/sales/${saleId}/cancel`, { reason });
    },
  },
  getters: {
    displayTotal(state) {
      return new Intl.NumberFormat('fr-FR').format(Math.round(state.total)) + ' FCFA';
    }
  }
});

