import axios from 'axios';

const BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

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

      if (!err.response) {
        this.onOffline?.(true);
        err.isOffline = true;
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
    } catch (err) {
      // Si le device n'existe plus en DB (401 "Device not registered" ou autre),
      // effacer le token invalide pour forcer un re-enregistrement au prochain passage sur /login.
      if (err.response?.status === 401 || err.response?.status === 403) {
        this.setToken?.(null);
      }
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
