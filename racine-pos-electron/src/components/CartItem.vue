<template>
  <div class="item">
    <div>{{ item.name }} x {{ item.quantity }}</div>
    <div>{{ item.price * item.quantity }} XAF</div>
    <button @click="$emit('remove')">Supprimer</button>
  </div>
</template>

<script setup>
defineProps({
  item: { type: Object, required: true },
});
</script>

<style scoped>
.item { display: flex; justify-content: space-between; padding: 8px 0; }
button { background: var(--danger); color: white; border: none; padding: 6px 10px; border-radius: 6px; }
</style>
