<template>
  <div class="card" :class="{ 'card-out': isOutOfStock }">
    <div class="product-name">{{ product.name }}</div>
    <div class="product-meta">
      <span class="product-price">{{ formatAmount(product.price) }}</span>
      <span class="stock-badge" :class="stockClass">{{ stockLabel }}</span>
    </div>
    <button
      class="btn-add"
      :disabled="isOutOfStock"
      @click="!isOutOfStock && $emit('add', product)"
    >
      {{ isOutOfStock ? t('product.outOfStock') : t('product.add') }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useStockStore } from '../stores/stock';

const { t } = useI18n();
const stockStore = useStockStore();

const props = defineProps({
  product: { type: Object, required: true },
});

defineEmits(['add']);

const formatAmount = (val) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(val || 0))) + ' FCFA';

const currentStock = computed(() => {
  const wsStock = stockStore.getStock(props.product.id);
  return wsStock !== null ? wsStock : (props.product.stock ?? null);
});

const isOutOfStock = computed(() => currentStock.value !== null && currentStock.value <= 0);

const stockClass = computed(() => {
  if (currentStock.value === null) return 'stock-unknown';
  if (currentStock.value <= 0) return 'stock-out';
  if (currentStock.value <= 5) return 'stock-low';
  return 'stock-ok';
});

const stockLabel = computed(() => {
  if (currentStock.value === null) return '';
  if (currentStock.value <= 0) return t('product.outOfStock');
  if (currentStock.value <= 5) return t('product.stockLow', { n: currentStock.value });
  return `${currentStock.value}`;
});
</script>

<style scoped>
.card {
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 12px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: border-color 0.15s;
}

.card:not(.card-out):hover { border-color: var(--primary); }
.card-out { opacity: 0.55; }

.product-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--on-surface);
  line-height: 1.3;
}

.product-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}

.product-price {
  font-size: 14px;
  font-weight: 800;
  color: var(--primary);
  font-feature-settings: "tnum";
}

.stock-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 999px;
  letter-spacing: 0.05em;
}

.stock-ok      { background: rgba(52,211,153,0.12); color: var(--success); }
.stock-low     { background: rgba(255,184,0,0.14);  color: var(--warning); }
.stock-out     { background: rgba(255,107,107,0.14); color: var(--danger); }
.stock-unknown { display: none; }

.btn-add {
  height: 36px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--primary-dim));
  color: var(--on-primary);
  transition: opacity 0.15s;
}

.btn-add:disabled {
  background: var(--surface-high);
  color: var(--on-surface-faint);
  cursor: not-allowed;
}

.btn-add:not(:disabled):hover { opacity: 0.88; }
</style>
