<template>
  <div class="terminal">
    <!-- PANNEAU CATALOGUE (gauche) -->
    <aside class="catalog">
      <div class="catalog-header">
        <h2 class="panel-title">{{ t('terminal.products') }}</h2>
        <div class="search-wrap">
          <i class="search-icon">⌕</i>
          <input
            v-model="search"
            class="search-input"
            placeholder="Rechercher un article…"
            @input="onSearch"
          />
        </div>
      </div>

      <div class="product-grid">
        <ProductCard
          v-for="p in productsStore.items"
          :key="p.id"
          :product="p"
          @add="addToCart"
        />
        <div v-if="!productsStore.items.length" class="empty-catalog">
          <span class="empty-icon">📦</span>
          <p>Aucun article disponible</p>
        </div>
      </div>
    </aside>

    <!-- PANNEAU PANIER (droite) -->
    <section class="cart">
      <div class="cart-header">
        <h2 class="panel-title">{{ t('terminal.cart') }}</h2>
        <span v-if="cart.items.length" class="cart-count">{{ cart.items.length }} article{{ cart.items.length > 1 ? 's' : '' }}</span>
      </div>

      <div class="cart-items">
        <CartItem
          v-for="i in cart.items"
          :key="i.product_id"
          :item="i"
          @remove="cart.removeItem(i.product_id)"
        />
        <div v-if="!cart.items.length" class="cart-empty">
          <span class="empty-icon">🛒</span>
          <p>Panier vide</p>
          <p class="empty-hint">Sélectionnez un article</p>
        </div>
      </div>

      <!-- TOTAL + CHECKOUT -->
      <div class="cart-footer">
        <div class="total-block">
          <span class="total-label">{{ t('terminal.total') }}</span>
          <span class="total-amount">{{ formatAmount(cart.total) }}</span>
        </div>
        <button
          class="btn-checkout"
          :disabled="!cart.items.length"
          @click="goToPayment"
        >
          <span class="checkout-icon">💳</span>
          {{ t('terminal.checkout') }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '../stores/cart';
import { useProductsStore } from '../stores/products';
import ProductCard from '../components/ProductCard.vue';
import CartItem from '../components/CartItem.vue';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const cart = useCartStore();
const productsStore = useProductsStore();
const router = useRouter();
const search = ref('');
let searchTimer = null;

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

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

onMounted(loadProducts);
</script>

<style scoped>
/* ── Layout principal ─────────────────────────────────────── */
.terminal {
  display: grid;
  grid-template-columns: 2fr 1fr;
  height: 100%;
  min-height: 0;
  background: var(--background);
  overflow: hidden;
}

/* ── Panneau catalogue ────────────────────────────────────── */
.catalog {
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--outline-variant);
  overflow: hidden;
}

.catalog-header {
  padding: 16px 18px 12px;
  background: var(--surface);
  border-bottom: 1px solid var(--outline-variant);
  display: flex;
  align-items: center;
  gap: 14px;
  flex-shrink: 0;
}

.panel-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--on-surface-muted);
  white-space: nowrap;
}

.search-wrap {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-style: normal;
  font-size: 18px;
  color: var(--on-surface-faint);
  pointer-events: none;
}

.search-input {
  width: 100%;
  height: 40px;
  background: var(--surface-high);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--on-surface);
  padding: 0 14px 0 38px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

.search-input::placeholder {
  color: var(--on-surface-faint);
}

.product-grid {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
  align-content: start;
}

/* ── Panneau panier ───────────────────────────────────────── */
.cart {
  display: flex;
  flex-direction: column;
  background: var(--surface);
  overflow: hidden;
}

.cart-header {
  padding: 16px 18px 12px;
  border-bottom: 1px solid var(--outline-variant);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.cart-count {
  font-size: 12px;
  font-weight: 700;
  background: rgba(237, 95, 30, 0.15);
  color: var(--primary);
  border-radius: 999px;
  padding: 3px 10px;
  letter-spacing: 0.04em;
}

.cart-items {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* ── Footer panier (total + checkout) ────────────────────── */
.cart-footer {
  padding: 16px;
  border-top: 1px solid var(--outline-variant);
  background: var(--surface-high);
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.total-block {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.total-label {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted);
}

.total-amount {
  font-size: clamp(22px, 2.4vw, 32px);
  font-weight: 900;
  color: var(--on-surface);
  letter-spacing: -0.01em;
  font-feature-settings: "tnum";
}

.btn-checkout {
  width: 100%;
  height: 56px;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--on-primary);
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: opacity 0.15s, transform 0.1s, box-shadow 0.15s;
  box-shadow: 0 4px 18px rgba(237, 95, 30, 0.35);
}

.btn-checkout:not(:disabled):hover {
  opacity: 0.93;
  transform: translateY(-1px);
  box-shadow: 0 6px 22px rgba(237, 95, 30, 0.45);
}

.btn-checkout:disabled {
  opacity: 0.35;
  cursor: not-allowed;
  box-shadow: none;
}

.checkout-icon {
  font-size: 20px;
}

/* ── États vides ──────────────────────────────────────────── */
.empty-catalog,
.cart-empty {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  gap: 8px;
  color: var(--on-surface-faint);
  text-align: center;
}

.empty-icon {
  font-size: 36px;
  opacity: 0.6;
}

.cart-empty {
  flex: 1;
}

.empty-hint {
  font-size: 12px;
  margin: 0;
  opacity: 0.7;
}
</style>
