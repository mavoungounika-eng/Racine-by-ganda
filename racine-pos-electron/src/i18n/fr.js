export default {
  login: {
    title: 'Connexion POS',
    device: 'Enregistrer le terminal',
    operator: 'Opérateur',
    button: 'Entrer',
  },
  session: {
    open: 'Ouvrir la session',
    close: 'Clôturer la session',
    cash: 'Caisse',
    discrepancy: 'Écart',
  },
  terminal: {
    products: 'Produits',
    cart: 'Panier',
    total: 'Total',
    checkout: 'Paiement',
  },
  payment: {
    methods: 'Modes de paiement',
    confirm: 'Confirmer',
    cancel: 'Annuler',
  },
  offline: {
    status: 'Hors ligne',
    online: 'En ligne',
    pending: '{count} vente(s) en attente',
    syncing: 'Synchronisation...',
    syncNow: 'Synchroniser maintenant',
    lastSync: 'Dernière sync: {time}',
    savedOffline: 'Vente enregistrée hors ligne',
    autoSync: 'Sync automatique à la reconnexion',
    noConnection: 'Pas de connexion',
    queueEmpty: 'File vide',
  },
  errors: {
    UNAUTHORIZED: 'Accès non autorisé',
    SESSION_ALREADY_OPEN: 'Session déjà ouverte',
    SESSION_NOT_FOUND: 'Session introuvable',
    PAYMENT_CONFIRM_FAILED: 'Confirmation paiement échouée',
  },
};
