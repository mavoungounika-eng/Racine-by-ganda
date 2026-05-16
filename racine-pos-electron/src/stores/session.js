import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';
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
      const auth = useAuthStore();
      return new PosApiClient(
        () => auth.token,
        (t) => {
          auth.token = t;
          auth.isAuthenticated = !!t;
        },
        (offline) => {
          auth.offline = offline;
        },
        () => auth.operatorToken,
      );
    },
    async openSession(openingCash) {
      this.status = 'loading';
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      const res = await this.client().post('/api/pos/sessions/open', { opening_cash: openingCash }, key);
      this.currentSession = res.data?.session || res.data;
      this.status = 'open';
      await saveOfflineSession(this.currentSession);
      return res;
    },
    async getCurrentSession() {
      try {
        const res = await this.client().get('/api/pos/sessions/current');
        this.currentSession = res.data?.session || null;
        if (this.currentSession) await saveOfflineSession(this.currentSession);
        return res;
      } catch (e) {
        // Fallback offline — charger depuis cache
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
      const res = await this.client().get(`/api/pos/sessions/${sessionId}/prepare-close`);
      this.summary = res.data || null;
      await this.buildLocalZReport(sessionId);
      return res;
    },
    async closeSession(sessionId, closingCash, notes = null) {
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      return this.client().post(`/api/pos/sessions/${sessionId}/close`, { closing_cash: closingCash, notes }, key);
    },
    async getZReport(sessionId) {
      const res = await this.client().get(`/api/pos/sessions/${sessionId}/z-report`);
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
        const res = await this.client().post('/api/pos/session/check', {
          operateur_id: operateurId,
          machine_id: machineId,
          machine_name: machineName,
        });
        return res.data;
      } catch (e) {
        console.warn('[POS] Vérification session fantôme échouée:', e);
        return { has_session: false, session: null };
      }
    },

    async resumeFantomeSession(sessionId, operateurId, machineId, machineName) {
      const res = await this.client().post('/api/pos/session/resume', {
        session_id: sessionId,
        operateur_id: operateurId,
        machine_id: machineId,
        machine_name: machineName,
      });
      const payload = res.data?.resume;
      this.currentSession = payload;
      this.status = 'open';
      await saveOfflineSession(this.currentSession);
      return payload;
    },

    async forceCloseAndNewSession(sessionId, operateurId, machineId, machineName, openingCash) {
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      const res = await this.client().post('/api/pos/session/force-close', {
        session_id: sessionId,
        operateur_id: operateurId,
        machine_id: machineId,
        machine_name: machineName,
        opening_cash: openingCash,
      });
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
          await this.client().post('/api/pos/session/heartbeat', {
            session_id: sessionId,
            total_ventes: this.currentSession?.total_ventes ?? 0,
            nombre_tickets: this.currentSession?.nombre_tickets ?? 0,
            panier_snapshot: cartStore?.items ?? [],
          });
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