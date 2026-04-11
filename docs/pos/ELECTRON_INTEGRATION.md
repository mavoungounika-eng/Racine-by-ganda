# Electron POS Integration Guide
Version 1.0.0 — Racine by Ganda
Base URL: http://127.0.0.1:8000

## 1. Authentication Flow

### Device Registration (once)
```javascript
const res = await fetch('http://127.0.0.1:8000/api/pos/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ machine_id: 'POS-001', name: 'Caisse principale' })
});
const { token, secret } = await res.json();
// Store securely with electron-store or keytar
```

### API Client with auto-refresh
```javascript
class PosApiClient {
  constructor(token) { this.token = token; }

  async request(method, path, body = null, idempotencyKey = null) {
    const headers = {
      'Authorization': `Bearer ${this.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (idempotencyKey) headers['X-Idempotency-Key'] = idempotencyKey;

    const res = await fetch(`http://127.0.0.1:8000/api/pos${path}`, {
      method, headers,
      body: body ? JSON.stringify(body) : null,
    });

    if (res.status === 401) {
      await this.refreshToken();
      return this.request(method, path, body, idempotencyKey);
    }
    return res.json();
  }

  async refreshToken() {
    const res = await fetch('http://127.0.0.1:8000/api/pos/auth/refresh', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    const data = await res.json();
    this.token = data.data.token;
  }
}
```

## 2. Session Lifecycle
```javascript
// Open session
const session = await api.request('POST', '/sessions/open',
  { opening_cash: 50000 },
  crypto.randomUUID() // idempotency key
);

// Get current session
const current = await api.request('GET', '/sessions/current');

// Pre-close reconciliation
const summary = await api.request('GET',
  `/sessions/${sessionId}/prepare-close`);

// Close session
const closed = await api.request('POST',
  `/sessions/${sessionId}/close`,
  { closing_cash: 75000, notes: 'Fin de journee' },
  crypto.randomUUID()
);

// Z-Report
const report = await api.request('GET',
  `/sessions/${sessionId}/z-report`);
```

## 3. Sale Creation with Idempotency
```javascript
const createSale = async (items, paymentMethod) => {
  const idempotencyKey = crypto.randomUUID();
  // Store key locally before sending (for retry)
  localStorage.setItem('last_sale_key', idempotencyKey);

  const sale = await api.request('POST', '/sales', {
    items, // [{ product_id, quantity }]
    payment_method: paymentMethod // cash | card | mobile_money
  }, idempotencyKey);

  localStorage.removeItem('last_sale_key');
  return sale;
};

// On app restart, check for incomplete sale
const pendingKey = localStorage.getItem('last_sale_key');
if (pendingKey) {
  // Retry with same key — server returns existing sale if already created
  await createSale(lastItems, lastMethod);
}
```

## 4. Offline Mode Handling
```javascript
class OfflineManager {
  async checkStatus() {
    return api.request('GET', '/offline/status');
  }

  async queueSale(saleData) {
    // Store locally when offline
    const queue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
    queue.push({ ...saleData, queued_at: new Date().toISOString() });
    localStorage.setItem('offline_queue', JSON.stringify(queue));
  }

  async syncWhenOnline() {
    // Check if back online
    const status = await this.checkStatus();
    if (!status.data.is_offline) {
      // Flush server queue
      const result = await api.request('POST', '/offline/queue/flush');
      console.log('Synced:', result.data);
    }
  }
}

// Listen for connectivity
window.addEventListener('online', () => offlineManager.syncWhenOnline());
```

## 5. Product Catalog
```javascript
// List with filters
const products = await api.request('GET',
  '/products?in_stock=true&per_page=50');

// Quick search (barcode scanner or name)
const results = await api.request('GET',
  `/products/search?q=${encodeURIComponent(query)}`);

// Categories for sidebar
const categories = await api.request('GET', '/products/categories');
```

## 6. Error Handling
```javascript
const handlePosError = (response) => {
  if (!response.success) {
    const { code, message } = response.error;
    switch (code) {
      case 'SESSION_ALREADY_OPEN':
        return showAlert('Une session est deja ouverte sur ce terminal.');
      case 'PAYMENT_ALREADY_CONFIRMED':
        return showAlert('Ce paiement a deja ete confirme.');
      case 'SALE_CREATION_FAILED':
        return showAlert('Erreur creation vente: ' + message);
      case 'UNAUTHORIZED':
        return redirectToLogin();
      default:
        return showAlert('Erreur: ' + message);
    }
  }
};
```

## 7. Rate Limiting
- Limit: 300 requests/minute per device
- On 429 response: wait 2 seconds then retry
```javascript
if (response.status === 429) {
  await new Promise(r => setTimeout(r, 2000));
  return this.request(method, path, body);
}
```

## 8. Payment Flows

### Cash
```javascript
// Sale created — cash confirmed at session close automatically
const sale = await api.request('POST', '/sales',
  { items, payment_method: 'cash' }, crypto.randomUUID());
```

### Card
```javascript
// Create sale then confirm after terminal approval
const sale = await api.request('POST', '/sales',
  { items, payment_method: 'card' }, crypto.randomUUID());

// After card terminal approves:
await api.request('POST',
  `/payments/${sale.data.payment.id}/confirm-card`, {
    transaction_id: terminalTransactionId,
    receipt_number: terminalReceiptNumber
  });
```

### Mobile Money
```javascript
// Create sale — confirmation arrives via Monetbil webhook automatically
const sale = await api.request('POST', '/sales',
  { items, payment_method: 'mobile_money' }, crypto.randomUUID());

// Poll payment status
const pollPayment = async (paymentId) => {
  const status = await api.request('GET', `/payments/${paymentId}/status`);
  if (status.data.payment.status === 'confirmed') return status;
  await new Promise(r => setTimeout(r, 3000));
  return pollPayment(paymentId);
};
```
