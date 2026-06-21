import { PosApiClient } from '../api/posClient';

/**
 * ApiService — High-level API service wrapping PosApiClient.
 *
 * Provides named methods for every POS backend endpoint, with built-in
 * token management and offline awareness. Designed to be used by Pinia
 * stores and Vue components alike.
 *
 * Usage:
 *   import { createApiService } from '../services/apiService';
 *   const api = createApiService(authStore);
 *   const products = await api.getProducts({ search: 'shirt' });
 *
 * The service delegates all HTTP work to PosApiClient (src/api/posClient.js)
 * so that 401 retry, 429 backoff, and offline detection remain centralized.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────────────────────────────────────

const STORAGE_API_BASE_URL = 'pos_api_base_url';
const DEFAULT_BASE_URL = 'https://racinebyganda.com';

/**
 * Read the configured API base URL.
 * Priority: localStorage override > VITE_API_URL env > hardcoded default.
 *
 * Note: electron-store is NOT used because the renderer process already
 * persists settings via localStorage (same pattern as auth.js). If native
 * persistence is needed later, an IPC channel can be added in preload.js.
 */
export function getApiBaseUrl() {
  return (
    localStorage.getItem(STORAGE_API_BASE_URL) ||
    import.meta.env.VITE_API_URL ||
    DEFAULT_BASE_URL
  );
}

/**
 * Persist a custom API base URL (e.g. from a Settings screen).
 */
