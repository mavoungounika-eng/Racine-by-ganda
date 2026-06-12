/**
 * ApiService — client API singleton pour le POS Electron.
 *
 * Consomme le backend Laravel sous `{baseURL}` (préfixe `/api/pos`) :
 *   - POST /login          → { success, data: { token, creator, stripe_publishable_key } }
 *   - GET  /products       → { success, data: [...] }
 *   - POST /orders         → { success, data: {...} }
 *   - GET  /orders?page=N  → { success, data: {...} }
 *   - POST /sync           → { success, data: {...} } (ventes offline avec offline_id)
 *
 * Auth : Bearer token Sanctum, injecté automatiquement par interceptor.
 * Persistance : localStorage (mécanisme déjà utilisé par le projet — cf.
 * src/stores/auth.js qui persiste pos_token / pos_device via localStorage).
 *
 * Toutes les méthodes réseau retournent `{ success, data?, message? }`
 * sans jamais throw pour les erreurs API attendues. Les erreurs réseau
 * (backend injoignable) sont normalisées avec `offline: true`.
 */

import axios from 'axios';

/** Clé localStorage sous laquelle sont persistés token + créateur + clé Stripe. */
const AUTH_STORAGE_KEY = 'auth';

/** Clé localStorage pour la baseURL configurée à chaud via setBaseUrl(). */
const BASE_URL_STORAGE_KEY = 'pos_api_base_url';

/**
 * ⚠️ PLACEHOLDER — remplacer par le domaine de production réel avant déploiement,
 * ou définir VITE_POS_API_URL dans le .env du projet Electron (prioritaire),
 * ou appeler ApiService.setBaseUrl('https://mondomaine.com/api/pos') à l'exécution.
 */
const DEFAULT_BASE_URL = 'https://TON_DOMAINE/api/pos';

const API_TIMEOUT = parseInt(import.meta.env.VITE_API_TIMEOUT || '10000', 10);

/**
 * Résout la baseURL effective, par ordre de priorité :
 * 1. Valeur persistée via setBaseUrl() (localStorage)
 * 2. Variable d'environnement Vite VITE_POS_API_URL
 * 3. Placeholder par défaut (à remplacer)
 * @returns {string} baseURL effective
 */
function resolveBaseUrl() {
  try {
    const stored = localStorage.getItem(BASE_URL_STORAGE_KEY);
    if (stored) return stored;
  } catch (e) {
    // localStorage indisponible (contexte non-browser) — fallback env/défaut
  }
  return import.meta.env.VITE_POS_API_URL || DEFAULT_BASE_URL;
}

