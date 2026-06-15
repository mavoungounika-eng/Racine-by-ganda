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
        <span class="header-total-amount">{{ formatAmount(totalAfterDiscount) }}</span>
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
            :class="{ active: method === m.value, 'tile-disabled': isMethodDisabled(m) }"
            :disabled="isMethodDisabled(m)"
            :title="isMethodDisabled(m) ? t('payment.offlineOnlyCash') : ''"
            @click="selectMethod(m)"
          >
            <span class="tile-icon">{{ m.icon }}</span>
            <span class="tile-label">{{ m.label }}</span>
            <span v-if="method === m.value" class="tile-check">✓</span>
            <span v-if="isMethodDisabled(m)" class="tile-offline">{{ t('offline.status') }}</span>
          </button>
        </div>
        <p v-if="offline.isOffline" class="offline-note">
          ⚠ {{ t('payment.offlineOnlyCash') }}
        </p>
      </div>

      <!-- Formulaire contextuel -->
      <div class="detail-card">
          <!-- REMISE OPTIONNELLE -->
        <div class="discount-section">
          <p class="section-label">Remise optionnelle</p>
          <div class="discount-row">
            <input
              v-model.number="discount_percent"
              class="discount-input"
              type="number"
              min="0"
              max="100"
              step="0.1"
              placeholder="0"
              :disabled="!!cart.coupon_code"
            />
            <span class="discount-unit">%</span>
            <span class="discount-value">−{{ formatAmount(discountAmount) }}</span>
          </div>
        </div>

        <!-- CODE PROMO -->
        <div class="coupon-section">
          <p class="section-label">Code promo <span class="optional">(optionnel)</span></p>
          <div v-if="!cart.coupon_code" class="coupon-row">
            <input
              v-model="couponCode"
              class="coupon-input"
              placeholder="Entrez un code"
              @keyup.enter="applyCoupon"
            />
            <button
              class="btn-apply-coupon"
              @click="applyCoupon"
              :disabled="!couponCode || validatingCoupon"
            >
              {{ validatingCoupon ? '⏳' : '✓' }}
            </button>
          </div>
          <div v-else class="coupon-applied">
            <span class="coupon-badge">{{ cart.coupon_code }}</span>
            <span class="coupon-discount">−{{ formatAmount(cart.coupon_discount) }}</span>
            <button class="btn-remove-coupon" @click="removeCoupon">✕</button>
          </div>
          <div v-if="couponError" class="coupon-error">{{ couponError }}</div>
        </div>

        <!-- INFOS CLIENT OPTIONNELLES -->
        <div class="customer-section">
          <p class="section-label">Informations client <span class="optional">(optionnel)</span></p>
          <input
            v-model="customer_name"
            class="text-input"
            placeholder="Nom du client"
          />
          <input
            v-model="customer_phone"
            class="text-input"
            placeholder="Téléphone"
            type="tel"
          />
        </div>
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

        <!-- MONETBIL (fenêtre de paiement dédiée) -->
        <div v-if="method === 'monetbil'" class="detail-fields">
          <label class="field-label">{{ t('payment.monetbilPhoneLabel') }}</label>
          <input
            v-model="phoneNumber"
            class="text-input"
            placeholder="+237 6XX XXX XXX"
            type="tel"
          />
          <p class="hint">{{ t('payment.monetbilHint') }}</p>
        </div>

        <!-- STRIPE (modale Stripe Elements) -->
        <div v-if="method === 'stripe'" class="detail-fields">
          <p class="hint">{{ t('payment.stripeHint') }}</p>
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

    <!-- MODAL REÇU POST-PAIEMENT -->
    <div v-if="showReceiptModal" class="modal-overlay" @click.self="showReceiptModal = false">
      <div class="modal-card modal-card--narrow">
        <div class="modal-header">
          <h3 class="modal-title">🧾 Reçu de vente</h3>
          <button class="modal-close" @click="showReceiptModal = false">✕</button>
        </div>
        <div class="modal-body">
          <ReceiptPrint v-if="receiptData" ref="receiptRef" :sale="receiptData" />
        </div>
        <div class="modal-footer">
          <button class="btn-action" @click="printReceipt">🖨 {{ t('receipt.print') }}</button>
          <button class="btn-action btn-action--primary" @click="newSale">+ {{ t('receipt.newSale') }}</button>
        </div>
      </div>
    </div>

    <!-- MODALE STRIPE ELEMENTS -->
    <StripeCardModal
      v-if="showStripeModal"
      :amount="totalAfterDiscount"
      currency="xaf"
      @success="onStripeSuccess"
      @close="showStripeModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import { useOfflineStore } from '../stores/offline';
