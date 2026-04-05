<template>
  <div class="terminal">
    <aside class="catalog">
      <h2>{{ t('terminal.products') }}</h2>
      <input v-model="search" placeholder="Search" @input="onSearch" />
      <div class="grid">
        <ProductCard v-for="p in productsStore.items" :key="p.id" :product="p" @add="addToCart" />
      </div>
    </aside>

    <section class="cart">
      <h2>{{ t('terminal.cart') }}</h2>
      <CartItem v-for="i in cart.items" :key="i.product_id" :item="i" @remove="cart.removeItem(i.product_id)" />
      <div class="total">{{ t('terminal.total') }}: {{ cart.total }} XAF</div>
      <button @click="goToPayment">{{ t('terminal.checkout') }}</button>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '../stores/cart';
import { useProductsStore } from '../stores/products';
import ProductCard from '../components/ProductCard.vue';
import CartItem from '../components/CartItem.vue';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const cart = useCartStore();
const productsStore = useProductsStore();
const router = useRouter();
const search = ref('');
let searchTimer = null;

const loadProducts = async () => {
  await productsStore.fetchProducts({ in_stock: true, per_page: 50 });
};

const onSearch = () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(async () => {
    if (search.value.length >= 2) {
      const results = await productsStore.searchProducts(search.value);
      productsStore.items = results;
    } else {
      await loadProducts();
    }
  }, 300);
};

const addToCart = (product) => cart.addItem(product);
const goToPayment = () => router.push('/payment');

onMounted(loadProducts);
</script>

<style scoped>
.terminal { display: grid; grid-template-columns: 2fr 1fr; height: 100vh; }
.catalog { padding: 16px; }
.cart { padding: 16px; background: #fff; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
button { background: var(--accent); color: white; border: none; padding: 12px 16px; border-radius: 10px; }
</style>
