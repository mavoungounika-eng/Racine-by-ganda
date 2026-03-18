import { defineStore } from 'pinia';
import { PosApiClient } from '../api/posClient';

const STORAGE_TOKEN = 'pos_token';
const STORAGE_DEVICE = 'pos_device';
const STORAGE_OPERATOR = 'pos_operator';
const STORAGE_OPERATOR_TOKEN = 'pos_operator_token';
const STORAGE_MACHINE_ID = 'pos_machine_id';
const STORAGE_TERMINAL_NAME = 'pos_terminal_name';

function generateMachineId() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
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
  actions: {
    client() {
      return new PosApiClient(
        () => this.token,
        (t) => {
          this.token = t;
          this.isAuthenticated = !!t;
          this.persist();
        },
        (offline) => {
          this.offline = offline;
        },
        () => this.operatorToken,
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
    getOrCreateMachineId() {
      let machineId = localStorage.getItem(STORAGE_MACHINE_ID);
      if (!machineId) {
        machineId = generateMachineId();
        localStorage.setItem(STORAGE_MACHINE_ID, machineId);
      }
      return machineId;
    },
    getOrCreateTerminalName(machineId) {
      let terminalName = localStorage.getItem(STORAGE_TERMINAL_NAME);
      if (!terminalName) {
        terminalName = `POS-${machineId.slice(0, 8)}`;
        localStorage.setItem(STORAGE_TERMINAL_NAME, terminalName);
      }
      return terminalName;
    },
    async ensureTerminalRegistered() {
      if (this.token) return { success: true, alreadyRegistered: true };

      let machineId = this.getOrCreateMachineId();
      let terminalName = this.getOrCreateTerminalName(machineId);

      try {
        return await this.register(machineId, terminalName);
      } catch (e) {
        const status = e?.response?.status;

        // If machine_id already exists but token was lost, regenerate once.
        if (status === 422) {
          machineId = generateMachineId();
          terminalName = `POS-${machineId.slice(0, 8)}`;
          localStorage.setItem(STORAGE_MACHINE_ID, machineId);
          localStorage.setItem(STORAGE_TERMINAL_NAME, terminalName);
          return await this.register(machineId, terminalName);
        }

        throw e;
      }
    },
    async register(machineId, name) {
      const res = await this.client().post('/api/pos/register', { machine_id: machineId, name });
      this.token = res.data?.token || res.token || null;
      this.device = res.data || { machine_id: machineId, name };
      this.isAuthenticated = !!this.token;
      this.persist();
      return res;
    },
    async login(email, password) {
      const res = await this.client().post('/api/pos/auth/operator/login', { email, password });

      // PosApiClient returns the API envelope body directly
      // shape: { success: true, data: { operator, token }, ... }
      if (res.success && res.data?.operator && res.data?.token) {
        this.operator = res.data.operator;
        this.operatorToken = res.data.token;
        this.persist();
        return res;
      }

      throw new Error(res.error?.message || 'Login failed');
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
  },
});
