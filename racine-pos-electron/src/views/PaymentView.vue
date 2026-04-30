<template>
  <div class="payment-shell">
    <!-- En-tête -->
    <div class="payment-header">
      <button class="btn-back" @click="$router.push('/terminal')">
        ← Retour
      </button>
      <h1 class="payment-title">{{ t('payment.methods') }}</h1>
      <div class="header-total">
        <span class="header-total-label">Total</span>
        <span class="header-total-amount">{{ formatAmount(cart.total) }}</span>
      </div>
    </div>

    <div class="payment-body">
      <!-- Sélection méthode — tuiles tactiles -->
      <div class="method-section">
        <p class="section-label">Mode de paiement</p>
        <div class="method-tiles">
          <button
            v-for="m in methods"
            :key="m.value"
            class="method-tile"
            :class="{ active: method === m.value }"
            @click="method = m.value"
          >
            <span class="tile-icon">{{ m.icon }}</span>
            <span class="tile-label">{{ m.label }}</span>
            <span v-if="method === m.value" class="tile-check">✓</span>
          </button>
        </div>
      </div>

      <!-- Formulaire contextuel -->
      <div class="detail-card">
        <!-- ESPÈCES -->
        <div v-if="method === 'cash'" class="detail-fields">
          <label class="field-label">Montant remis par le client</label>
          <div class="amount-row">
            <input
              v-model.number="receivedAmount"
              class="amount-input"
              type="number"
              min="0"
              step="100"
              placeholder="0"
              autofocus
            />
            <span class="amount-currency">FCFA</span>
          </div>

          <div class="change-display" v-if="receivedAmount > 0">
            <div v-if="changeDue > 0" class="change-due success">
              <span class="change-icon">↩</span>
              <div>
                <p class="change-label">Rendu monnaie</p>
                <p class="change-value">{{ formatAmount(changeDue) }}</p>
              </div>
            </div>
            <div v-else-if="remainingDue > 0" class="change-due warning">
              <span class="change-icon">⚠</span>
              <div>
                <p class="change-label">Reste à payer</p>
                <p class="change-value">{{ formatAmount(remainingDue) }}</p>
              </div>
            </div>
            <div v-else class="change-due exact">
              <span class="change-icon">✓</span>
              <div>
                <p class="change-label">Montant exact</p>
                <p class="change-value">0 FCFA de rendu</p>
              </div>
            </div>
          </div>
        </div>

        <!-- CARTE BANCAIRE -->
        <div v-if="method === 'card'" class="detail-fields">
          <label class="field-label">ID de transaction</label>
          <input v-model="transactionId" class="text-input" placeholder="TXN-XXXXXXXX" />
          <label class="field-label">Numéro de reçu</label>
          <input v-model="receiptNumber" class="text-input" placeholder="REC-XXXXXXXX" />
        </div>

        <!-- MOBILE MONEY -->
        <div v-if="method === 'mobile_money'" class="detail-fields">
          <label class="field-label">Numéro de téléphone</label>
          <input
            v-model="phoneNumber"
            class="text-input"
            placeholder="+242 XX XXX XXXX"
            type="tel"
          />
          <p class="hint">MTN Mobile Money · Orange Money · Airtel Money</p>
        </div>

        <!-- Feedback -->
        <div v-if="status" class="feedback feedback-success">
          <span>✓</span> {{ status }}
        </div>
        <div v-if="error" class="feedback feedback-error">
          <span>✗</span> {{ error }}
        </div>
      </div>
    </div>

    <!-- Bouton confirmer -->
    <div class="payment-footer">
      <button
        class="btn-confirm"
        :disabled="!canConfirm || processing"
        @click="confirm"
      >
        <span v-if="processing" class="spinner">↻</span>
        <span v-else>💳</span>
        {{ processing ? 'Traitement…' : t('payment.confirm') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '../stores/cart';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const cart = useCartStore();
const router = useRouter();

const method = ref('cash');
const transactionId = ref('');
const receiptNumber = ref('');
const phoneNumber = ref('');
const receivedAmount = ref(0);
const status = ref('');
const error = ref('');
const processing = ref(false);

const methods = [
  { value: 'cash',         icon: '💵', label: 'Espèces' },
  { value: 'card',         icon: '💳', label: 'Carte bancaire' },
  { value: 'mobile_money', icon: '📱', label: 'Mobile Money' },
];

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

const changeDue = computed(() => {
  const due = Number(cart.total || 0);
  const paid = Number(receivedAmount.value || 0);
  return Math.max(paid - due, 0);
});

const remainingDue = computed(() => {
  const due = Number(cart.total || 0);
  const paid = Number(receivedAmount.value || 0);
  return Math.max(due - paid, 0);
});

const canConfirm = computed(() => {
  if (method.value === 'cash') return Number(receivedAmount.value) >= Number(cart.total || 0);
  if (method.value === 'card') return transactionId.value.trim().length > 0;
  if (method.value === 'mobile_money') return phoneNumber.value.trim().length >= 8;
  return false;
});

onMounted(() => {
  if (cart.items.length === 0) {
    router.push('/terminal');
    return;
  }
  receivedAmount.value = Number(cart.total || 0);
});

const confirm = async () => {
  error.value = '';
  status.value = '';
  processing.value = true;

  try {
    if (method.value === 'cash' && Number(receivedAmount.value || 0) < Number(cart.total || 0)) {
      throw new Error('MONTANT_INSUFFISANT');
    }

    const saleRes = await cart.createSale(method.value);

    if (saleRes.offline) {
      status.value = t('offline.savedOffline') + ' — synchronisation automatique';
      setTimeout(() => router.push('/terminal'), 2000);
      return;
    }

    const paymentId = saleRes.sale?.payment?.id;

    if (method.value === 'cash') {
      status.value = `Paiement validé. Rendu monnaie : ${formatAmount(changeDue.value)}`;
      setTimeout(() => router.push('/terminal'), 1500);
      return;
    }

    if (method.value === 'card') {
      status.value = 'En attente de confirmation carte…';
      await cart.confirmCardPayment(paymentId, transactionId.value, receiptNumber.value);
      cart.clearCart();
      router.push('/terminal');
      return;
    }

    if (method.value === 'mobile_money') {
      status.value = 'En attente de confirmation mobile…';
      await cart.pollPaymentStatus(paymentId, 20, 3000);
      cart.clearCart();
      router.push('/terminal');
    }
  } catch (e) {
    if (e.message === 'MONTANT_INSUFFISANT') {
      error.value = 'Le montant reçu est inférieur au total du panier.';
    } else {
      error.value = e.response?.data?.error?.message || 'Échec du paiement';
    }
  } finally {
    processing.value = false;
  }
};
</script>

<style scoped>
/* ── Shell ────────────────────────────────────────────────── */
.payment-shell {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--background);
  overflow: hidden;
}

/* ── En-tête ──────────────────────────────────────────────── */
.payment-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 20px;
  background: var(--surface);
  border-bottom: 1px solid var(--outline-variant);
  flex-shrink: 0;
}

