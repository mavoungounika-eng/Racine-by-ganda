<template>
  <div class="terminal-shell">
    <header class="topbar">
      <div class="topbar-brand">
        <span class="brand-logo">R</span>
        <span class="brand-name">RACINE <span class="brand-by">by</span> GANDA</span>
      </div>
      <nav class="topbar-actions">
        <button class="action-btn" @click="syncNow" :class="{ spinning: syncing }" title="Synchroniser">
          <span class="action-icon">🔄</span>
          <span class="action-label">Sync</span>
        </button>
        <button class="action-btn" @click="showHistory = true" title="Historique">
          <span class="action-icon">📋</span>
          <span class="action-label">Historique</span>
        </button>
        <button class="action-btn" @click="printLastReceipt" title="Imprimer facture">
          <span class="action-icon">🧾</span>
          <span class="action-label">Facture</span>
        </button>
        <button class="action-btn action-btn--danger" @click="goCloseSession" title="Clôturer la session">
          <span class="action-icon">🔒</span>
          <span class="action-label">Clôture</span>
        </button>
      </nav>
      <div class="topbar-session">
        <span class="session-operator">{{ auth.operator?.name || 'Opérateur' }}</span>
        <span class="session-dot" :class="auth.offline ? 'offline' : 'online'"></span>
      </div>
    </header>

    <div class="terminal">
      <aside class="catalog">
        <div class="catalog-header">
          <h2 class="panel-title">{{ t('terminal.products') }}</h2>
          <div class="search-wrap">
            <i class="search-icon">⌕</i>
            <input v-model="search" class="search-input" placeholder="Rechercher un article…" @input="onSearch" />
          </div>
        </div>
        <div class="product-grid">
          <ProductCard v-for="p in productsStore.items" :key="p.id" :product="p" @add="addToCart" />
          <div v-if="!productsStore.items.length" class="empty-catalog">
            <span class="empty-icon">📦</span>
            <p>Aucun article disponible</p>
          </div>
        </div>
      </aside>

      <section class="cart">
        <div class="cart-header">
          <h2 class="panel-title">{{ t('terminal.cart') }}</h2>
          <span v-if="cart.items.length" class="cart-count">{{ cart.items.length }} article{{ cart.items.length > 1 ? 's' : '' }}</span>
        </div>
        <div class="cart-items">
          <CartItem v-for="i in cart.items" :key="i.product_id" :item="i" @remove="cart.removeItem(i.product_id)" />
          <div v-if="!cart.items.length" class="cart-empty">
            <span class="empty-icon">🛒</span>
            <p>Panier vide</p>
            <p class="empty-hint">Sélectionnez un article</p>
          </div>
        </div>
        <div class="cart-footer">
          <div class="total-block">
            <span class="total-label">{{ t('terminal.total') }}</span>
            <span class="total-amount">{{ formatAmount(cart.total) }}</span>
          </div>
          <button class="btn-checkout" :disabled="!cart.items.length" @click="goToPayment">
            <span class="checkout-icon">💳</span>
            {{ t('terminal.checkout') }}
          </button>
        </div>
      </section>
    </div>

    <!-- MODAL HISTORIQUE -->
    <div v-if="showHistory" class="modal-overlay" @click.self="showHistory = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title">📋 Historique des ventes</h3>
          <button class="modal-close" @click="showHistory = false">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingHistory" class="modal-loading">Chargement…</div>
          <div v-else-if="!salesHistory.length" class="modal-empty">Aucune vente pour cette session</div>
          <div v-else class="history-list">
            <div v-for="sale in salesHistory" :key="sale.id" class="history-item" @click="viewSale(sale)">
              <div class="history-info">
                <span class="history-id">#{{ sale.id }}</span>
                <span class="history-date">{{ fmtDate(sale.created_at) }}</span>
              </div>
              <div class="history-right">
                <span class="history-amount">{{ formatAmount(sale.total_amount) }}</span>
                <span class="history-method">{{ labelMethod(sale.payment_method) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL FACTURE -->
    <div v-if="showReceipt" class="modal-overlay" @click.self="showReceipt = false">
      <div class="modal-card modal-card--narrow">
        <div class="modal-header">
          <h3 class="modal-title">🧾 Facture</h3>
          <button class="modal-close" @click="showReceipt = false">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="!lastSale" class="modal-empty">Aucune vente récente</div>
          <div v-else class="receipt">
            <div class="receipt-header">
              <p class="receipt-brand">RACINE BY GANDA</p>
              <p class="receipt-sub">Point de Vente</p>
              <p class="receipt-sep">- - - - - - - - - - - - - -</p>
            </div>
            <div class="receipt-row"><span>N° vente</span><span>#{{ lastSale.id }}</span></div>
            <div class="receipt-row"><span>Date</span><span>{{ fmtDate(lastSale.created_at) }}</span></div>
            <div class="receipt-row"><span>Caissier</span><span>{{ auth.operator?.name }}</span></div>
            <p class="receipt-sep">- - - - - - - - - - - - - -</p>
            <div v-for="item in (lastSale.items || [])" :key="item.id" class="receipt-row">
              <span>{{ item.product_name }} ×{{ item.quantity }}</span>
              <span>{{ formatAmount(item.subtotal) }}</span>
            </div>
            <p class="receipt-sep">= = = = = = = = = = = = = =</p>
            <div class="receipt-total"><span>TOTAL</span><span>{{ formatAmount(lastSale.total_amount) }}</span></div>
            <div class="receipt-row"><span>Paiement</span><span>{{ labelMethod(lastSale.payment_method) }}</span></div>
            <p class="receipt-sep">- - - - - - - - - - - - - -</p>
            <p class="receipt-footer">Merci pour votre confiance</p>
            <p class="receipt-footer">www.racinebyganda.com</p>
          </div>
          <button v-if="lastSale" class="btn-print" @click="window.print()">🖨 Imprimer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '../stores/cart';
import { useProductsStore } from '../stores/products';
import { useAuthStore } from '../stores/auth';
import { useSessionStore } from '../stores/session';
import ProductCard from '../components/ProductCard.vue';
import CartItem from '../components/CartItem.vue';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const cart = useCartStore();
const productsStore = useProductsStore();
const auth = useAuthStore();
const session = useSessionStore();
const router = useRouter();

const search = ref('');
const syncing = ref(false);
const showHistory = ref(false);
const showReceipt = ref(false);
const salesHistory = ref([]);
const loadingHistory = ref(false);
const lastSale = ref(null);

let searchTimer = null;

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

const fmtDate = (val) => val
  ? new Date(val).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  : '—';

const methodLabels = { cash: 'Espèces', card: 'Carte bancaire', mobile_money: 'Mobile Money' };
const labelMethod = (m) => methodLabels[m] || m || '—';

const loadProducts = async () => {
  await productsStore.fetchProducts({ in_stock: true, per_page: 50 });
};

const onSearch = () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(async () => {
    if (search.value.length >= 2) {
      const results = await productsStore.searchProducts(search.value);
      productsStore.items = results;
    } else {
      await loadProducts();
    }
  }, 300);
};

const addToCart = (product) => cart.addItem(product);
const goToPayment = () => router.push('/payment');
const goCloseSession = () => router.push('/session/close');

const syncNow = async () => {
  syncing.value = true;
  try {
    await loadProducts();
    if (session.syncPendingSales) await session.syncPendingSales();
  } catch (e) { console.warn('Sync failed', e); }
  finally { setTimeout(() => { syncing.value = false; }, 800); }
};

const loadHistory = async () => {
  loadingHistory.value = true;
  try {
    const sessionId = session.currentSession?.id;
    if (!sessionId) { salesHistory.value = []; return; }
    const client = auth.client();
    const res = await client.get(`/api/pos/sessions/${sessionId}/sales`);
    salesHistory.value = res?.data?.sales || res?.sales || [];
    if (salesHistory.value.length) lastSale.value = salesHistory.value[0];
  } catch (e) {
    console.warn('History failed', e);
    salesHistory.value = [];
  } finally {
    loadingHistory.value = false;
  }
};

const viewSale = (sale) => {
  lastSale.value = sale;
  showHistory.value = false;
  showReceipt.value = true;
};

const printLastReceipt = async () => {
  if (!lastSale.value) await loadHistory();
  showReceipt.value = true;
};

watch(showHistory, (val) => { if (val) loadHistory(); });

onMounted(loadProducts);
</script>

<style scoped>
.terminal-shell { display: flex; flex-direction: column; height: 100%; overflow: hidden; background: var(--background); }

.topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 18px; height: 52px; background: var(--surface); border-bottom: 1px solid var(--outline-variant); flex-shrink: 0; z-index: 10; }