export function setApiBaseUrl(url) {
  if (url) {
    localStorage.setItem(STORAGE_API_BASE_URL, url);
  } else {
    localStorage.removeItem(STORAGE_API_BASE_URL);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Service Class
// ─────────────────────────────────────────────────────────────────────────────

class ApiService {
  /**
   * @param {object} authStore — Pinia auth store instance (useAuthStore())
   */
  constructor(authStore) {
    this._auth = authStore;
    this._client = new PosApiClient(
      () => this._auth.token,
      (t) => {
        this._auth.token = t;
        this._auth.isAuthenticated = !!t;
        this._auth.persist();
      },
      (offline) => {
        this._auth.offline = offline;
      },
      () => this._auth.operatorToken,
    );
  }

  // ────────────────────────────────────────────────────────────
  // Auth
  // ────────────────────────────────────────────────────────────

  /**
   * Authenticate an operator (email + password).
   * On success stores operator info and token in the auth store.
   *
   * @param {string} email
   * @param {string} password
   * @returns {Promise<object>} Backend response with operator + token
   */
  async login(email, password) {
    const res = await this._client.post('/api/pos/auth/operator/login', {
      email,
      password,
    });

    if (res?.success && res?.data?.operator && res?.data?.token) {
      this._auth.operator = res.data.operator;
      this._auth.operatorToken = res.data.token;
      this._auth.offline = false;
      this._auth.persist();
    }

    return res;
  }

  /**
   * Log out the current operator. Clears stored auth on both sides.
   */
  async logout() {
    try {
      await this._client.post('/api/pos/auth/operator/logout');
    } catch (e) {
      // API failure should not block local cleanup
      console.warn('[ApiService] Logout API call failed:', e);
    }

    this._auth.operator = null;
    this._auth.operatorToken = null;
    this._auth.token = null;
    this._auth.device = null;
    this._auth.isAuthenticated = false;
    this._auth.persist();
  }

  /**
   * Register the POS terminal device with the backend.
   *
   * @param {string} machineId — Stable UUID for this machine
   * @param {string} name — Human-readable device name
   * @returns {Promise<object>}
   */
  async registerDevice(machineId, name = 'POS Terminal') {
    const res = await this._client.post('/api/pos/register', {
      machine_id: machineId,
      name,
    });

    this._auth.token = res.data?.token || res.token;
    this._auth.device = res.data?.device || res.device || { machine_id: machineId, name };
    this._auth.isAuthenticated = !!this._auth.token;
    this._auth.persist();

    return res;
  }

  /**
   * Check whether we have a stored token (does not validate with backend).
   * For a full server-side check, use refreshToken().
   */
  isAuthenticated() {
    return !!this._auth.token && !!this._auth.operatorToken;
  }

  // ────────────────────────────────────────────────────────────
  // Products
  // ────────────────────────────────────────────────────────────

  /**
   * Fetch paginated product list.
   *
   * @param {object} [params]
   * @param {string} [params.search] — Text search filter
   * @param {number} [params.page]   — Page number (default 1)
   * @param {number} [params.category_id] — Filter by category
   * @returns {Promise<object>}
   */
  async getProducts(params = {}) {
    return this._client.get('/api/pos/products', params);
  }

  /**
   * Server-side product search (dedicated endpoint).
   *
   * @param {string} query — Search term
   * @returns {Promise<object>}
   */
  async searchProducts(query) {
    return this._client.get('/api/pos/products/search', { q: query });
  }

  /**
   * Fetch product categories.
   *
   * @returns {Promise<object>}
   */
  async getCategories() {
    return this._client.get('/api/pos/products/categories');
  }

  // ────────────────────────────────────────────────────────────
  // Orders / Sales
  // ────────────────────────────────────────────────────────────

  /**
   * Create a new POS sale / order.
   *
   * @param {object} payload
   * @param {Array}  payload.items           — [{ product_id, quantity }]
   * @param {string} payload.payment_method  — 'cash' | 'card' | 'mobile_money'
   * @param {number} payload.total_amount
   * @param {string} [payload.monetbil_ref]
   * @param {string} [payload.stripe_ref]
   * @param {string} [payload.offline_id]    — UUID of the offline-queued sale
   * @param {number} [payload.session_id]
   * @param {string} [idempotencyKey]        — Idempotency key for dedup
   * @returns {Promise<object>}
   */
  async createOrder(payload, idempotencyKey = null) {
    const key = idempotencyKey || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
    return this._client.post('/api/pos/sales', payload, key);
  }

  /**
   * Fetch paginated list of orders/sales for the current session/operator.
   *
   * @param {number} [page=1]
   * @returns {Promise<object>}
   */
  async getOrders(page = 1) {
    return this._client.get('/api/pos/sales', { page });
  }

  /**
   * Cancel a sale.
   *
   * @param {string|number} saleId
   * @param {string} reason
   * @returns {Promise<object>}
   */
  async cancelOrder(saleId, reason) {
    return this._client.post(`/api/pos/sales/${saleId}/cancel`, { reason });
  }

  // ────────────────────────────────────────────────────────────
  // Offline Sync
  // ────────────────────────────────────────────────────────────

  /**
   * Bulk-upload offline-queued sales to the backend.
   *
   * @param {Array} orders — Array of sale payloads with uuid + idempotency_key
   * @returns {Promise<object>} Backend response with synced/conflicts
   */
  async syncOfflineOrders(orders) {
    const deviceId = this._auth.device?.machine_id || 'electron-pos';
    return this._client.post('/api/pos/offline/sync', {
      device_id: deviceId,
      sales: orders,
    });
  }

  /**
   * Check server-side offline queue status.
   *
   * @returns {Promise<object>}
   */
  async getOfflineStatus() {
    return this._client.get('/api/pos/offline/status');
  }

  /**
   * Flush the server-side offline queue.
   *
   * @returns {Promise<object>}
   */
  async flushOfflineQueue() {
    return this._client.post('/api/pos/offline/queue/flush');
  }

  /**
   * Resolve a sync conflict.
   *
   * @param {string} saleUuid
   * @param {'force_apply'|'discard'} resolution
   * @returns {Promise<object>}
   */
  async resolveConflict(saleUuid, resolution) {
    return this._client.post('/api/pos/offline/conflict/resolve', {
      sale_uuid: saleUuid,
      resolution,
    });
  }

  // ────────────────────────────────────────────────────────────
  // Sessions
  // ────────────────────────────────────────────────────────────

  /**
   * Open a new POS session.
   *
   * @param {number} openingCash
   * @returns {Promise<object>}
   */
  async openSession(openingCash) {
    const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
    return this._client.post('/api/pos/sessions/open', { opening_cash: openingCash }, key);
  }

  /**
   * Get the current active session for this device/operator.
   *
   * @returns {Promise<object>}
   */
  async getCurrentSession() {
    return this._client.get('/api/pos/sessions/current');
  }

  /**
   * Prepare session closing (fetch expected totals, summary).
   *
   * @param {string|number} sessionId
   * @returns {Promise<object>}
   */
  async prepareClose(sessionId) {
    return this._client.get(`/api/pos/sessions/${sessionId}/prepare-close`);
  }

  /**
   * Close a POS session.
   *
   * @param {string|number} sessionId
   * @param {number} closingCash
   * @param {string} [notes]
   * @returns {Promise<object>}
   */
  async closeSession(sessionId, closingCash, notes = null) {
    const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
    return this._client.post(`/api/pos/sessions/${sessionId}/close`, { closing_cash: closingCash, notes }, key);
  }

  /**
   * Get the Z-report for a session.
   *
   * @param {string|number} sessionId
   * @returns {Promise<object>}
   */
  async getZReport(sessionId) {
    return this._client.get(`/api/pos/sessions/${sessionId}/z-report`);
  }

  /**
   * Fetch sales for a specific session.
   *
   * @param {string|number} sessionId
   * @returns {Promise<object>}
   */
  async getSessionSales(sessionId) {
    return this._client.get(`/api/pos/sessions/${sessionId}/sales`);
  }

  // ────────────────────────────────────────────────────────────
  // Payments
  // ────────────────────────────────────────────────────────────

  /**
   * Confirm a card payment after terminal processing.
   *
   * @param {string|number} paymentId
   * @param {string} [transactionId]
   * @param {string} [receiptNumber]
   * @returns {Promise<object>}
   */
  async confirmCardPayment(paymentId, transactionId = null, receiptNumber = null) {
    return this._client.post(`/api/pos/payments/${paymentId}/confirm-card`, {
      transaction_id: transactionId,
      receipt_number: receiptNumber,
    });
  }

  /**
   * Poll payment status until it transitions from 'pending'.
   *
   * @param {string|number} paymentId
   * @param {number} [maxAttempts=20]
   * @param {number} [intervalMs=3000]
   * @returns {Promise<object>}
   */
  async pollPaymentStatus(paymentId, maxAttempts = 20, intervalMs = 3000) {
    for (let i = 0; i < maxAttempts; i += 1) {
      const res = await this._client.get(`/api/pos/payments/${paymentId}/status`);
      const status = res.data?.payment?.status;
      if (status && status !== 'pending') return res;
      await new Promise((r) => setTimeout(r, intervalMs));
    }
    throw new Error('PAYMENT_TIMEOUT');
  }

  // ────────────────────────────────────────────────────────────
  // Monetbil
  // ────────────────────────────────────────────────────────────

  /**
   * Initialise un paiement Monetbil et récupère l'URL de paiement.
   *
   * @param {number} amount
   * @param {string} currency
   * @param {string} [phone]
   * @returns {Promise<object>}
   */
  async initMonetbil(amount, currency = 'XAF', phone = null) {
    return this._client.post('/api/pos/payments/monetbil/init', {
      amount,
      currency,
      phone,
    });
  }
  // ----------------------------------------------------------
  // Stripe
  // ----------------------------------------------------------

  /**
   * Cree un PaymentIntent Stripe cote backend.
   *
   * @param {number} amount
   * @param {string} currency
   * @returns {Promise<object>}
   */
  async createStripeIntent(amount, currency = 'XAF') {
    return this._client.post('/api/pos/payments/stripe-intent', {
      amount,
      currency,
    });
  }


  // ────────────────────────────────────────────────────────────
  // Coupons
  // ────────────────────────────────────────────────────────────

  /**
   * Validate a coupon code.
   *
   * @param {string} code
   * @param {number} amount
   * @returns {Promise<object>}
   */
  async validateCoupon(code, amount) {
    return this._client.get('/api/pos/coupons/validate', { code, amount });
  }

  // ────────────────────────────────────────────────────────────
  // Phantom Session
  // ────────────────────────────────────────────────────────────

  /**
   * Check for a phantom/abandoned session.
   */
  async checkPhantomSession(operateurId, machineId, machineName) {
    return this._client.post('/api/pos/session/check', {
      operateur_id: operateurId,
      machine_id: machineId,
      machine_name: machineName,
    });
  }

  /**
   * Resume a phantom session.
   */
  async resumePhantomSession(sessionId, operateurId, machineId, machineName) {
    return this._client.post('/api/pos/session/resume', {
      session_id: sessionId,
      operateur_id: operateurId,
      machine_id: machineId,
      machine_name: machineName,
    });
  }

  /**
   * Force-close an old session and open a new one.
   */
  async forceCloseAndNewSession(sessionId, operateurId, machineId, machineName, openingCash) {
    return this._client.post('/api/pos/session/force-close', {
      session_id: sessionId,
      operateur_id: operateurId,
      machine_id: machineId,
      machine_name: machineName,
      opening_cash: openingCash,
    });
  }

  /**
   * Send a heartbeat for the current session.
   */
  async sendHeartbeat(sessionId, totalVentes, nombreTickets, panierSnapshot = []) {
    return this._client.post('/api/pos/session/heartbeat', {
      session_id: sessionId,
      total_ventes: totalVentes,
      nombre_tickets: nombreTickets,
      panier_snapshot: panierSnapshot,
    });
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Factory
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Create a new ApiService bound to the given auth store.
 *
 * @param {object} authStore — Pinia auth store (useAuthStore())
 * @returns {ApiService}
 */
export function createApiService(authStore) {
  return new ApiService(authStore);
}

export default ApiService;
