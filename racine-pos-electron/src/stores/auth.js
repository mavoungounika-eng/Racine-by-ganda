import { defineStore } from 'pinia';
import { PosApiClient } from '../api/posClient';

const STORAGE_TOKEN = 'pos_token';
const STORAGE_DEVICE = 'pos_device';
const STORAGE_OPERATOR = 'pos_operator';
const STORAGE_OPERATOR_TOKEN = 'pos_operator_token';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    device: null,
    operator: null,
    operatorToken: null,
    isAuthenticated: false,
    offline: false,
  }),
  actions: {
    client() {
      return new PosApiClient(
        () => this.token,
        (t) => { this.token = t; this.isAuthenticated = !!t; this.persist(); },
        (offline) => { this.offline = offline; },
        () => this.operatorToken
      );
    },
    loadFromStorage() {
      const token = localStorage.getItem(STORAGE_TOKEN);
      const device = localStorage.getItem(STORAGE_DEVICE);
      const operator = localStorage.getItem(STORAGE_OPERATOR);
      const operatorToken = localStorage.getItem(STORAGE_OPERATOR_TOKEN);
      this.token = token || null;
      this.device = device ? JSON.parse(device) : null;
      this.operator = operator ? JSON.parse(operator) : null;
      this.operatorToken = operatorToken || null;
      this.isAuthenticated = !!this.token;
    },
    persist() {
      if (this.token) localStorage.setItem(STORAGE_TOKEN, this.token);
      if (this.device) localStorage.setItem(STORAGE_DEVICE, JSON.stringify(this.device));
      if (this.operator) localStorage.setItem(STORAGE_OPERATOR, JSON.stringify(this.operator));
      if (this.operatorToken) localStorage.setItem(STORAGE_OPERATOR_TOKEN, this.operatorToken);
    },
    async register(machineId, name) {
      const res = await this.client().post('/api/pos/register', { machine_id: machineId, name });
      this.token = res.data?.token || res.token;
      this.device = res.data?.device || res.device || { machine_id: machineId, name };
      this.isAuthenticated = !!this.token;
      this.persist();
      return res;
    },
    async login(email, password) {
      try {
        // PosApiClient.post() retourne directement le BODY JSON du backend
        // (axios `res.data`), pas la réponse axios enveloppée. Donc ici `res`
        // vaut déjà `{ success, data: { operator, token }, error, meta }`.
        // On déstructure en conséquence — l'ancienne version lisait
        // `res.data.success` / `res.data.data.operator` (double-unwrapping)
        // et jetait systématiquement "Login failed" alors que le back répondait 200.
        const res = await this.client().post('/api/pos/auth/operator/login', { email, password });

        if (res?.success && res?.data?.operator && res?.data?.token) {
          this.operator = res.data.operator;
          this.operatorToken = res.data.token;
          this.persist();
          // Rafraîchir les headers Echo/WebSocket avec le nouveau token opérateur
          try {
            const { refreshEchoAuth } = await import('../plugins/echo.js');
            refreshEchoAuth();
          } catch (e) { /* echo indisponible — POS continue */ }
          return res;
        }
        throw new Error(res?.error?.message || 'Login failed');
      } catch (e) {
        console.error('Operator login error:', e);
        throw e;
      }
    },
    async logout() {
      if (this.operatorToken) {
        try {
          await this.client().post('/api/pos/auth/operator/logout');
        } catch (e) {
          console.warn('Logout API failed:', e);
        }
      }

      this.token = null;
      this.device = null;
      this.operator = null;
      this.operatorToken = null;
      this.isAuthenticated = false;
      localStorage.removeItem(STORAGE_TOKEN);
      localStorage.removeItem(STORAGE_DEVICE);
      localStorage.removeItem(STORAGE_OPERATOR);
      localStorage.removeItem(STORAGE_OPERATOR_TOKEN);
    },
    async refreshToken() {
      return this.client().refreshToken();
    },
    async ensureTerminalRegistered() {
      // If we already have a device token, we're registered
      if (this.token && this.device) {
        return this.device;
      }

      // Try to register a new terminal
      try {
        const machineId = crypto.randomUUID ? crypto.randomUUID() : `${[1e7]+-1e3+-4e3+-8e3+-1e11}`.replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
        const device = await this.register(machineId, 'POS Terminal');
        return device;
      } catch (error) {
        console.error('Terminal registration failed:', error);
        throw error;
      }
    },
  },
});
