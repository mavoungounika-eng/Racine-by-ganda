<template>
  <div class="page">
    <h1>{{ t('payment.methods') }}</h1>
    <div class="card">
      <select v-model="method">
        <option value="cash">Cash</option>
        <option value="card">Card</option>
        <option value="mobile_money">Mobile Money</option>
      </select>

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
import { ref, onMounted } from 'vue';
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
const status = ref('');
const error = ref('');

onMounted(() => {
  if (cart.items.length === 0) router.push('/terminal');
});

const confirm = async () => {
  error.value = '';
  status.value = '';
  try {
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
      router.push('/terminal');
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
    error.value = e.response?.data?.error?.message || 'Payment failed';
  }
};
</script>

<style scoped>
.page { padding: 24px; }
.card { background: var(--card); padding: 16px; border-radius: 12px; display: grid; gap: 12px; }
button { background: var(--accent); color: white; border: none; padding: 12px 16px; border-radius: 10px; }
.status { color: #0f766e; }
.error { color: var(--danger); }
</style>
