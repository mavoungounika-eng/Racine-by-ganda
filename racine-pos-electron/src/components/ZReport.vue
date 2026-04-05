<template>
  <div class="report">
    <pre>{{ report }}</pre>
  </div>
</template>

<script setup>
defineProps({
  report: { type: Object, required: true },
});
</script>

<style scoped>
.report { background: var(--card); padding: 12px; border-radius: 10px; }
</style>
