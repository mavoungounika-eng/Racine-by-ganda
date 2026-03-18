export default {
  login: {
    title: 'POS Login',
    device: 'Register device',
    operator: 'Operator',
    button: 'Enter',
  },
  session: {
    open: 'Open session',
    close: 'Close session',
    cash: 'Cash',
    discrepancy: 'Discrepancy',
  },
  terminal: {
    products: 'Products',
    cart: 'Cart',
    total: 'Total',
    checkout: 'Checkout',
  },
  payment: {
    methods: 'Payment methods',
    confirm: 'Confirm',
    cancel: 'Cancel',
  },
  offline: {
    status: 'Offline',
    online: 'Online',
    pending: '{count} sale(s) pending',
    syncing: 'Syncing...',
    syncNow: 'Sync now',
    lastSync: 'Last sync: {time}',
    savedOffline: 'Sale saved offline',
    autoSync: 'Auto-sync on reconnection',
    noConnection: 'No connection',
    queueEmpty: 'Queue empty',
  },
  errors: {
    UNAUTHORIZED: 'Unauthorized',
    SESSION_ALREADY_OPEN: 'Session already open',
    SESSION_NOT_FOUND: 'Session not found',
    PAYMENT_CONFIRM_FAILED: 'Payment confirmation failed',
  },
};
