<template>
  <div class="card" :class="{ 'card-out': isOutOfStock }">
    <div class="product-image-wrap">
      <img
        v-if="product.thumbnail"
        :src="product.thumbnail"
        :alt="product.name"
        class="product-image"
        @error="onImgError"
      />
      <div v-else class="product-image-placeholder">
        <span>{{ initials }}</span>
      </div>
    </div>
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

const initials = computed(() => {
  const n = props.product.name || '?';
  return n.slice(0, 2).toUpperCase();
});

const onImgError = (e) => {
  e.target.style.display = 'none';
  if (e.target.nextElementSibling) {
    e.target.nextElementSibling.style.display = 'flex';
  }
};

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
.product-image-wrap {
  width: 100%;
  height: 110px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--surface-high);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-bottom: 2px;
}
.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.product-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #2a1008, #1a0a04);
  color: #ED5F1E;
  font-size: 24px;
  font-weight: 800;
  letter-spacing: 0.05em;
}
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
