<template>
  <div class="modal">
    <slot />
  </div>
</template>

<script setup></script>

<style scoped>
.modal { background: var(--card); padding: 16px; border-radius: 12px; }
</style>
