<template>
  <div v-if="visible" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card modal-card--receipt">
      <div class="modal-header">
        <h3 class="modal-title">{{ t('receipt.title') }}</h3>
        <button class="modal-close" @click="$emit('close')">&#10005;</button>
      </div>
      <div class="modal-body">
        <div class="receipt" ref="receiptRef">
          <div class="receipt-header">
            <p class="receipt-brand">RACINE BY GANDA</p>
            <p class="receipt-sub">Point de Vente</p>
            <p class="receipt-sep">- - - - - - - - - - - - - -</p>
          </div>

          <!-- Sale info -->
          <div class="receipt-row">
            <span>{{ t('receipt.saleNumber') }}</span>
            <span>#{{ sale?.id || sale?.offline_id || '---' }}</span>
          </div>
          <div class="receipt-row">
            <span>{{ t('receipt.date') }}</span>
            <span>{{ formatDate(sale?.created_at) }}</span>
          </div>
          <div v-if="sale?.operator_name" class="receipt-row">
            <span>{{ t('receipt.operator') }}</span>
            <span>{{ sale.operator_name }}</span>
          </div>
          <p class="receipt-sep">- - - - - - - - - - - - - -</p>

          <!-- Items -->
          <div
            v-for="item in (sale?.items || items)"
            :key="item.product_id || item.id"
            class="receipt-row"
          >
            <span>{{ item.product_name || item.name }} x{{ item.quantity }}</span>
            <span>{{ formatAmount(item.subtotal || item.price * item.quantity) }}</span>
          </div>
          <p class="receipt-sep">= = = = = = = = = = = = = =</p>

          <!-- Discounts -->
          <div v-if="discountPercent > 0" class="receipt-row">
            <span>{{ t('receipt.discount') }} ({{ discountPercent }}%)</span>
            <span>-{{ formatAmount(discountAmount) }}</span>
          </div>
          <div v-if="couponCode" class="receipt-row">
            <span>{{ t('receipt.coupon') }} ({{ couponCode }})</span>
            <span>-{{ formatAmount(couponDiscount) }}</span>
          </div>

          <!-- Total -->
          <div class="receipt-total">
            <span>TOTAL</span>
            <span>{{ formatAmount(total) }}</span>
          </div>

          <!-- Payment info -->
          <div class="receipt-row">
            <span>{{ t('receipt.paymentMethod') }}</span>
            <span>{{ labelPaymentMethod(paymentMethod) }}</span>
          </div>
          <div v-if="paymentMethod === 'cash' && changeDue > 0" class="receipt-row">
            <span>{{ t('receipt.change') }}</span>
            <span>{{ formatAmount(changeDue) }}</span>
          </div>
          <div v-if="transactionRef" class="receipt-row">
            <span>{{ t('receipt.reference') }}</span>
            <span>{{ transactionRef }}</span>
          </div>
          <div v-if="isOfflineSale" class="receipt-row receipt-row--offline">
            <span>{{ t('receipt.offlineNote') }}</span>
          </div>

          <p class="receipt-sep">- - - - - - - - - - - - - -</p>
          <p class="receipt-thanks">{{ t('receipt.thanks') }}</p>
          <p class="receipt-url">www.racinebyganda.com</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-action" @click="printReceipt">
          {{ t('receipt.print') }}
        </button>
        <button class="btn-action btn-action--primary" @click="$emit('newSale')">
          + {{ t('receipt.newSale') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const receiptRef = ref(null);

const props = defineProps({
  visible: { type: Boolean, default: false },
  sale: { type: Object, default: null },
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  paymentMethod: { type: String, default: 'cash' },
  changeDue: { type: Number, default: 0 },
  discountPercent: { type: Number, default: 0 },
  discountAmount: { type: Number, default: 0 },
  couponCode: { type: String, default: '' },
  couponDiscount: { type: Number, default: 0 },
  transactionRef: { type: String, default: '' },
  isOfflineSale: { type: Boolean, default: false },
});

defineEmits(['close', 'newSale']);

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

const formatDate = (dateStr) => {
  if (!dateStr) return new Date().toLocaleString('fr-FR');
  return new Date(dateStr).toLocaleString('fr-FR');
};

const labelPaymentMethod = (m) => {
  const methods = { cash: 'Especes', card: 'Carte bancaire', mobile_money: 'Mobile Money' };
  return methods[m] || m || '---';
};

/**
 * Build a standalone HTML document for silent printing on thermal 80mm printers.
 * Inline styles ensure consistent output regardless of app theme.
 */
const buildReceiptHtml = () => {
  const saleItems = props.sale?.items || props.items;
  const rows = saleItems
    .map((item) => {
      const name = item.product_name || item.name || '---';
      const qty = item.quantity;
      const amount = formatAmount(item.subtotal || item.price * qty);
      return `<div class="row"><span>${name} x${qty}</span><span>${amount}</span></div>`;
    })
    .join('');

  let extras = '';
  if (props.discountPercent > 0) {
    extras += `<div class="row"><span>${t('receipt.discount')} (${props.discountPercent}%)</span><span>-${formatAmount(props.discountAmount)}</span></div>`;
  }
  if (props.couponCode) {
    extras += `<div class="row"><span>${t('receipt.coupon')} (${props.couponCode})</span><span>-${formatAmount(props.couponDiscount)}</span></div>`;
  }

  let paymentInfo = `<div class="row"><span>${t('receipt.paymentMethod')}</span><span>${labelPaymentMethod(props.paymentMethod)}</span></div>`;
  if (props.paymentMethod === 'cash' && props.changeDue > 0) {
    paymentInfo += `<div class="row"><span>${t('receipt.change')}</span><span>${formatAmount(props.changeDue)}</span></div>`;
  }
  if (props.transactionRef) {
    paymentInfo += `<div class="row"><span>${t('receipt.reference')}</span><span>${props.transactionRef}</span></div>`;
  }
  if (props.isOfflineSale) {
    paymentInfo += `<div class="offline">${t('receipt.offlineNote')}</div>`;
  }

  const saleId = props.sale?.id || props.sale?.offline_id || '---';
  const saleDate = formatDate(props.sale?.created_at);
  const operatorRow = props.sale?.operator_name
    ? `<div class="row"><span>${t('receipt.operator')}</span><span>${props.sale.operator_name}</span></div>`
    : '';

  return `<!DOCTYPE html><html><head><meta charset="utf-8"><style>
@page{margin:0;size:80mm auto}
body{font-family:'Courier New',monospace;font-size:11px;line-height:1.6;margin:0;padding:3mm;width:74mm;color:#000}
.center{text-align:center}
.brand{font-weight:900;font-size:14px;letter-spacing:.1em}
.sub{font-size:10px;color:#444;margin:2px 0}
.sep{margin:4px 0;color:#666;text-align:center;white-space:pre}
.row{display:flex;justify-content:space-between;margin:3px 0;gap:6px}
.row span:first-child{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.total{display:flex;justify-content:space-between;font-weight:900;font-size:13px;padding:4px 0;border-top:1px dashed #000;border-bottom:1px dashed #000;margin:6px 0}
.thanks{text-align:center;font-weight:600;margin-top:8px}
.url{text-align:center;font-size:10px;color:#666}
.offline{color:#666;font-style:italic;text-align:center;margin:3px 0}
</style></head><body>
<div class="center"><div class="brand">RACINE BY GANDA</div><div class="sub">Point de Vente</div></div>
<div class="sep">- - - - - - - - - - - - - -</div>
<div class="row"><span>${t('receipt.saleNumber')}</span><span>#${saleId}</span></div>
<div class="row"><span>${t('receipt.date')}</span><span>${saleDate}</span></div>
${operatorRow}
<div class="sep">- - - - - - - - - - - - - -</div>
${rows}
<div class="sep">= = = = = = = = = = = = = =</div>
${extras}
<div class="total"><span>TOTAL</span><span>${formatAmount(props.total)}</span></div>
${paymentInfo}
<div class="sep">- - - - - - - - - - - - - -</div>
<div class="thanks">${t('receipt.thanks')}</div>
<div class="url">www.racinebyganda.com</div>
</body></html>`;
};

const printReceipt = async () => {
  // Try silent thermal print via Electron IPC
  if (window.electron?.printer) {
    try {
      const html = buildReceiptHtml();
      const result = await window.electron.printer.printReceipt({ html, silent: true });
      if (result.success) return;
    } catch {
      // Silent print unavailable — fall through to browser dialog
    }
  }
  // Fallback: standard browser print dialog
  window.print();
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  display: grid;
  place-items: center;
  z-index: 100;
  padding: 24px;
}

.modal-card {
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 20px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
  overflow: hidden;
}

.modal-card--receipt {
  width: min(420px, 100%);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid var(--outline-variant);
  flex-shrink: 0;
}

.modal-title {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--on-surface);
}

.modal-close {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 8px;
  background: var(--surface-high);
  color: var(--on-surface-muted);
  font-size: 13px;
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: background 0.15s;
}

.modal-close:hover {
  background: var(--outline-variant);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 16px 20px;
}

.modal-footer {
  display: flex;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid var(--outline-variant);
  flex-shrink: 0;
}

/* ── Receipt (thermal-printer friendly) ────────────────────── */
.receipt {
  font-family: 'Courier New', monospace;
  font-size: 11px;
  line-height: 1.6;
  color: var(--on-surface);
  max-width: 80mm;
  margin: 0 auto;
}

.receipt-header {
  text-align: center;
  margin-bottom: 12px;
}

.receipt-brand {
  margin: 0;
  font-weight: 900;
  font-size: 14px;
  letter-spacing: 0.1em;
  color: #ED5F1E;
}

.receipt-sub {
  margin: 2px 0;
  font-size: 10px;
  color: var(--on-surface-muted);
}

.receipt-sep {
  margin: 6px 0;
  white-space: pre;
  text-align: center;
  color: var(--on-surface-faint);
}

.receipt-row {
  display: flex;
  justify-content: space-between;
  margin: 4px 0;
  gap: 8px;
}

.receipt-row span:first-child {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.receipt-row--offline {
  color: #FFB800;
  font-style: italic;
  justify-content: center;
}

.receipt-total {
  display: flex;
  justify-content: space-between;
  margin: 8px 0;
  font-weight: 900;
  font-size: 13px;
  padding: 6px 0;
  border-top: 1px dashed var(--on-surface-faint);
  border-bottom: 1px dashed var(--on-surface-faint);
}

.receipt-thanks {
  margin: 8px 0 2px;
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--on-surface);
}

.receipt-url {
  margin: 2px 0;
  text-align: center;
  font-size: 10px;
  color: var(--on-surface-muted);
}

/* ── Action buttons ─────────────────────────────────────────── */
.btn-action {
  flex: 1;
  height: 40px;
  border: 1px solid var(--outline-variant);
  border-radius: 10px;
  background: transparent;
  color: var(--on-surface);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-action:hover {
  border-color: #ED5F1E;
  background: rgba(237, 95, 30, 0.06);
}

.btn-action--primary {
  background: linear-gradient(135deg, #ED5F1E 0%, #c44d17 100%);
  color: #FFFFFF;
  border: none;
}

.btn-action--primary:hover {
  opacity: 0.93;
}

/* ── Print styles ───────────────────────────────────────────── */
@media print {
  .modal-overlay {
    position: static;
    background: none;
    padding: 0;
  }

  .modal-card {
    box-shadow: none;
    border: none;
    border-radius: 0;
    width: 80mm;
  }

  .modal-header,
  .modal-footer {
    display: none;
  }

  .modal-body {
    padding: 0;
  }

  .receipt {
    color: #000;
    max-width: 80mm;
  }

  .receipt-brand {
    color: #000;
  }

  .receipt-sep {
    color: #666;
  }

  .receipt-row--offline {
    color: #666;
  }
}
</style>