class ApiServiceClass {
  constructor() {
    /** @type {{ token: string|null, creator: object|null, stripe_publishable_key: string|null }} */
    this.auth = this._loadAuth();

    /** @type {import('axios').AxiosInstance} */
    this.client = axios.create({
      baseURL: resolveBaseUrl(),
      timeout: API_TIMEOUT,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    });

    // Interceptor request : injection du Bearer token Sanctum si présent.
    this.client.interceptors.request.use((config) => {
      if (this.auth.token) {
        config.headers.Authorization = `Bearer ${this.auth.token}`;
      }
      return config;
    });

    // Interceptor response : 401 → purge du token + événement global pour
    // que l'UI redirige vers l'écran de login. Erreurs réseau → flag offline.
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          this._clearAuth();
          try {
            window.dispatchEvent(new CustomEvent('auth:expired'));
          } catch (e) {
            // window indisponible — rien à faire
          }
        }
        if (!error.response) {
          error.isOffline = true;
        }
        return Promise.reject(error);
      }
    );
  }

  // -------------------------------------------------------------------------
  // Persistance (localStorage — cohérent avec src/stores/auth.js)
  // -------------------------------------------------------------------------

  /**
   * Charge l'état d'auth persisté depuis localStorage.
   * @returns {{ token: string|null, creator: object|null, stripe_publishable_key: string|null }}
   * @private
   */
  _loadAuth() {
    const empty = { token: null, creator: null, stripe_publishable_key: null };
    try {
      const raw = localStorage.getItem(AUTH_STORAGE_KEY);
      if (!raw) return empty;
      const parsed = JSON.parse(raw);
      return {
        token: parsed.token || null,
        creator: parsed.creator || null,
        stripe_publishable_key: parsed.stripe_publishable_key || null,
      };
    } catch (e) {
      return empty;
    }
  }

  /**
   * Persiste l'état d'auth courant dans localStorage sous la clé `auth`.
   * @private
   */
  _persistAuth() {
    try {
      localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(this.auth));
    } catch (e) {
      console.warn('[ApiService] Persistance auth impossible:', e);
    }
  }

  /**
   * Vide l'état d'auth (mémoire + localStorage).
   * @private
   */
  _clearAuth() {
    this.auth = { token: null, creator: null, stripe_publishable_key: null };
    try {
      localStorage.removeItem(AUTH_STORAGE_KEY);
    } catch (e) {
      // localStorage indisponible — état mémoire déjà purgé
    }
  }

  // -------------------------------------------------------------------------
  // Normalisation des réponses / erreurs
  // -------------------------------------------------------------------------

  /**
   * Exécute une requête et normalise la réponse au format `{ success, data?, message? }`.
   * Ne throw jamais pour les erreurs API attendues (4xx/5xx/offline).
   * @param {Promise<import('axios').AxiosResponse>} promise
   * @returns {Promise<{ success: boolean, data?: *, message?: string, status?: number, offline?: boolean }>}
   * @private
   */
  async _request(promise) {
    try {
      const res = await promise;
      const body = res.data;
      // Backend au format { success: true, data: ... } / { success: false, message: ... }
      if (body && typeof body === 'object' && 'success' in body) {
        return body;
      }
      return { success: true, data: body };
    } catch (error) {
      if (error.isOffline || !error.response) {
        return {
          success: false,
          offline: true,
          message: 'Connexion au serveur impossible (mode hors ligne ?).',
        };
      }
      const body = error.response.data;
      return {
        success: false,
        status: error.response.status,
        message:
          body?.message ||
          body?.error?.message ||
          `Erreur serveur (HTTP ${error.response.status}).`,
      };
    }
  }

  // -------------------------------------------------------------------------
  // API publique
  // -------------------------------------------------------------------------

  /**
   * Authentifie le créateur sur le backend (POST /login) et persiste
   * token + infos créateur + clé publique Stripe sous la clé `auth`.
   * @param {string} email — email du créateur
   * @param {string} password — mot de passe
   * @returns {Promise<{ success: boolean, data?: { token: string, creator: object, stripe_publishable_key: string }, message?: string, offline?: boolean }>}
   */
  async login(email, password) {
    const result = await this._request(this.client.post('/login', { email, password }));
    if (result.success && result.data?.token) {
      this.auth = {
        token: result.data.token,
        creator: result.data.creator || null,
        stripe_publishable_key: result.data.stripe_publishable_key || null,
      };
      this._persistAuth();
    }
    return result;
  }

  /**
   * Récupère le catalogue produits (GET /products).
   * @returns {Promise<{ success: boolean, data?: Array<object>, message?: string, offline?: boolean }>}
   */
  async getProducts() {
    return this._request(this.client.get('/products'));
  }

  /**
   * Crée une commande/vente (POST /orders).
   * @param {object} payload — données de la commande (items, paiement, etc.)
   * @returns {Promise<{ success: boolean, data?: object, message?: string, offline?: boolean }>}
   */
  async createOrder(payload) {
    return this._request(this.client.post('/orders', payload));
  }

  /**
   * Récupère l'historique paginé des commandes (GET /orders?page=N).
   * @param {number} [page=1] — numéro de page
   * @returns {Promise<{ success: boolean, data?: object, message?: string, offline?: boolean }>}
   */
  async getOrders(page = 1) {
    return this._request(this.client.get('/orders', { params: { page } }));
  }

  /**
   * Synchronise un lot de ventes créées hors ligne (POST /sync).
   * Chaque vente doit porter un `offline_id` unique pour l'idempotence côté backend.
   * @param {Array<object>} orders — tableau de ventes offline (avec offline_id)
   * @returns {Promise<{ success: boolean, data?: object, message?: string, offline?: boolean }>}
   */
  async syncOfflineOrders(orders) {
    return this._request(this.client.post('/sync', { orders }));
  }

  /**
   * Indique si un token d'authentification est présent.
   * @returns {boolean} true si un token est stocké
   */
  isAuthenticated() {
    return !!this.auth.token;
  }

  /**
   * Déconnecte localement : purge token, infos créateur et clé Stripe
   * (mémoire + localStorage). Pas d'appel réseau (pas d'endpoint /logout au contrat).
   * @returns {{ success: boolean }}
   */
  logout() {
    this._clearAuth();
    return { success: true };
  }

  /**
   * Change la baseURL de l'API à chaud et la persiste pour les prochains lancements.
   * @param {string} url — nouvelle baseURL (ex: https://mondomaine.com/api/pos)
   */
  setBaseUrl(url) {
    const normalized = String(url || '').replace(/\/+$/, '');
    this.client.defaults.baseURL = normalized;
    try {
      localStorage.setItem(BASE_URL_STORAGE_KEY, normalized);
    } catch (e) {
      console.warn('[ApiService] Persistance baseURL impossible:', e);
    }
  }

  /**
   * Retourne la clé publique Stripe reçue au login (persistée sous `auth`).
   * @returns {string|null} clé publique Stripe ou null si non connectée
   */
  getStripePublishableKey() {
    return this.auth.stripe_publishable_key;
  }
}

/** Singleton ApiService — instance unique partagée par toute l'application. */
const ApiService = new ApiServiceClass();

export default ApiService;
export { ApiService };
