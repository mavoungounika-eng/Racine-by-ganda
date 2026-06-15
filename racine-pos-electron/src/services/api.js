/**
 * ApiService — client API singleton pour le POS Electron.
 *
 * Contrat backend Laravel (préfixe /api/pos) :
 *
 *   POST /register                   → { success, data: { device, token } }
 *   POST /auth/operator/login        → { success, data: { operator, token } }
 *   POST /auth/refresh               → { success, data: { token } }
 *   GET  /offline/status             → { success, data: { ... } }
 *   GET  /products                   → { success, data: [...] }
 *   POST /orders                     → { success, data: { ... } }
 *   GET  /orders?page=N              → { success, data: { ... } }
 *   POST /sync                       → { success, data: { ... } }
 *
 * Auth double-couche :
 *   - Authorization: Bearer <device_jwt>   → identifie le terminal
 *   - X-Operator-Token: <sanctum_token>    → identifie l'opérateur connecté
 */

import axios from 'axios';

const KEY_DEVICE_JWT     = 'pos_device_jwt';
const KEY_DEVICE_ID      = 'pos_device_id';
const KEY_MACHINE_ID     = 'pos_machine_id';
const KEY_OPERATOR_TOKEN = 'pos_operator_token';
const KEY_OPERATOR       = 'pos_operator';

const BASE_URL   = (import.meta.env.VITE_API_URL || 'https://racinebyganda.com') + '/api/pos';
const TIMEOUT_MS = parseInt(import.meta.env.VITE_API_TIMEOUT || '10000', 10);

function lsGet(key) { try { return localStorage.getItem(key); } catch { return null; } }
function lsSet(key, value) { try { localStorage.setItem(key, value); } catch {} }
function lsDel(key) { try { localStorage.removeItem(key); } catch {} }

function getOrCreateMachineId() {
  let id = lsGet(KEY_MACHINE_ID);
  if (!id) {
    id = 'POS-' + Date.now().toString(36).toUpperCase() + '-' +
         Math.random().toString(36).substring(2, 8).toUpperCase();
    lsSet(KEY_MACHINE_ID, id);
  }
  return id;
}

class ApiServiceClass {
  constructor() {
    this.client = axios.create({
      baseURL: BASE_URL,
      timeout: TIMEOUT_MS,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    });

    this.client.interceptors.request.use((config) => {
      const deviceJwt     = lsGet(KEY_DEVICE_JWT);
      const operatorToken = lsGet(KEY_OPERATOR_TOKEN);
      if (deviceJwt)     config.headers['Authorization']   = `Bearer ${deviceJwt}`;
      if (operatorToken) config.headers['X-Operator-Token'] = operatorToken;
      return config;
    });

    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          this._clearOperator();
          try { window.dispatchEvent(new CustomEvent('pos:auth:expired')); } catch {}
        }
        if (!error.response) error.isOffline = true;
        return Promise.reject(error);
      }
    );
  }

  async _request(promise) {
    try {
      const res  = await promise;
      const body = res.data;
      if (body && typeof body === 'object' && 'success' in body) return body;
      return { success: true, data: body };
    } catch (error) {
      if (error.isOffline || !error.response) {
        return { success: false, offline: true, message: 'Serveur inaccessible — mode hors ligne.' };
      }
      const body = error.response?.data;
      return {
        success: false,
        status:  error.response.status,
        message: body?.error?.message || body?.message || `Erreur HTTP ${error.response.status}`,
      };
    }
  }

  _clearOperator() { lsDel(KEY_OPERATOR_TOKEN); lsDel(KEY_OPERATOR); }
  _clearDevice()   { lsDel(KEY_DEVICE_JWT);     lsDel(KEY_DEVICE_ID); }

  async registerDevice(name = 'Terminal POS') {
    const machine_id = getOrCreateMachineId();
    const result = await this._request(this.client.post('/register', { machine_id, name }));
    if (result.success && result.data?.token) {
      lsSet(KEY_DEVICE_JWT, result.data.token);
      lsSet(KEY_DEVICE_ID,  result.data.device?.id || '');
    }
    return result;
  }

  async loginOperator(email, password) {
    const result = await this._request(
      this.client.post('/auth/operator/login', { email, password })
    );
    if (result.success && result.data?.token) {
      lsSet(KEY_OPERATOR_TOKEN, result.data.token);
      lsSet(KEY_OPERATOR, JSON.stringify(result.data.operator || {}));
    }
    return result;
  }

  async refreshDeviceToken() {
    const result = await this._request(this.client.post('/auth/refresh'));
    if (result.success && result.data?.token) lsSet(KEY_DEVICE_JWT, result.data.token);
    return result;
  }

  async checkOnlineStatus() { return this._request(this.client.get('/offline/status')); }
  async getProducts()       { return this._request(this.client.get('/products')); }
  async getOrders(page = 1) { return this._request(this.client.get('/orders', { params: { page } })); }
  async createOrder(payload){ return this._request(this.client.post('/orders', payload)); }
  async syncOfflineOrders(orders) { return this._request(this.client.post('/sync', { orders })); }

  isDeviceRegistered()  { return !!lsGet(KEY_DEVICE_JWT); }
  isOperatorLoggedIn()  { return !!lsGet(KEY_OPERATOR_TOKEN); }
  getOperator()         { try { return JSON.parse(lsGet(KEY_OPERATOR) || 'null'); } catch { return null; } }
  logoutOperator()      { this._clearOperator(); return { success: true }; }
  resetDevice()         { this._clearDevice(); this._clearOperator(); lsDel(KEY_MACHINE_ID); return { success: true }; }
}

const ApiService = new ApiServiceClass();
export default ApiService;
export { ApiService };
