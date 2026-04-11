import { defineStore } from 'pinia';

export const useCurrencyStore = defineStore('currency', {
  state: () => ({
    currentCurrency: 'XAF',
    supportedCurrencies: ['XAF', 'XOF', 'EUR'],
    rates: {
      XAF: { XOF: 1.0, EUR: 1 / 655.957 },
      XOF: { XAF: 1.0, EUR: 1 / 655.957 },
      EUR: { XAF: 655.957, XOF: 655.957 },
    },
    symbols: {
      XAF: 'FCFA',
      XOF: 'CFA',
      EUR: '€',
    },
    decimals: {
      XAF: 0,
      XOF: 0,
      EUR: 2,
    },
  }),
  actions: {
    setCurrency(currency) {
      if (this.supportedCurrencies.includes(currency)) {
        this.currentCurrency = currency;
      }
    },
    convert(amount, from, to) {
      if (from === to) return amount;
      const rate = this.rates[from]?.[to];
      if (!rate) return amount;
      const converted = amount * rate;
      const dec = this.decimals[to] || 0;
      return dec === 0 ? Math.round(converted) : Number(converted.toFixed(dec));
    },
    format(amount, currency = null) {
      const code = currency || this.currentCurrency;
      const symbol = this.symbols[code] || code;
      const dec = this.decimals[code] || 0;
      
      const formatted = amount.toLocaleString('fr-FR', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec,
      });

      return `${formatted} ${symbol}`;
    },
    getConvertedTotal(amountXaf) {
        return this.convert(amountXaf, 'XAF', this.currentCurrency);
    }
  },
});
