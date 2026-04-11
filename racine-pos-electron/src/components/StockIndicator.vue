<script setup>
/**
 * StockIndicator — Badge inline temps réel
 * Affiche le niveau de stock d'un produit alimenté par le StockStore WS.
 * Utilisable dans la liste produits du POS, le panier, etc.
 */
import { computed } from 'vue'
import { useStockStore } from '@/stores/stock'

const props = defineProps({
  productId: { type: Number, required: true },
  /** Stock initial depuis l'API (avant 1er event WS) */
  initialStock: { type: Number, default: null },
})

const stockStore = useStockStore()

/** Stock WS ou valeur initiale en fallback */
const stock = computed(() => {
  const wsStock = stockStore.getStock(props.productId)
  return wsStock !== null ? wsStock : props.initialStock
})

const status = computed(() => {
  if (stock.value === null) return 'unknown'
  if (stock.value === 0)   return 'out'
  if (stock.value <= 5)    return 'low'
  return 'ok'
})
</script>

<template>
  <span :class="['stock-badge', `stock-${status}`]" :title="`Stock: ${stock ?? '?'}`">
    <template v-if="status === 'ok'">
      <span class="stock-dot" />
      {{ stock }}
    </template>
    <template v-else-if="status === 'low'">
      ⚠ {{ stock }} restant{{ stock > 1 ? 's' : '' }}
    </template>
    <template v-else-if="status === 'out'">
      🚫 Épuisé
    </template>
    <template v-else>
      —
    </template>
  </span>
</template>

<style scoped>
.stock-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 9999px;
  white-space: nowrap;
  transition: all 0.3s ease;
}

.stock-ok {
  background: #d1fae5;
  color: #065f46;
}

.stock-low {
  background: #fef3c7;
  color: #92400e;
  animation: pulse-warning 2s ease-in-out infinite;
}

.stock-out {
  background: #fee2e2;
  color: #991b1b;
}

.stock-unknown {
  background: #f3f4f6;
  color: #6b7280;
}

.stock-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

@keyframes pulse-warning {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.65; }
}
</style>
