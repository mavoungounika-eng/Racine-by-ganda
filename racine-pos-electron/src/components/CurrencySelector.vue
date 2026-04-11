<template>
  <div class="currency-selector">
    <select v-model="currencyStore.currentCurrency" @change="handleSwitch" class="form-select form-select-sm">
      <option v-for="code in currencyStore.supportedCurrencies" :key="code" :value="code">
        {{ code }} ({{ currencyStore.symbols[code] }})
      </option>
    </select>
  </div>
</template>

<script setup>
import { useCurrencyStore } from '../stores/currency';

const currencyStore = useCurrencyStore();

const handleSwitch = (event) => {
  currencyStore.setCurrency(event.target.value);
};
</script>

<style scoped>
.currency-selector {
  margin-right: 15px;
}
.form-select {
  background-color: #2a2a2a;
  color: white;
  border: 1px solid #444;
  border-radius: 4px;
  padding: 2px 5px;
  cursor: pointer;
}
</style>
