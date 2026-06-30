import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import ApiService from '../services/apiService';
import LocalDb from '../services/localDb';
import { saveOfflineSession, loadOfflineSession } from './offlineCache.js';

export const useSessionStore = defineStore('session', {
  state: () => ({
    currentSession: null,
    status: 'idle',
    openingCash: 0,
    summary: null,
    localZReport: null,
    zReportData: null,
  }),
  getters: {
    zReport(state) {
      return state.zReportData || state.localZReport || null;
    },
  },
  actions: {
    client() {
      return ApiService;
    },
    async openSession(openingCash) {
      this.status = 'loading';
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      const res = await ApiService.openSession(openingCash);
      this.currentSession = res.data?.session || res.data;
      this.status = 'open';
      await saveOfflineSession(this.currentSession);
      return res;
    },
    async getCurrentSession() {
      try {
        const res = await ApiService.getCurrentSession();
        this.currentSession = res.data?.session || null;
        if (this.currentSession) await saveOfflineSession(this.currentSession);
        return res;
      } catch (e) {
        // 404 = pas de session ouverte — ne pas charger le cache
        if (e.response?.status === 404) {
          this.currentSession = null;
          return { data: { session: null } };
        }
        // Autre erreur (réseau) — fallback offline
        const cached = await loadOfflineSession();
        if (cached) {
          this.currentSession = cached;
          console.warn('[POS] Session chargée depuis cache offline');
          return { data: { session: cached } };
        }
        this.currentSession = null;
        throw e;
      }
    },
    async prepareClose(sessionId) {
      const res = await ApiService.prepareClose(sessionId);
      this.summary = res.data || null;
      await this.buildLocalZReport(sessionId);
      return res;
    },
    async closeSession(sessionId, closingCash, notes = null) {
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      return ApiService.closeSession(sessionId, closingCash, notes);
    },
    async getZReport(sessionId) {
      const res = await ApiService.getZReport(sessionId);
      this.zReportData = res.data?.z_report || res.data || null;
      return res;
    },
    async buildLocalZReport(sessionId = null) {
      const targetSessionId = sessionId || this.currentSession?.id || null;
      const statuses = ['pending', 'synced', 'conflict', 'failed'];
      const salesByStatus = await Promise.all(statuses.map((status) => LocalDb.getAllSales(status)));

      const localSales = salesByStatus
        .flat()
        .filter((sale) => !targetSessionId || String(sale.session_id) === String(targetSessionId));

      const totalSales = localSales.length;
      const totalAmount = localSales.reduce((sum, sale) => sum + Number(sale.total_amount || 0), 0);
      const totalCash = localSales
        .filter((sale) => sale.payment_method === 'cash')
        .reduce((sum, sale) => sum + Number(sale.total_amount || 0), 0);

      this.localZReport = {
        source: 'local',
        session_id: targetSessionId,
        total_sales: totalSales,
        total_amount: totalAmount,
        total_cash: totalCash,
        expected_cash: this.summary?.expected_cash ?? totalCash,
      };

      return this.localZReport;
    },

    async checkFantomeSession(operateurId, machineId, machineName) {
      try {
        const res = await ApiService.checkPhantomSession(operateurId, machineId, machineName);
        return res.data;
      } catch (e) {
        console.warn('[POS] Vérification session fantôme échouée:', e);
        return { has_session: false, session: null };
      }
    },

    async resumeFantomeSession(sessionId, operateurId, machineId, machineName) {
      const res = await ApiService.resumePhantomSession(sessionId, operateurId, machineId, machineName);
      const payload = res.data?.resume;
      this.currentSession = payload;
      this.status = 'open';
      await saveOfflineSession(this.currentSession);
      return payload;
    },

    async forceCloseAndNewSession(sessionId, operateurId, machineId, machineName, openingCash) {
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      const res = await ApiService.forceCloseAndNewSession(sessionId, operateurId, machineId, machineName, openingCash);
      const newSession = res.data?.new_session;
      this.currentSession = newSession;
      this.status = 'open';
      await saveOfflineSession(this.currentSession);
      return newSession;
    },

    startHeartbeat(sessionId) {
      if (this._heartbeatInterval) clearInterval(this._heartbeatInterval);
      this._heartbeatInterval = setInterval(async () => {
        try {
          const cartStore = (await import('./cart')).useCartStore();
          await ApiService.sendHeartbeat(sessionId, this.currentSession?.total_ventes ?? 0, this.currentSession?.nombre_tickets ?? 0, cartStore?.items ?? []);
        } catch (e) {
          console.warn('[POS] Heartbeat échoué:', e);
        }
      }, 5 * 60 * 1000);
    },

    stopHeartbeat() {
      if (this._heartbeatInterval) {
        clearInterval(this._heartbeatInterval);
        this._heartbeatInterval = null;
      }
    },
  },
});