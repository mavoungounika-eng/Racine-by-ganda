import { createRouter, createWebHashHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import SessionOpenView from '../views/SessionOpenView.vue';
import TerminalView from '../views/TerminalView.vue';
import PaymentView from '../views/PaymentView.vue';
import SessionCloseView from '../views/SessionCloseView.vue';
import OfflineView from '../views/OfflineView.vue';
import { useAuthStore } from '../stores/auth';
import { useSessionStore } from '../stores/session';
import { useOfflineStore } from '../stores/offline';

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: LoginView },
  { path: '/session/open', component: SessionOpenView },
  { path: '/terminal', component: TerminalView },
  { path: '/payment', component: PaymentView },
  { path: '/session/close', component: SessionCloseView },
  { path: '/offline', component: OfflineView },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

let authInitialized = false;

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  const session = useSessionStore();
  const offline = useOfflineStore();

  // Ne charger le storage qu'une seule fois par session app
  if (!authInitialized) {
    auth.loadFromStorage();
    authInitialized = true;
  }

  // En mode offline : bloquer seulement les routes qui nécessitent le backend
  // Le terminal et le paiement restent accessibles (ventes en queue locale)
  const offlineBlocked = ['/session/open', '/session/close'];
  if (offline.isOffline && offlineBlocked.includes(to.path)) return '/offline';
  if (!auth.isAuthenticated && to.path !== '/login') return '/login';

  const requiresSession = ['/terminal', '/payment', '/session/close'];
  if (requiresSession.includes(to.path) && !session.currentSession) {
    try { await session.getCurrentSession(); } catch (_) {}
    if (!session.currentSession) return '/session/open';
  }

  return true;
});

export default router;