import { useRouter } from 'vue-router';
import ApiService from '../services/api.js';
import OfflineStore from '../services/offlineStore.js';
import ReceiptPrint from '../components/ReceiptPrint.vue';
import StripeCardModal from '../components/StripeCardModal.vue';

const { t } = useI18n();
const cart = useCartStore();
const auth = useAuthStore();
const offline = useOfflineStore();
const router = useRouter();

const method = ref('cash');
const transactionId = ref('');
const receiptNumber = ref('');
const phoneNumber = ref('');
const receivedAmount = ref(0);
const discount_percent = ref(0);
const couponCode = ref('');
const validatingCoupon = ref(false);
const couponError = ref('');
const customer_name = ref('');
const customer_phone = ref('');
const status = ref('');
const error = ref('');
const processing = ref(false);
const showReceiptModal = ref(false);
const showStripeModal = ref(false);
const receiptData = ref(null);
const receiptRef = ref(null);

const methods = [
  { value: 'cash',         icon: '💵', label: 'Espèces' },
  { value: 'card',         icon: '💳', label: 'Carte bancaire' },
  { value: 'mobile_money', icon: '📱', label: 'Mobile Money' },
  { value: 'monetbil',     icon: '📲', label: 'Monetbil',       onlineOnly: true },
  { value: 'stripe',       icon: '🌐', label: 'Carte (Stripe)', onlineOnly: true },
];

// Monetbil / Stripe nécessitent le réseau : tuiles désactivées hors-ligne.
const isMethodDisabled = (m) => !!m.onlineOnly && offline.isOffline;

const selectMethod = (m) => {
  if (isMethodDisabled(m)) return;
  method.value = m.value;
};

// Si la connexion tombe pendant la sélection → retour forcé sur Espèces.
watch(() => offline.isOffline, (isOff) => {
  if (isOff && methods.find((m) => m.value === method.value)?.onlineOnly) {
    method.value = 'cash';
    showStripeModal.value = false;
  }
});

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

const labelPaymentMethod = (m) => {
  const methods = {
    cash: 'Espèces',
    card: 'Carte bancaire',
    mobile_money: 'Mobile Money',
    monetbil: 'Monetbil',
    stripe: 'Carte (Stripe)',
  };
  return methods[m] || m || '—';
};

const discountAmount = computed(() => {
  return Number(cart.total || 0) * (Number(discount_percent.value || 0) / 100);
});

const totalAfterDiscount = computed(() => {
  const baseTotal = Number(cart.total || 0);
  const manualDiscount = baseTotal * (Number(discount_percent.value || 0) / 100);
  const couponDiscount = Number(cart.coupon_discount || 0);
  return baseTotal - manualDiscount - couponDiscount;
});

const changeDue = computed(() => {
  const due = totalAfterDiscount.value;
  const paid = Number(receivedAmount.value || 0);
  return Math.max(paid - due, 0);
});

const remainingDue = computed(() => {
  const due = totalAfterDiscount.value;
  const paid = Number(receivedAmount.value || 0);
  return Math.max(due - paid, 0);
});

