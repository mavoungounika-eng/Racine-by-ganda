<template>
  <div class="item">
    <div class="item-info">
      <span class="item-name">{{ item.name }}</span>
      <span class="item-qty">× {{ item.quantity }}</span>
    </div>
    <div class="item-right">
      <span class="item-total">{{ formatAmount(item.price * item.quantity) }}</span>
      <button class="btn-remove" @click="$emit('remove')" :title="t('cart.remove')">✕</button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
  item: { type: Object, required: true },
});

defineEmits(['remove']);

const formatAmount = (val) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(val || 0))) + ' FCFA';
</script>

<style scoped>
.item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
  gap: 8px;
}

.item:last-child { border-bottom: none; }

.item-info {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}

.item-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--on-surface);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-qty {
  font-size: 12px;
  color: var(--on-surface-muted);
  white-space: nowrap;
}

.item-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.item-total {
  font-size: 13px;
  font-weight: 700;
  color: var(--on-surface);
  font-feature-settings: "tnum";
}

.btn-remove {
  background: transparent;
  color: var(--on-surface-faint);
  border: none;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
}

.btn-remove:hover {
  background: rgba(255, 107, 107, 0.15);
  color: var(--danger);
}
</style>
