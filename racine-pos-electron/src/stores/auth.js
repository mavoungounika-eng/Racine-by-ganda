import { defineStore } from 'pinia';
import { createApiService } from '../services/apiService';
import { refreshEchoAuth } from '../plugins/echo.js';
import { saveOfflineAuth, verifyOfflineAuth, clearOfflineAuth } from './offlineCache.js';

const STORAGE_TOKEN = 'pos_token';
const STORAGE_DEVICE = 'pos_device';
const STORAGE_OPERATOR = 'pos_operator';
const STORAGE_OPERATOR_TOKEN = 'pos_operator_token';
const STORAGE_MACHINE_ID = 'pos_machine_id';
const STORAGE_DEVICE_INFO = 'pos_device_info';

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
    deviceInfo: null,
  }),
  getters: {
    creatorInfo: (state) => {
      if (!state.operator) return null;
      return {
        id: state.operator.id ?? null,
        name: state.operator.name ?? null,
        email: state.operator.email ?? null,
        shop_name: state.operator.shop_name ?? state.operator.boutique ?? null,
      };
    },
    isFullyAuthenticated: (state) => !!state.token && !!state.operatorToken,
  },
  actions: {
    apiService() {
      return createApiService(this);
    },
    loadFromStorage() {
      const token = localStorage.getItem(STORAGE_TOKEN);
      const device = localStorage.getItem(STORAGE_DEVICE);
      const operator = localStorage.getItem(STORAGE_OPERATOR);
      const operatorToken = localStorage.getItem(STORAGE_OPERATOR_TOKEN);
      const deviceInfo = localStorage.getItem(STORAGE_DEVICE_INFO);
      this.token = token || null;
      this.device = device ? JSON.parse(device) : null;
      this.operator = operator ? JSON.parse(operator) : null;
      this.operatorToken = operatorToken || null;
      this.deviceInfo = deviceInfo ? JSON.parse(deviceInfo) : null;
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
      if (this.deviceInfo) localStorage.setItem(STORAGE_DEVICE_INFO, JSON.stringify(this.deviceInfo));
      else localStorage.removeItem(STORAGE_DEVICE_INFO);
    },
    async register(machineId, name) {
      const api = this.apiService();
      const res = await api.registerDevice(machineId, name);
      this.deviceInfo = { name, machine_id: machineId, status: 'registered' };
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

        const api = this.apiService();
        const res = await api.login(email, password);

        if (res?.success && res?.data?.operator && res?.data?.token) {
          this.offline = false;
          this.persist();
          await saveOfflineAuth(email, password, res.data.operator, res.data.token, this.token);
          try {
            refreshEchoAuth();
          } catch (e) { /* echo indisponible — POS continue */ }
          return res;
        }
        throw new Error(res?.error?.message || 'Login failed');
      } catch (e) {
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
      const api = this.apiService();
      await api.logout();

      this.deviceInfo = null;
      localStorage.removeItem(STORAGE_DEVICE_INFO);
      await clearOfflineAuth();
    },
    async refreshToken() {
      // ApiService delegates to PosApiClient.refreshToken() internally.
      // Direct refresh is handled by PosApiClient's 401 retry logic.
      // This action is kept for backward compatibility with callers.
      try {
        const api = this.apiService();
        const res = await api.getOfflineStatus();
        return !!res;
      } catch (e) {
        if (e.response?.status === 401 || e.response?.status === 403) {
          this.token = null;
          this.isAuthenticated = false;
          this.persist();
          return false;
        }
        // Network error — token may still be valid
        return !!this.token;
      }
    },
    async ensureTerminalRegistered(options = {}) {
      if (options.isOffline) {
        this.offline = true;
        return this.device || null;
      }

      if (this.token && this.device) {
        const valid = await this.refreshToken();
        if (valid || this.token) return this.device;
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