.btn-back {
  background: transparent;
  border: 1px solid var(--outline-variant);
  border-radius: 10px;
  color: var(--on-surface-muted);
  padding: 8px 14px;
  font-size: 13px;
  cursor: pointer;
  white-space: nowrap;
  transition: border-color 0.15s, color 0.15s;
}

.btn-back:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.payment-title {
  flex: 1;
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--on-surface);
}

.header-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.header-total-label {
  font-size: 11px;
  color: var(--on-surface-muted);
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.header-total-amount {
  font-size: 22px;
  font-weight: 900;
  color: var(--on-surface);
  font-feature-settings: "tnum";
}

/* ── Body ────────────────────────────────────────────────── */
.payment-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  max-width: 640px;
  margin: 0 auto;
  width: 100%;
}

/* ── Tuiles méthode ───────────────────────────────────────── */
.section-label {
  margin: 0 0 12px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--on-surface-muted);
}

.method-tiles {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.method-tile {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 20px 12px 16px;
  background: var(--surface);
  border: 2px solid var(--border);
  border-radius: 16px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, transform 0.1s;
}

.method-tile:hover {
  border-color: var(--primary);
  background: var(--surface-high);
}

.method-tile.active {
  border-color: var(--primary);
  background: rgba(237, 95, 30, 0.1);
}

.tile-icon {
  font-size: 32px;
}

.tile-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--on-surface);
  text-align: center;
}

