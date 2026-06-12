<template>
  <div class="receipt-print" :class="{ 'receipt-print--printing': printing }">
    <div class="rp-header">
      <p class="rp-brand">RACINE BY GANDA</p>
      <p class="rp-sub">{{ t('receipt.title') }}</p>
      <p v-if="sale.creatorName" class="rp-sub">{{ t('receipt.creator') }} : {{ sale.creatorName }}</p>
      <p class="rp-sep">- - - - - - - - - - - - - - - -</p>
    </div>

    <div v-if="sale.reference" class="rp-row">
      <span>{{ t('receipt.reference') }}</span>
      <span>{{ sale.reference }}</span>
    </div>
    <div class="rp-row">
      <span>{{ t('receipt.date') }}</span>
      <span>{{ formattedDate }}</span>
    </div>

    <p class="rp-sep">- - - - - - - - - - - - - - - -</p>

    <div v-for="(item, idx) in sale.items" :key="idx" class="rp-row">
      <span>{{ item.name }} ×{{ item.quantity }}</span>
      <span>{{ formatAmount(item.subtotal ?? item.unit_price * item.quantity) }}</span>
    </div>

    <p class="rp-sep">= = = = = = = = = = = = = = = =</p>

    <div v-if="sale.discountLabel" class="rp-row">
      <span>{{ sale.discountLabel }}</span>
      <span>−{{ formatAmount(sale.discountAmount) }}</span>
    </div>

    <div class="rp-total">
      <span>{{ t('receipt.total') }}</span>
      <span>{{ formatAmount(sale.total) }}</span>
    </div>

    <div class="rp-row">
      <span>{{ t('receipt.method') }}</span>
      <span>{{ sale.methodLabel }}</span>
    </div>

    <div v-if="sale.change != null && sale.change > 0" class="rp-row">
      <span>{{ t('receipt.change') }}</span>
      <span>{{ formatAmount(sale.change) }}</span>
    </div>

    <p class="rp-sep">- - - - - - - - - - - - - - - -</p>
    <p class="rp-footer">{{ t('receipt.thanks') }}</p>
    <p class="rp-footer">www.racinebyganda.com</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  /**
   * sale : {
   *   creatorName: string|null,
   *   items: [{ name, quantity, unit_price, subtotal? }],
   *   total: number,
   *   methodLabel: string,     — libellé déjà traduit ('Espèces', 'Monetbil', …)
   *   date: string|Date|null,  — défaut: maintenant
   *   reference: string|null,  — n° de vente / monetbil_ref / stripe_ref / offline_id
   *   change: number|null,     — rendu monnaie (espèces)
   *   discountLabel: string|null,
   *   discountAmount: number|null,
   * }
   */
  sale: { type: Object, required: true },
});

const printing = ref(false);

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

const formattedDate = computed(() => {
  const d = props.sale.date ? new Date(props.sale.date) : new Date();
  return d.toLocaleString('fr-FR');
});

/**
 * Impression : marque ce reçu comme zone imprimable (classe portée pendant
 * window.print()), le CSS @media print global masque tout le reste.
 */
const print = () => {
  printing.value = true;
  // Laisser Vue appliquer la classe avant d'ouvrir le dialogue d'impression.
  requestAnimationFrame(() => {
    const done = () => {
      printing.value = false;
      window.removeEventListener('afterprint', done);
    };
    window.addEventListener('afterprint', done);
    window.print();
    // Fallback si afterprint n'est pas émis (annulation silencieuse).
    setTimeout(done, 2000);
  });
};

defineExpose({ print });
</script>

<style scoped>
/* ── Affichage écran (charte : noir #160D0C / blanc #FFFFFF) ─────────── */
.receipt-print {
  font-family: 'Courier New', monospace;
  font-size: 11px;
  line-height: 1.6;
  color: var(--on-surface, #FFFFFF);
}

.rp-header {
  text-align: center;
  margin-bottom: 12px;
}

.rp-brand {
  margin: 0;
  font-weight: 900;
  font-size: 13px;
  letter-spacing: 0.06em;
}

.rp-sub {
  margin: 2px 0;
  font-size: 10px;
}

.rp-sep {
  margin: 6px 0;
  white-space: pre;
  overflow: hidden;
}

.rp-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin: 4px 0;
}

.rp-total {
  display: flex;
  justify-content: space-between;
  margin: 6px 0;
  font-weight: 900;
  font-size: 12px;
}

.rp-footer {
  margin: 4px 0;
  text-align: center;
  font-size: 10px;
}
</style>

<!-- Styles d'impression GLOBAUX (non scoped) : pendant window.print(),
     seul le reçu marqué .receipt-print--printing est visible. -->
<style>
@media print {
  body * {
    visibility: hidden !important;
  }

  .receipt-print--printing,
  .receipt-print--printing * {
    visibility: visible !important;
  }

  .receipt-print--printing {
    position: fixed !important;
    inset: 0 auto auto 0 !important;
    width: 72mm; /* largeur ticket thermique standard */
    margin: 0 !important;
    padding: 4mm !important;
    background: #FFFFFF !important;
    color: #160D0C !important;
    font-size: 10pt !important;
  }

  .receipt-print--printing .rp-sub,
  .receipt-print--printing .rp-footer {
    color: #160D0C !important;
  }

  @page {
    margin: 0;
  }
}
</style>
