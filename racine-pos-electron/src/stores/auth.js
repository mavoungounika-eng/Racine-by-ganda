import { defineStore } from 'pinia';
import { PosApiClient } from '../api/posClient';
import { createApiService } from '../services/apiService';
import { refreshEchoAuth } from '../plugins/echo.js';
import { saveOfflineAuth, verifyOfflineAuth, clearOfflineAuth } from './offlineCache.js';

const STORAGE_TOKEN = 'pos_token';
const STORAGE_DEVICE = 'pos_device';
const STORAGE_OPERATOR = 'pos_operator';
const STORAGE_OPERATOR_TOKEN = 'pos_operator_token';
const STORAGE_MACHINE_ID = 'pos_machine_id';

function generateMachineId() {
  if (crypto.randomUUID) return crypto.randomUUID();
  return `${[1e7]+-1e3+-4e3+-8e3+-1e11}`.replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
}

async function getStableMachineId() {
  try {
    const fromDisk = await window.electron?.getMachineId?.();
    if (fromDisk) {
      localStorage.setItem(STORAGE_MACHINE_ID, fromDisk);
      return fromDisk;
    }
  } catch (error) {
    console.warn('[POS] machine_id disque indisponible, fallback localStorage:', error);
  }

  let machineId = localStorage.getItem(STORAGE_MACHINE_ID);
  if (!machineId) {
    machineId = generateMachineId();
    localStorage.setItem(STORAGE_MACHINE_ID, machineId);
  }
  return machineId;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    device: null,
    operator: null,
    operatorToken: null,
    isAuthenticated: false,
    offline: false,
  }),
  getters: {
    /**
     * Operator / creator info from the login response.
     * Exposes id, name, email, shop_name when available.
     */
    creatorInfo: (state) => {
      if (!state.operator) return null;
      return {
        id: state.operator.id ?? null,
        name: state.operator.name ?? null,
        email: state.operator.email ?? null,
        shop_name: state.operator.shop_name ?? state.operator.boutique ?? null,
      };
    },
    /** True when both device and operator tokens are present. */
    isFullyAuthenticated: (state) => !!state.token && !!state.operatorToken,
  },
  actions: {
    /**
     * Return a high-level ApiService instance bound to this auth store.
     * Prefer this over client() for new code — apiService() provides
     * named methods instead of raw HTTP verbs.
     */
    apiService() {
      return createApiService(this);
    },
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
      else localStorage.removeItem(STORAGE_TOKEN);
      if (this.device) localStorage.setItem(STORAGE_DEVICE, JSON.stringify(this.device));
      else localStorage.removeItem(STORAGE_DEVICE);
      if (this.operator) localStorage.setItem(STORAGE_OPERATOR, JSON.stringify(this.operator));
      else localStorage.removeItem(STORAGE_OPERATOR);
      if (this.operatorToken) localStorage.setItem(STORAGE_OPERATOR_TOKEN, this.operatorToken);
      else localStorage.removeItem(STORAGE_OPERATOR_TOKEN);
    },
    async register(machineId, name) {
      const res = await this.client().post('/api/pos/register', { machine_id: machineId, name });
      this.token = res.data?.token || res.token;
      this.device = res.data?.device || res.device || { machine_id: machineId, name };
      this.isAuthenticated = !!this.token;
      this.persist();
      return res;
    },
    async login(email, password, options = {}) {
      try {
        if (options.isOffline) {
          const cached = await verifyOfflineAuth(email, password);
          if (!cached) {
            throw new Error('Connexion hors ligne impossible: identifiants non disponibles sur ce terminal.');
          }

          this.operator = cached.operator;
          this.operatorToken = cached.token;
          if (cached.deviceToken) this.token = cached.deviceToken;
          this.offline = true;
          this.persist();
          return { success: true, offline: true, data: cached };
        }

        const res = await this.client().post('/api/pos/auth/operator/login', { email, password });

        if (res?.success && res?.data?.operator && res?.data?.token) {
          this.operator = res.data.operator;
          this.operatorToken = res.data.token;
          this.offline = false;
          this.persist();
          // Mise en cache pour usage offline
          await saveOfflineAuth(email, password, res.data.operator, res.data.token, this.token);
          try {
            refreshEchoAuth();
          } catch (e) { /* echo indisponible — POS continue */ }
          return res;
        }
        throw new Error(res?.error?.message || 'Login failed');
      } catch (e) {
        // Fallback offline si backend inaccessible ou rate-limité
        const isNetworkError = !e.response || e.response?.status === 429 || e.response?.status >= 500;
        if (isNetworkError) {
          const cached = await verifyOfflineAuth(email, password);
          if (cached) {
            this.operator   = cached.operator;
            this.operatorToken = cached.token;
            if (cached.deviceToken) this.token = cached.deviceToken;
            this.offline    = true;
            this.persist();
            console.warn('[POS] Mode offline activé — credentials depuis cache');
            return { success: true, offline: true, data: cached };
          }
        }
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
      await clearOfflineAuth();
    },
    async refreshToken() {
      return this.client().refreshToken();
    },
    async ensureTerminalRegistered(options = {}) {
      if (options.isOffline) {
        this.offline = true;
        return this.device || null;
      }

      if (this.token && this.device) {
        // Valider que le token est encore accepté par le backend
        const valid = await this.client().refreshToken();
        if (valid || this.token) return this.device; // valide ou erreur réseau (offline)
        // Token révoqué (401) → forcer re-registration
        this.device = null;
        localStorage.removeItem(STORAGE_DEVICE);
      }

      try {
        const machineId = await getStableMachineId();
        const device = await this.register(machineId, 'POS Terminal');
        return device;
      } catch (error) {
        if (!error.response && this.token && this.device) {
          this.offline = true;
          return this.device;
        }

        console.error('Terminal registration failed:', error);
        throw error;
      }
    },
  },
});
