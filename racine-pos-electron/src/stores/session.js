import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';

export const useSessionStore = defineStore('session', {
  state: () => ({
    currentSession: null,
    status: 'idle',
    openingCash: 0,
    summary: null,
  }),
  actions: {
    client() {
      const auth = useAuthStore();
      return new PosApiClient(
        () => auth.token,
        (t) => { auth.token = t; auth.isAuthenticated = !!t; },
        (offline) => { auth.offline = offline; }
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
      return res;
    },
    async closeSession(sessionId, closingCash, notes = null) {
      const key = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
      return this.client().post(`/api/pos/sessions/${sessionId}/close`, { closing_cash: closingCash, notes }, key);
    },
    async getZReport(sessionId) {
      return this.client().get(`/api/pos/sessions/${sessionId}/z-report`);
    },
  },
});