.tile-check {
  position: absolute;
  top: 10px;
  right: 12px;
  font-size: 14px;
  font-weight: 900;
  color: var(--primary);
}

/* ── Détail formulaire ────────────────────────────────────── */
.detail-card {
  background: var(--surface);
  border: 1px solid var(--outline-variant);
  border-radius: 16px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.detail-fields {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.field-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted);
}

.amount-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.amount-input {
  flex: 1;
  height: 56px;
  font-size: 28px;
  font-weight: 900;
  text-align: right;
  background: var(--surface-high);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--on-surface);
  padding: 0 16px;
  font-feature-settings: "tnum";
  transition: border-color 0.15s, box-shadow 0.15s;
}

.amount-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

.amount-currency {
  font-size: 16px;
  font-weight: 700;
  color: var(--on-surface-muted);
  white-space: nowrap;
}

.text-input {
  height: 44px;
  background: var(--surface-high);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--on-surface);
  padding: 0 14px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.text-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

.text-input::placeholder {
  color: var(--on-surface-faint);
}

.hint {
  margin: 0;
  font-size: 12px;
  color: var(--on-surface-faint);
}

/* ── Rendu monnaie ────────────────────────────────────────── */
.change-display {
  margin-top: 4px;
}

.change-due {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 12px;
}

.change-due.success { background: rgba(52, 211, 153, 0.12); }
.change-due.warning { background: rgba(255, 184, 0, 0.12); }
.change-due.exact   { background: rgba(237, 95, 30, 0.1); }

.change-icon {
  font-size: 22px;
}

.change-due.success .change-icon { color: var(--success); }
.change-due.warning .change-icon { color: var(--warning); }
.change-due.exact   .change-icon { color: var(--primary); }

.change-label {
  margin: 0;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted);
}

.change-value {
  margin: 2px 0 0;
  font-size: 20px;
  font-weight: 900;
  color: var(--on-surface);
  font-feature-settings: "tnum";
}

.change-due.success .change-value { color: var(--success); }
.change-due.warning .change-value { color: var(--warning); }

/* ── Feedback ─────────────────────────────────────────────── */
.feedback {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.feedback-success {
  background: rgba(52, 211, 153, 0.12);
  color: var(--success);
  border: 1px solid rgba(52, 211, 153, 0.25);
}

.feedback-error {
  background: rgba(255, 107, 107, 0.12);
  color: var(--danger);
  border: 1px solid rgba(255, 107, 107, 0.25);
}

/* ── Footer — bouton confirmer ────────────────────────────── */
.payment-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--outline-variant);
  background: var(--surface);
  flex-shrink: 0;
}

.btn-confirm {
  width: 100%;
  max-width: 640px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  height: 58px;
  border: none;
  border-radius: 14px;
  font-size: 17px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--on-primary);
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(237, 95, 30, 0.35);
  transition: opacity 0.15s, transform 0.1s, box-shadow 0.15s;
}

.btn-confirm:not(:disabled):hover {
  opacity: 0.93;
  transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(237, 95, 30, 0.45);
}

.btn-confirm:disabled {
  opacity: 0.35;
  cursor: not-allowed;
  box-shadow: none;
}

.spinner {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