const canConfirm = computed(() => {
  if (method.value === 'cash') return Number(receivedAmount.value) >= totalAfterDiscount.value;
  if (method.value === 'card') return transactionId.value.trim().length > 0;
  if (method.value === 'mobile_money') return phoneNumber.value.trim().length >= 8;
  if (method.value === 'monetbil') return !offline.isOffline;
  if (method.value === 'stripe') return !offline.isOffline;
  return false;
});

onMounted(() => {
  if (cart.items.length === 0) {
    router.push('/terminal');
    return;
  }
  discount_percent.value = cart.discount_percent || 0;
  customer_name.value = cart.customer_name || '';
  customer_phone.value = cart.customer_phone || '';
  couponCode.value = '';
  couponError.value = '';
  receivedAmount.value = Number(totalAfterDiscount.value || 0);
  
  // Raccourci Escape pour revenir
  const handleEscape = (e) => {
    if (e.key === 'Escape' && !showReceiptModal.value) {
      router.push('/terminal');
    }
  };
  window.addEventListener('keydown', handleEscape);
  
  return () => window.removeEventListener('keydown', handleEscape);
});

// ── Helpers commande (contrat ApiService / OfflineStore) ─────────────────
// Payload contrat : { items: [{product_id, quantity, unit_price}],
//   payment_method, total, monetbil_ref?, stripe_ref?, offline_id? }
const buildOrderPayload = (paymentMethod, extra = {}) => ({
  items: cart.items.map((i) => ({
    product_id: i.product_id,
    quantity: i.quantity,
    unit_price: Number(i.price),
  })),
  payment_method: paymentMethod,
  total: Math.round(Number(totalAfterDiscount.value || 0)),
  ...extra,
});

// Snapshot du panier pour le reçu (le panier est vidé après la vente).
const snapshotReceipt = (paymentMethod, reference, change = null) => ({
  creatorName: ApiService.auth?.creator?.name || auth.operator?.name || null,
  items: cart.items.map((i) => ({
    name: i.name,
    quantity: i.quantity,
    unit_price: Number(i.price),
    subtotal: Number(i.price) * i.quantity,
  })),
  total: Math.round(Number(totalAfterDiscount.value || 0)),
  methodLabel: labelPaymentMethod(paymentMethod),
  date: new Date().toISOString(),
  reference: reference || null,
  change,
  discountLabel:
    discount_percent.value > 0
      ? `Remise (${discount_percent.value}%)`
      : (cart.coupon_code ? `Code promo (${cart.coupon_code})` : null),
  discountAmount:
    discount_percent.value > 0
      ? discountAmount.value
      : (cart.coupon_code ? Number(cart.coupon_discount || 0) : null),
});

const openReceipt = (paymentMethod, reference, change = null) => {
  receiptData.value = snapshotReceipt(paymentMethod, reference, change);
  cart.clearCart();
  showReceiptModal.value = true;
};

/**
 * Crée la commande côté backend via le contrat ApiService.createOrder().
 * Retourne la référence de vente, ou lève une erreur message-utilisateur.
 */
const submitOrder = async (payload) => {
  const res = await ApiService.createOrder(payload);
  if (!res?.success) {
    if (res?.offline) {
      // Le réseau est tombé entre-temps → bascule offline.
      const offlineId = await OfflineStore.savePendingOrder({ ...payload });
      return { offline: true, reference: offlineId };
    }
    throw new Error(res?.message || t('payment.orderFailed'));
  }
  const d = res.data || {};
  const reference = d.order?.id ?? d.sale?.id ?? d.id ?? d.reference ?? null;
  return { offline: false, reference: reference != null ? `#${reference}` : null };
};

