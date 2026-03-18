import axios from 'axios';

const BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

// Sale endpoints that should be queued locally when offline
const SALE_ENDPOINTS = ['/api/pos/sales'];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function generateUUID() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export class PosApiClient {
  constructor(getToken, setToken, onOffline, getOperatorToken = null) {
    this.getToken = getToken;
    this.setToken = setToken;
    this.onOffline = onOffline;
    this.getOperatorToken = getOperatorToken;
    this.client = axios.create({
      baseURL: BASE_URL,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    });
  }

  async request(method, path, data = null, idempotencyKey = null, retry = true, params = null) {
    const headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    };

    const token = this.getToken?.();
    const operatorToken = this.getOperatorToken?.();

    if (token) headers.Authorization = `Bearer ${token}`;
    if (operatorToken) headers['X-Operator-Token'] = operatorToken;
    if (idempotencyKey) headers['X-Idempotency-Key'] = idempotencyKey;

    try {
      const res = await this.client.request({
        method,
        url: path,
        data,
        params,
        headers,
      });
      this.onOffline?.(false);
      return res.data;
    } catch (err) {
      if (err.response?.status === 401 && retry) {
        const refreshed = await this.refreshToken();
        if (refreshed) return this.request(method, path, data, idempotencyKey, false, params);
      }

      if (err.response?.status === 429 && retry) {
        await sleep(2000);
        return this.request(method, path, data, idempotencyKey, false, params);
      }

      // Network error (no response) - check if this is a sale POST that can be queued
      if (!err.response) {
        this.onOffline?.(true);
        err.isOffline = true;

        if (method === 'post' && SALE_ENDPOINTS.some((ep) => path.startsWith(ep)) && data) {
          // Transparently queue the sale and return a fake success response
          try {
            const { useOfflineStore } = await import('../stores/offline.js');
            const offlineStore = useOfflineStore();
            const uuid = data.uuid || generateUUID();
            await offlineStore.queueSale({ ...data, uuid }, idempotencyKey || uuid);
            // Return a response shape that callers can handle
            return {
              success: true,
              queued: true,
              offline: true,
              data: { queued: true, uuid },
            };
          } catch {
            // If queuing itself fails, fall through and throw
          }
        }
      }

      throw err;
    }
  }

  async refreshToken() {
    try {
      const token = this.getToken?.();
      if (!token) return false;
      const res = await this.client.post('/api/pos/auth/refresh', null, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const newToken = res.data?.data?.token || res.data?.token;
      if (newToken) {
        this.setToken?.(newToken);
        return true;
      }
      return false;
    } catch {
      return false;
    }
  }

  get(path, params = null) {
    return this.request('get', path, null, null, true, params);
  }

  post(path, data = null, idempotencyKey = null) {
    return this.request('post', path, data, idempotencyKey);
  }

  delete(path) {
    return this.request('delete', path);
  }
}
