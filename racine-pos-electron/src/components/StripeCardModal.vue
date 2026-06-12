<template>
  <div class="stripe-overlay" @click.self="cancel">
    <div class="stripe-card-modal">
      <div class="stripe-header">
        <h3 class="stripe-title">{{ t('payment.stripeTitle') }}</h3>
        <button class="stripe-close" :disabled="processing" @click="cancel">✕</button>
      </div>

      <div class="stripe-body">
        <div class="stripe-amount">
          <span class="stripe-amount-label">{{ t('terminal.total') }}</span>
          <span class="stripe-amount-value">{{ formattedAmount }}</span>
        </div>

        <!-- Chargement Stripe.js / clé publique -->
        <div v-if="loading" class="stripe-state">
          <span class="stripe-spinner">↻</span> {{ t('payment.stripeLoading') }}
        </div>

        <!-- Stripe indisponible (clé absente, CSP, réseau) -->
        <div v-else-if="fatalError" class="stripe-state stripe-state--error">
          ✗ {{ fatalError }}
        </div>

        <!-- Formulaire carte -->
        <template v-else>
          <label class="stripe-field-label">{{ t('payment.stripeCardLabel') }}</label>
          <div ref="cardElementRef" class="stripe-element-host"></div>
          <p v-if="cardError" class="stripe-card-error">{{ cardError }}</p>
        </template>
      </div>

      <div class="stripe-footer">
        <button class="stripe-btn stripe-btn--ghost" :disabled="processing" @click="cancel">
          {{ t('payment.cancel') }}
        </button>
        <button
          class="stripe-btn stripe-btn--primary"
          :disabled="loading || !!fatalError || !cardComplete || processing"
          @click="pay"
        >
          <span v-if="processing" class="stripe-spinner">↻</span>
          {{ processing ? t('payment.stripeProcessing') : t('payment.stripePay') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { loadStripe } from '@stripe/stripe-js';
import ApiService from '../services/api.js';

const { t } = useI18n();

const props = defineProps({
  /** Montant TOTAL dans la devise d'affichage (FCFA — unité entière, le XAF n'a pas de décimales). */
  amount: { type: Number, required: true },
  /** Devise ISO (minuscules) envoyée au backend pour le PaymentIntent. */
  currency: { type: String, default: 'xaf' },
});

const emit = defineEmits(['success', 'close']);

const loading = ref(true);
const fatalError = ref('');
const cardError = ref('');
const cardComplete = ref(false);
const processing = ref(false);
const cardElementRef = ref(null);

let stripe = null;
let cardElement = null;

const formattedAmount = computed(
  () => new Intl.NumberFormat('fr-FR').format(Math.round(props.amount)) + ' FCFA',
);

// Style Stripe Elements — charte stricte (#160D0C / #ED5F1E / #FFB800 / #FFFFFF).
const ELEMENT_STYLE = {
  base: {
    color: '#FFFFFF',
    fontFamily: 'inherit',
    fontSize: '16px',
    iconColor: '#FFB800',
    '::placeholder': { color: '#FFFFFF' },
  },
  invalid: {
    color: '#FFB800',
    iconColor: '#ED5F1E',
  },
};

onMounted(async () => {
  try {
    // 1. Clé publique : contrat ApiService (reçue au login) puis fallback env.
    const publishableKey =
      ApiService.getStripePublishableKey() ||
      import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY ||
      null;

    if (!publishableKey) {
      fatalError.value = t('payment.stripeNoKey');
      return;
    }

    // 2. Charger Stripe.js (script injecté depuis js.stripe.com — nécessite
    //    que la CSP du renderer autorise ce domaine, cf. rapport).
    stripe = await loadStripe(publishableKey);
    if (!stripe) {
      fatalError.value = t('payment.stripeUnavailable');
      return;
    }

    // 3. Monter l'élément carte.
    const elements = stripe.elements();
    cardElement = elements.create('card', { style: ELEMENT_STYLE, hidePostalCode: true });
    loading.value = false;
    // Attendre que le host soit rendu (v-else du template).
    await Promise.resolve();
    if (cardElementRef.value) {
      cardElement.mount(cardElementRef.value);
      cardElement.on('change', (event) => {
        cardComplete.value = !!event.complete;
        cardError.value = event.error ? event.error.message : '';
      });
    }
  } catch (e) {
    console.error('[StripeCardModal] init error:', e);
    fatalError.value = t('payment.stripeUnavailable');
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => {
  if (cardElement) {
    try { cardElement.destroy(); } catch { /* déjà détruit */ }
    cardElement = null;
  }
});

/**
 * Récupère un client_secret de PaymentIntent auprès du backend.
 *
 * ⚠️ DÉPENDANCE BACKEND : POST /api/pos/payments/stripe-intent
 *    body     : { amount, currency }
 *    réponse  : { success, data: { client_secret } }
 * Cet endpoint n'existe pas encore côté Laravel (cf. routes/api_pos.php) —
 * il est documenté comme dépendance dans le rapport de l'agent.
 */
async function fetchClientSecret() {
  const res = await ApiService.client.post('/payments/stripe-intent', {
    amount: Math.round(props.amount),
    currency: props.currency,
  });
  const body = res?.data || {};
  return body?.data?.client_secret || body?.client_secret || null;
}

const pay = async () => {
  if (!stripe || !cardElement || processing.value) return;
  processing.value = true;
  cardError.value = '';

  try {
    const clientSecret = await fetchClientSecret();
    if (!clientSecret) {
      cardError.value = t('payment.stripeIntentFailed');
      return;
    }

    const result = await stripe.confirmCardPayment(clientSecret, {
      payment_method: { card: cardElement },
    });

    if (result.error) {
      cardError.value = result.error.message || t('payment.stripeFailed');
      return;
    }

    if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
      emit('success', { paymentIntentId: result.paymentIntent.id });
      return;
    }

    cardError.value = t('payment.stripeFailed');
  } catch (e) {
    console.error('[StripeCardModal] payment error:', e);
    cardError.value =
      e?.response?.data?.error?.message ||
      e?.response?.data?.message ||
      t('payment.stripeIntentFailed');
  } finally {
    processing.value = false;
  }
};

const cancel = () => {
  if (processing.value) return;
  emit('close');
};
</script>

<style scoped>
/* Charte : Noir #160D0C · Orange #ED5F1E · Jaune #FFB800 · Blanc #FFFFFF */
.stripe-overlay {
  position: fixed;
  inset: 0;
  background: rgba(22, 13, 12, 0.82); /* #160D0C */
  display: grid;
  place-items: center;
  z-index: 120;
  padding: 24px;
}

.stripe-card-modal {
  background: var(--surface, #160D0C);
  border: 1px solid var(--outline-variant, rgba(255, 255, 255, 0.12));
  border-radius: 20px;
  width: min(440px, 100%);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(22, 13, 12, 0.7);
}

.stripe-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid var(--outline-variant, rgba(255, 255, 255, 0.12));
}

.stripe-title {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--on-surface, #FFFFFF);
}

.stripe-close {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 8px;
  background: var(--surface-high, rgba(255, 255, 255, 0.06));
  color: var(--on-surface-muted, #FFFFFF);
  cursor: pointer;
  display: grid;
  place-items: center;
}

.stripe-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.stripe-amount {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}

.stripe-amount-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted, #FFFFFF);
}

.stripe-amount-value {
  font-size: 22px;
  font-weight: 900;
  color: var(--on-surface, #FFFFFF);
  font-feature-settings: 'tnum';
}

.stripe-state {
  padding: 14px 16px;
  border-radius: 10px;
  background: var(--surface-high, rgba(255, 255, 255, 0.06));
  color: var(--on-surface, #FFFFFF);
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.stripe-state--error {
  background: rgba(237, 95, 30, 0.12); /* #ED5F1E */
  color: #ED5F1E;
  border: 1px solid rgba(237, 95, 30, 0.3);
}

.stripe-field-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--on-surface-muted, #FFFFFF);
}

.stripe-element-host {
  padding: 14px 16px;
  background: var(--surface-high, rgba(255, 255, 255, 0.06));
  border: 1px solid var(--border, rgba(255, 255, 255, 0.16));
  border-radius: 12px;
}

.stripe-card-error {
  margin: 0;
  font-size: 12px;
  font-weight: 600;
  color: #ED5F1E;
}

.stripe-footer {
  display: flex;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid var(--outline-variant, rgba(255, 255, 255, 0.12));
}

.stripe-btn {
  flex: 1;
  height: 46px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: opacity 0.15s;
}

.stripe-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.stripe-btn--ghost {
  background: transparent;
  border: 1px solid var(--outline-variant, rgba(255, 255, 255, 0.16));
  color: var(--on-surface, #FFFFFF);
}

.stripe-btn--primary {
  background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
  border: none;
  color: #FFFFFF;
}

.stripe-spinner {
  display: inline-block;
  animation: stripe-spin 0.8s linear infinite;
}

@keyframes stripe-spin {
  to { transform: rotate(360deg); }
}
</style>