.topbar-brand { display: flex; align-items: center; gap: 9px; }
.brand-logo { width: 28px; height: 28px; border-radius: 8px; background: var(--primary); color: var(--on-primary); font-weight: 900; font-size: 15px; display: grid; place-items: center; flex-shrink: 0; }
.brand-name { font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--on-surface); }
.brand-by { font-weight: 400; text-transform: lowercase; color: var(--on-surface-muted); }

.topbar-actions { display: flex; align-items: center; gap: 4px; }

.action-btn { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid var(--outline-variant); border-radius: 8px; background: transparent; color: var(--on-surface-muted); font-size: 12px; font-weight: 700; letter-spacing: 0.04em; cursor: pointer; transition: all 0.15s; }
.action-btn:hover { border-color: var(--primary); color: var(--on-surface); background: rgba(237, 95, 30, 0.06); }
.action-btn--danger:hover { border-color: var(--danger, #ff6b6b); color: var(--danger, #ff6b6b); background: rgba(255, 107, 107, 0.06); }
.action-icon { font-size: 14px; }
.action-label { white-space: nowrap; }
.action-btn.spinning .action-icon { display: inline-block; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.topbar-session { display: flex; align-items: center; gap: 8px; }
.session-operator { font-size: 12px; font-weight: 700; color: var(--on-surface-muted); }
.session-dot { width: 8px; height: 8px; border-radius: 50%; }
.session-dot.online { background: var(--success, #34d399); }
.session-dot.offline { background: var(--danger, #ff6b6b); }

.terminal { display: grid; grid-template-columns: 2fr 1fr; flex: 1; min-height: 0; overflow: hidden; }

.catalog { display: flex; flex-direction: column; border-right: 1px solid var(--outline-variant); overflow: hidden; }
.catalog-header { padding: 14px 18px 10px; background: var(--surface); border-bottom: 1px solid var(--outline-variant); display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
.panel-title { margin: 0; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--on-surface-muted); white-space: nowrap; }
.search-wrap { flex: 1; position: relative; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-style: normal; font-size: 18px; color: var(--on-surface-faint); pointer-events: none; }
.search-input { width: 100%; height: 38px; background: var(--surface-high); border: 1px solid var(--border); border-radius: 10px; color: var(--on-surface); padding: 0 14px 0 38px; font-size: 13px; transition: border-color 0.15s, box-shadow 0.15s; }
.search-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18); }
.search-input::placeholder { color: var(--on-surface-faint); }
.product-grid { flex: 1; overflow-y: auto; padding: 16px; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; align-content: start; }

.cart { display: flex; flex-direction: column; background: var(--surface); overflow: hidden; }
.cart-header { padding: 14px 18px 10px; border-bottom: 1px solid var(--outline-variant); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.cart-count { font-size: 11px; font-weight: 700; background: rgba(237, 95, 30, 0.15); color: var(--primary); border-radius: 999px; padding: 3px 10px; }
.cart-items { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 6px; }
.cart-footer { padding: 14px; border-top: 1px solid var(--outline-variant); background: var(--surface-high); flex-shrink: 0; display: flex; flex-direction: column; gap: 12px; }
.total-block { display: flex; align-items: baseline; justify-content: space-between; }
.total-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--on-surface-muted); }
.total-amount { font-size: clamp(20px, 2.2vw, 30px); font-weight: 900; color: var(--on-surface); font-feature-settings: "tnum"; }
.btn-checkout { width: 100%; height: 52px; border: none; border-radius: 14px; font-size: 15px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--on-primary); background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 18px rgba(237, 95, 30, 0.35); transition: opacity 0.15s, transform 0.1s; }
.btn-checkout:not(:disabled):hover { opacity: 0.93; transform: translateY(-1px); }
.btn-checkout:disabled { opacity: 0.35; cursor: not-allowed; box-shadow: none; }
.checkout-icon { font-size: 18px; }

.empty-catalog, .cart-empty { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; gap: 8px; color: var(--on-surface-faint); text-align: center; }
.empty-icon { font-size: 34px; opacity: 0.6; }
.cart-empty { flex: 1; }
.empty-hint { font-size: 12px; margin: 0; opacity: 0.7; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); display: grid; place-items: center; z-index: 100; padding: 24px; }
.modal-card { background: var(--surface); border: 1px solid var(--outline-variant); border-radius: 20px; width: min(640px, 100%); max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6); overflow: hidden; }
.modal-card--narrow { width: min(380px, 100%); }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid var(--outline-variant); flex-shrink: 0; }
.modal-title { margin: 0; font-size: 16px; font-weight: 800; color: var(--on-surface); }
.modal-close { width: 30px; height: 30px; border: none; border-radius: 8px; background: var(--surface-high); color: var(--on-surface-muted); font-size: 13px; cursor: pointer; display: grid; place-items: center; transition: background 0.15s; }
.modal-close:hover { background: var(--outline-variant); }
.modal-body { flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
.modal-loading, .modal-empty { text-align: center; color: var(--on-surface-muted); font-size: 13px; padding: 32px; }

.history-list { display: flex; flex-direction: column; gap: 8px; }
.history-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--surface-high); border-radius: 10px; border: 1px solid var(--outline-variant); cursor: pointer; transition: border-color 0.15s; }
.history-item:hover { border-color: var(--primary); }
.history-info { display: flex; flex-direction: column; gap: 2px; }
.history-id { font-size: 13px; font-weight: 800; color: var(--on-surface); }
.history-date { font-size: 11px; color: var(--on-surface-muted); }
.history-right { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.history-amount { font-size: 14px; font-weight: 900; color: var(--primary); font-feature-settings: "tnum"; }
.history-method { font-size: 11px; color: var(--on-surface-muted); }

.receipt { width: 100%; font-family: 'Courier New', monospace; font-size: 12px; color: var(--on-surface); line-height: 1.6; }
.receipt-header { text-align: center; margin-bottom: 8px; }
.receipt-brand { margin: 0; font-size: 14px; font-weight: 900; letter-spacing: 0.1em; }
.receipt-sub { margin: 0; font-size: 11px; color: var(--on-surface-muted); }
.receipt-sep { margin: 6px 0; color: var(--on-surface-faint); text-align: center; font-size: 11px; }
.receipt-row { display: flex; justify-content: space-between; gap: 8px; padding: 1px 0; }
.receipt-row span:first-child { color: var(--on-surface-muted); flex: 1; }
.receipt-row span:last-child { font-weight: 700; }
.receipt-total { display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; padding: 4px 0; }
.receipt-footer { text-align: center; font-size: 11px; color: var(--on-surface-muted); margin: 2px 0; }

.btn-print { width: 100%; height: 42px; border: 1px solid var(--outline-variant); border-radius: 10px; background: var(--surface-high); color: var(--on-surface-muted); font-size: 13px; font-weight: 700; cursor: pointer; transition: border-color 0.15s, color 0.15s; }
.btn-print:hover { border-color: var(--primary); color: var(--on-surface); }

@media print {
  .topbar, .catalog, .cart, .modal-overlay { display: none !important; }
}
</style>
