<template>
  <div class="page">
    <h1>{{ t('payment.methods') }}</h1>
    <div class="card">
      <select v-model="method">
        <option value="cash">Cash</option>
        <option value="card">Card</option>
        <option value="mobile_money">Mobile Money</option>
      </select>

      <div v-if="method === 'cash'" class="field">
        <label>Montant reçu</label>
        <input v-model.number="receivedAmount" type="number" min="0" step="0.01" placeholder="Montant remis par le client" />
        <p v-if="changeDue > 0" class="status">Rendu monnaie: {{ formatAmount(changeDue) }}</p>
        <p v-else-if="remainingDue > 0" class="error">Reste à payer: {{ formatAmount(remainingDue) }}</p>
      </div>

      <div v-if="method === 'card'" class="field">
        <input v-model="transactionId" placeholder="Transaction ID" />
        <input v-model="receiptNumber" placeholder="Receipt Number" />
      </div>

      <div v-if="method === 'mobile_money'" class="field">
        <input v-model="phoneNumber" placeholder="Phone Number" />
      </div>

      <button @click="confirm">{{ t('payment.confirm') }}</button>
      <p v-if="status" class="status">{{ status }}</p>
      <p v-if="error" class="error">{{ error }}</p>
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

onMounted(() => {
  if (cart.items.length === 0) {
    router.push('/terminal');
    return;
  }

  receivedAmount.value = Number(cart.total || 0);
});

const formatAmount = (value) => new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0))) + ' FCFA';

const confirm = async () => {
  error.value = '';
  status.value = '';

  try {
    if (method.value === 'cash' && Number(receivedAmount.value || 0) < Number(cart.total || 0)) {
      throw new Error('MONTANT_INSUFFISANT');
    }

    const saleRes = await cart.createSale(method.value);

    if (saleRes.offline) {
      status.value = t('offline.savedOffline') + ' - ' + t('offline.autoSync');
      setTimeout(() => {
        router.push('/terminal');
      }, 2000);
      return;
    }

    const paymentId = saleRes.sale?.payment?.id;

    if (method.value === 'cash') {
      status.value = `Paiement validé. Rendu monnaie: ${formatAmount(changeDue.value)}`;
      setTimeout(() => {
        router.push('/terminal');
      }, 1200);
      return;
    }

    if (method.value === 'card') {
      status.value = 'Waiting for card confirmation...';
      await cart.confirmCardPayment(paymentId, transactionId.value, receiptNumber.value);
      cart.clearCart();
      router.push('/terminal');
      return;
    }

    if (method.value === 'mobile_money') {
      status.value = 'Waiting for mobile confirmation...';
      await cart.pollPaymentStatus(paymentId, 20, 3000);
      cart.clearCart();
      router.push('/terminal');
    }
  } catch (e) {
    if (e.message === 'MONTANT_INSUFFISANT') {
      error.value = 'Le montant reçu est inférieur au total du panier.';
      return;
    }

    error.value = e.response?.data?.error?.message || 'Payment failed';
  }
};
</script>

<style scoped>
.page { padding: 24px; }
.card { background: var(--card); padding: 16px; border-radius: 12px; display: grid; gap: 12px; }
.field { display: grid; gap: 8px; }
button { background: var(--accent); color: white; border: none; padding: 12px 16px; border-radius: 10px; }
.status { color: #0f766e; }
.error { color: var(--danger); }
</style>