// ── Flux MONETBIL : fenêtre main-process via IPC (contextBridge) ─────────
const payWithMonetbil = async () => {
  const bridge = window.electron?.payments;
  if (!bridge?.openMonetbilWindow) {
    throw new Error(t('payment.monetbilUnavailable'));
  }

  status.value = t('payment.monetbilWaiting');

  // 1. Init backend : récupère l'URL de paiement si l'endpoint existe.
  //    ⚠ DÉPENDANCE BACKEND : POST /api/pos/payments/monetbil/init
  //      body { amount, currency, phone? } → { payment_url, return_url }.
  //    Absent à ce jour (cf. routes/api_pos.php) → fallback : le main
  //    process construit l'URL widget Monetbil v2.1 lui-même.
  let openOpts = null;
  try {
    const initRes = await ApiService.client.post('/payments/monetbil/init', {
      amount: Math.round(Number(totalAfterDiscount.value || 0)),
      currency: 'XAF',
      phone: phoneNumber.value || null,
    });
    const d = initRes?.data?.data || initRes?.data || {};
    if (d.payment_url) {
      openOpts = { paymentUrl: d.payment_url, returnUrl: d.return_url || undefined };
    }
  } catch {
    // Endpoint absent / erreur — on passe au fallback widget.
  }

  if (!openOpts) {
    openOpts = {
      serviceKey: import.meta.env.VITE_MONETBIL_SERVICE_KEY || null,
      amount: Math.round(Number(totalAfterDiscount.value || 0)),
      currency: 'XAF',
      phone: phoneNumber.value || null,
      paymentRef: (crypto.randomUUID ? crypto.randomUUID() : String(Date.now())),
    };
  }

  // 2. Fenêtre de paiement (bloque jusqu'à retour / fermeture).
  const result = await bridge.openMonetbilWindow(openOpts);

  if (result?.status === 'cancelled') {
    status.value = '';
    error.value = t('payment.monetbilCancelled');
    return;
  }
  if (result?.status !== 'success' || !result?.monetbil_ref) {
    status.value = '';
    error.value = t('payment.monetbilFailed');
    return;
  }

  // 3. Enregistrer la vente avec la référence Monetbil.
  const order = await submitOrder(
    buildOrderPayload('monetbil', { monetbil_ref: result.monetbil_ref }),
  );
  status.value = t('payment.success');
  openReceipt('monetbil', order.reference || result.monetbil_ref);
};

// ── Flux STRIPE : succès renvoyé par la modale Stripe Elements ───────────
const onStripeSuccess = async ({ paymentIntentId }) => {
  showStripeModal.value = false;
  processing.value = true;
  error.value = '';
  try {
    const order = await submitOrder(
      buildOrderPayload('stripe', { stripe_ref: paymentIntentId }),
    );
    status.value = t('payment.success');
    openReceipt('stripe', order.reference || paymentIntentId);
  } catch (e) {
    error.value = e.message || t('payment.orderFailed');
  } finally {
    processing.value = false;
  }
};

