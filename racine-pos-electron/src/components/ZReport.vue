<template>
  <div class="zreport-shell">
    <!-- Actions (non imprimées) -->
    <div class="zreport-actions no-print">
      <p class="zreport-hint">Rapport de clôture de caisse</p>
    </div>

    <!-- Ticket 80mm -->
    <div class="ticket" ref="ticketEl">
      <!-- En-tête enseigne -->
      <div class="ticket-header">
        <p class="brand">RACINE BY GANDA</p>
        <p class="brand-sub">Point de Vente</p>
        <p class="ticket-sep">- - - - - - - - - - - - - - - - -</p>
      </div>

      <!-- Infos session -->
      <div class="ticket-section">
        <div class="ticket-row">
          <span>Rapport</span>
          <span>Z-Report</span>
        </div>
        <div class="ticket-row" v-if="report?.session_id">
          <span>Session</span>
          <span>#{{ report.session_id }}</span>
        </div>
        <div class="ticket-row" v-if="report?.opened_at">
          <span>Ouverture</span>
          <span>{{ fmt(report.opened_at) }}</span>
        </div>
        <div class="ticket-row" v-if="report?.closed_at">
          <span>Clôture</span>
          <span>{{ fmt(report.closed_at) }}</span>
        </div>
        <div class="ticket-row" v-if="report?.cashier">
          <span>Caissier</span>
          <span>{{ report.cashier }}</span>
        </div>
      </div>

      <p class="ticket-sep">- - - - - - - - - - - - - - - - -</p>

      <!-- Résumé transactions -->
      <div class="ticket-section">
        <p class="section-title">TRANSACTIONS</p>
        <div class="ticket-row">
          <span>Nombre de ventes</span>
          <span>{{ report?.total_sales ?? report?.sales_count ?? 0 }}</span>
        </div>
        <div class="ticket-row" v-if="report?.total_items != null">
          <span>Articles vendus</span>
          <span>{{ report.total_items }}</span>
        </div>
        <div class="ticket-row" v-if="report?.discounts != null">
          <span>Remises</span>
          <span>-{{ fmtAmt(report.discounts) }}</span>
        </div>
      </div>

      <p class="ticket-sep">- - - - - - - - - - - - - - - - -</p>

      <!-- Détail par méthode de paiement -->
      <div class="ticket-section" v-if="paymentMethods.length">
        <p class="section-title">PAR MODE DE PAIEMENT</p>
        <div
          v-for="pm in paymentMethods"
          :key="pm.method"
          class="ticket-row"
        >
          <span>{{ labelMethod(pm.method) }}</span>
          <span>{{ fmtAmt(pm.amount) }}</span>
        </div>
      </div>

      <p class="ticket-sep">- - - - - - - - - - - - - - - - -</p>

      <!-- Fonds de caisse -->
      <div class="ticket-section">
        <p class="section-title">FONDS DE CAISSE</p>
        <div class="ticket-row" v-if="report?.opening_cash != null">
          <span>Fond d'ouverture</span>
          <span>{{ fmtAmt(report.opening_cash) }}</span>
        </div>
        <div class="ticket-row" v-if="report?.cash_sales != null || report?.total_cash != null">
          <span>Espèces encaissées</span>
          <span>{{ fmtAmt(report.cash_sales ?? report.total_cash) }}</span>
        </div>
        <div class="ticket-row" v-if="report?.expected_cash != null">
          <span>Attendu en caisse</span>
          <span>{{ fmtAmt(report.expected_cash) }}</span>
        </div>
        <div class="ticket-row" v-if="report?.closing_cash != null">
          <span>Déclaré à clôture</span>
          <span>{{ fmtAmt(report.closing_cash) }}</span>
        </div>
        <div
          v-if="discrepancy !== null"
          class="ticket-row"
          :class="discrepancy !== 0 ? 'row-alert' : 'row-ok'"
        >
          <span>Écart</span>
          <span>{{ discrepancy >= 0 ? '+' : '' }}{{ fmtAmt(discrepancy) }}</span>
        </div>
      </div>

      <p class="ticket-sep">= = = = = = = = = = = = = = = = =</p>

      <!-- Total net -->
      <div class="ticket-total">
        <span>TOTAL NET</span>
        <span>{{ fmtAmt(totalNet) }}</span>
      </div>

      <p class="ticket-sep">= = = = = = = = = = = = = = = = =</p>

      <!-- Pied de page -->
      <div class="ticket-footer">
        <p>Merci pour votre confiance</p>
        <p>www.racinebyganda.com</p>
        <p class="ticket-date">Imprimé le {{ now }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  report: { type: Object, required: true },
});

