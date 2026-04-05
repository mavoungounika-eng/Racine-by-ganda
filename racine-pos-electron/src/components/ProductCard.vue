<template>
  <div class="card">
    <div class="name">{{ product.name }}</div>
    <div class="price">{{ product.price }} XAF</div>
    <button @click="$emit('add', product)">Ajouter</button>
  </div>
</template>

<script setup>
defineProps({
  product: { type: Object, required: true },
});
</script>

<style scoped>
.card { background: var(--card); padding: 12px; border-radius: 10px; }
button { background: var(--accent); color: white; border: none; padding: 8px 12px; border-radius: 8px; }
</style>