const confirm = async () => {
  error.value = '';
  status.value = '';
  processing.value = true;

  try {
    if (method.value === 'cash' && Number(receivedAmount.value || 0) < totalAfterDiscount.value) {
      throw new Error('MONTANT_INSUFFISANT');
    }

    // Mettre à jour le store avec les valeurs actuelles
    cart.discount_percent = discount_percent.value;
    cart.customer_name = customer_name.value || null;
    cart.customer_phone = customer_phone.value || null;

    // ── ESPÈCES : contrat ApiService.createOrder / OfflineStore ──────────
    if (method.value === 'cash') {
      const payload = buildOrderPayload('cash');
      const change = changeDue.value;

      if (offline.isOffline) {
        const offlineId = await OfflineStore.savePendingOrder(payload);
        status.value = t('offline.savedOffline') + ' — ' + t('offline.autoSync');
        openReceipt('cash', offlineId, change);
        return;
      }

      const order = await submitOrder(payload);
      if (order.offline) {
        status.value = t('offline.savedOffline') + ' — ' + t('offline.autoSync');
        openReceipt('cash', order.reference, change);
        return;
      }

      status.value = `Paiement validé. Rendu monnaie : ${formatAmount(change)}`;
      openReceipt('cash', order.reference, change);
      return;
    }

    // ── MONETBIL : fenêtre de paiement dédiée (main process) ─────────────
    if (method.value === 'monetbil') {
      if (offline.isOffline) throw new Error(t('payment.offlineOnlyCash'));
      await payWithMonetbil();
      return;
    }

    // ── STRIPE : ouvre la modale Stripe Elements (suite dans onStripeSuccess)
    if (method.value === 'stripe') {
      if (offline.isOffline) throw new Error(t('payment.offlineOnlyCash'));
      showStripeModal.value = true;
      return;
    }

    // ── Flux existants (carte manuelle / mobile money) — inchangés ───────
    const pendingReceipt = snapshotReceipt(method.value, null, null);
    const saleRes = await cart.createSale(method.value);

    if (saleRes.offline) {
      status.value = t('offline.savedOffline') + ' — synchronisation automatique';
      setTimeout(() => router.push('/terminal'), 2000);
      return;
    }

    const paymentId = saleRes.sale?.payment?.id;
    pendingReceipt.reference = saleRes.sale?.id != null ? `#${saleRes.sale.id}` : null;

    if (method.value === 'card') {
      status.value = 'En attente de confirmation carte…';
      await cart.confirmCardPayment(paymentId, transactionId.value, receiptNumber.value);
      receiptData.value = pendingReceipt;
      showReceiptModal.value = true;
      return;
    }

    if (method.value === 'mobile_money') {
      status.value = 'En attente de confirmation mobile…';
      await cart.pollPaymentStatus(paymentId, 20, 3000);
      receiptData.value = pendingReceipt;
      showReceiptModal.value = true;
    }
  } catch (e) {
    if (e.message === 'MONTANT_INSUFFISANT') {
      error.value = 'Le montant reçu est inférieur au total du panier.';
    } else {
      error.value = e.response?.data?.error?.message || e.message || 'Échec du paiement';
    }
  } finally {
    processing.value = false;
  }
};

const printReceipt = () => {
  if (receiptRef.value?.print) {
    receiptRef.value.print();
  } else {
    window.print();
  }
};

const newSale = () => {
  cart.clearCart();
  showReceiptModal.value = false;
  router.push('/terminal');
};

const applyCoupon = async () => {
  if (!couponCode.value.trim()) return;
  
  validatingCoupon.value = true;
  couponError.value = '';
  
  try {
    const result = await cart.validateCoupon(couponCode.value, totalAfterDiscount.value);
    
    if (result.success) {
      const discountValue = result.data?.discount_value || 0;
      cart.applyCoupon(couponCode.value, discountValue);
      couponCode.value = '';
    } else {
      couponError.value = result.error || 'Impossible de valider le code';
    }
  } catch (e) {
    couponError.value = 'Erreur lors de la validation';
    console.error('Coupon validation error:', e);
  } finally {
    validatingCoupon.value = false;
  }
};

const removeCoupon = () => {
  couponError.value = '';
  cart.removeCoupon();
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

.discount-section, .customer-section {
  padding: 16px 20px;
  border-bottom: 1px solid var(--outline-variant);
}

.coupon-section {
  padding: 16px 20px;
  border-bottom: 1px solid var(--outline-variant);
}

.discount-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.discount-input {
  width: 80px;
  height: 40px;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0 12px;
  font-size: 14px;
  color: var(--on-surface);
  background: var(--surface-high);
}

.discount-unit {
  font-size: 14px;
  font-weight: 700;
  color: var(--on-surface-muted);
}

.discount-value {
  font-size: 14px;
  font-weight: 700;
  color: var(--primary);
  margin-left: auto;
}

.section-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--on-surface-muted);
  margin: 0 0 12px;
}