const fmt = (val) => {
  if (!val) return '—';
  return new Date(val).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const fmtAmt = (val) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(val || 0))) + ' FCFA';

const methodLabels = {
  cash:         'Espèces',
  card:         'Carte bancaire',
  mobile_money: 'Mobile Money',
};
const labelMethod = (m) => methodLabels[m] || m;

const paymentMethods = computed(() => {
  const r = props.report;
  if (!r) return [];
  // Format [{method, amount}] ou {cash:X, card:Y, mobile_money:Z}
  if (Array.isArray(r.payment_methods)) return r.payment_methods;
  const methods = [];
  if (r.cash_sales   != null) methods.push({ method: 'cash',         amount: r.cash_sales });
  if (r.card_sales   != null) methods.push({ method: 'card',         amount: r.card_sales });
  if (r.mobile_sales != null) methods.push({ method: 'mobile_money', amount: r.mobile_sales });
  return methods;
});

const totalNet = computed(() => {
  const r = props.report;
  if (!r) return 0;
  return r.total_amount ?? r.net_total ?? r.total_sales_amount ?? 0;
});

const discrepancy = computed(() => {
  const r = props.report;
  if (!r || r.closing_cash == null || r.expected_cash == null) return null;
  return Number(r.closing_cash) - Number(r.expected_cash);
});

const now = computed(() =>
  new Date().toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
);
</script>

<style scoped>
/* ── Shell ────────────────────────────────────────────────── */
.zreport-shell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.zreport-hint {
  margin: 0;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--on-surface-muted);
  font-weight: 700;
}

/* ── Ticket 80mm ──────────────────────────────────────────── */
.ticket {
  width: 302px;           /* 80mm à 96dpi */
  background: #FAFAF8;
  color: #1A1A1A;
  font-family: 'Courier New', 'Courier', monospace;
  font-size: 12px;
  line-height: 1.5;
  padding: 16px 14px;
  border-radius: 4px;
  border: 1px solid rgba(0,0,0,0.12);
  box-shadow: 0 2px 12px rgba(0,0,0,0.4);
}

/* ── En-tête ──────────────────────────────────────────────── */
.ticket-header {
  text-align: center;
  margin-bottom: 8px;
}

.brand {
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  letter-spacing: 0.12em;
  font-family: inherit;
}

.brand-sub {
  margin: 2px 0 6px;
  font-size: 11px;
  color: #555;
  font-family: inherit;
}

/* ── Séparateurs ──────────────────────────────────────────── */
.ticket-sep {
  margin: 6px 0;
  color: #999;
  font-family: inherit;
  text-align: center;
  font-size: 11px;
}

/* ── Sections ─────────────────────────────────────────────── */
.ticket-section {
  margin: 6px 0;
}

.section-title {
  margin: 0 0 4px;
  font-weight: 900;
  font-size: 11px;
  letter-spacing: 0.1em;
  color: #333;
  font-family: inherit;
}

/* ── Lignes ───────────────────────────────────────────────── */
.ticket-row {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  padding: 1px 0;
  font-family: inherit;
}

.ticket-row span:first-child {
  color: #555;
  flex: 1;
}

.ticket-row span:last-child {
  font-weight: 700;
  white-space: nowrap;
}

.row-alert span { color: #CC3300 !important; }
.row-ok span    { color: #1A6E3A !important; }

/* ── Total net ────────────────────────────────────────────── */
.ticket-total {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  font-weight: 900;
  font-family: inherit;
  padding: 4px 0;
}

/* ── Pied de page ─────────────────────────────────────────── */
.ticket-footer {
  text-align: center;
  margin-top: 8px;
  color: #666;
  font-size: 11px;
  font-family: inherit;
}

.ticket-footer p { margin: 2px 0; font-family: inherit; }
.ticket-date { color: #999; }

/* ── Impression ───────────────────────────────────────────── */
@media print {
  .no-print { display: none !important; }

  .zreport-shell {
    background: white;
    padding: 0;
  }

  .ticket {
    width: 80mm;
    box-shadow: none;
    border: none;
    border-radius: 0;
    padding: 4mm 3mm;
  }
}
</style>
