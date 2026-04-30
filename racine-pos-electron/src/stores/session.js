import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';
import LocalDb from '../services/localDb';

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
      return res;
    },
    async getCurrentSession() {
      try {
        const res = await this.client().get('/api/pos/sessions/current');
        this.currentSession = res.data?.session || null;
        return res;
      } catch (e) {
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
  },
});