.optional {
  font-size: 10px;
  font-weight: 400;
  text-transform: none;
  color: var(--on-surface-faint);
  margin-left: 4px;
}

.text-input {
  width: 100%;
  height: 40px;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0 12px;
  font-size: 14px;
  color: var(--on-surface);
  background: var(--surface-high);
  margin-bottom: 10px;
}

.text-input:last-child {
  margin-bottom: 0;
}

.text-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

.coupon-row {
  display: flex;
  gap: 8px;
}

.coupon-input {
  flex: 1;
  height: 40px;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0 12px;
  font-size: 14px;
  color: var(--on-surface);
  background: var(--surface-high);
  text-transform: uppercase;
}

.coupon-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.18);
}

.btn-apply-coupon {
  width: 40px;
  height: 40px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface-high);
  color: var(--on-surface);
  cursor: pointer;
  font-size: 14px;
  font-weight: 700;
  display: grid;
  place-items: center;
  transition: all 0.15s;
}

.btn-apply-coupon:hover:not(:disabled) {
  border-color: var(--primary);
  background: rgba(237, 95, 30, 0.1);
  color: var(--primary);
}

.btn-apply-coupon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.coupon-applied {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(52, 211, 153, 0.1);
  border: 1px solid rgba(52, 211, 153, 0.3);
  border-radius: 10px;
  padding: 10px 12px;
}

.coupon-badge {
  font-size: 13px;
  font-weight: 700;
  color: var(--on-surface);
  text-transform: uppercase;
  flex: 1;
}

.coupon-discount {
  font-size: 13px;
  font-weight: 700;
  color: var(--primary);
}

.btn-remove-coupon {
  width: 24px;
  height: 24px;
  border: 1px solid rgba(255, 107, 107, 0.3);
  border-radius: 6px;
  background: transparent;
  color: var(--danger);
  cursor: pointer;
  font-size: 12px;
  display: grid;
  place-items: center;
  transition: all 0.15s;
}

.btn-remove-coupon:hover {
  border-color: var(--danger);
  background: rgba(255, 107, 107, 0.1);
}

.coupon-error {
  margin-top: 8px;
  font-size: 12px;
  color: var(--danger);
  font-weight: 600;
}

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
  width: min(420px, 100%);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
  overflow: hidden;
}

.modal-card--narrow {
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

.receipt {
  font-family: monospace;
  font-size: 11px;
  line-height: 1.6;
  color: var(--on-surface);
}

.receipt-header {
  text-align: center;
  margin-bottom: 12px;
}

.receipt-brand {
  margin: 0;
  font-weight: 900;
  font-size: 13px;
}

.receipt-sub {
  margin: 2px 0;
  font-size: 10px;
  color: var(--on-surface-muted);
}

.receipt-sep {
  margin: 6px 0;
  white-space: pre;
}

.receipt-row {
  display: flex;
  justify-content: space-between;
  margin: 4px 0;
}

.receipt-total {
  display: flex;
  justify-content: space-between;
  margin: 6px 0;
  font-weight: 900;
  font-size: 12px;
}

.receipt-footer {
  margin: 4px 0;
  text-align: center;
  font-size: 10px;
  color: var(--on-surface-muted);
}

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
  border-color: var(--primary);
  background: rgba(237, 95, 30, 0.06);
}

.btn-action--primary {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
  color: var(--on-primary);
  border: none;
}

.btn-action--primary:hover {
  opacity: 0.93;
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

/* Tuile désactivée hors-ligne (Monetbil / Stripe) */
.method-tile.tile-disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.method-tile.tile-disabled:hover {
  border-color: var(--border);
  background: var(--surface);
}

.tile-offline {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #FFB800; /* jaune charte */
}

.offline-note {
  margin: 12px 0 0;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #FFB800; /* jaune charte */
  background: rgba(255, 184, 0, 0.1);
  border: 1px solid rgba(255, 184, 0, 0.3);
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
