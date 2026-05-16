@extends('layouts.admin-master')

@section('title', 'Transactions - Payments Hub - RACINE BY GANDA')
@section('page-title', 'Transactions')
@section('page-subtitle', 'Liste et gestion des transactions de paiement')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'txns',
    'bulkActions' => []
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Transactions</h5>
    <a href="{{ route('admin.payments.transactions.export.csv') }}" class="al-action-btn">Export CSV</a>
  </div>

  {{-- Filtres --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" id="txns-search" class="al-filter-input" placeholder="Ref, transaction ID, téléphone…" style="min-width:240px;">
    <select id="txns-provider" class="al-filter-select">
      <option value="">Tous les providers</option>
      <option value="stripe">Stripe</option>
      <option value="monetbil">Monetbil</option>
    </select>
    <select id="txns-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="succeeded">Réussie</option>
      <option value="pending">En attente</option>
      <option value="processing">En cours</option>
      <option value="failed">Échouée</option>
      <option value="canceled">Annulée</option>
      <option value="refunded">Remboursée</option>
    </select>
    <input type="date" id="txns-date-from" class="al-filter-input" title="Date début">
    <input type="date" id="txns-date-to" class="al-filter-input" title="Date fin">
  </div>

  {{-- Stats --}}
  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-succeeded" style="color:#4ade80;">—</span><small>Réussies</small></div>
    <div class="al-stat-item"><span id="stat-failed" style="color:#f87171;">—</span><small>Échouées</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
  </div>

  {{-- Tableau --}}
  <div class="table-responsive">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th>Référence</th>
          <th>Provider</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Commande</th>
          <th>Téléphone</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="txns-tbody"><tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  <div class="al-pag-bar mt-3" id="txns-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.payments.transactions.data") }}';
  let page = 1, search = '', provider = '', status = '', dateFrom = '', dateTo = '';

  function statusBadge(s) {
    const map = {
      succeeded: ['#4ade80','Réussie'],
      pending: ['#fbbf24','En attente'],
      processing: ['#60a5fa','En cours'],
      failed: ['#f87171','Échouée'],
      canceled: ['#f87171','Annulée'],
      refunded: ['#fb923c','Remboursée'],
    };
    const [color, label] = map[s] || ['#aaa', s || '—'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function providerBadge(p) {
    if (p === 'stripe') return '<span class="badge" style="background:rgba(96,165,250,.15);color:#60a5fa;border:1px solid rgba(96,165,250,.3);">Stripe</span>';
    if (p === 'monetbil') return '<span class="badge" style="background:rgba(255,184,0,.15);color:#FFB800;border:1px solid rgba(255,184,0,.3);">Monetbil</span>';
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:#aaa;">${p || '—'}</span>`;
  }

  function fmt(amount, currency) {
    if (!amount && amount !== 0) return '—';
    try { return new Intl.NumberFormat('fr-FR', { style:'currency', currency: currency||'XAF', minimumFractionDigits:0 }).format(amount); }
    catch(e) { return amount + ' ' + (currency||''); }
  }

  function renderTable(data) {
    const tbody = document.getElementById('txns-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(t => `
        <tr>
          <td><code style="color:#ED5F1E;font-size:.75em;">${t.payment_ref || t.transaction_id || '—'}</code></td>
          <td>${providerBadge(t.provider)}</td>
          <td style="color:#eee;font-weight:600;">${fmt(t.amount, t.currency)}</td>
          <td>${statusBadge(t.status)}</td>
          <td style="color:#aaa;font-size:.85rem;">${t.order ? '#'+t.order.order_number : (t.order_id ? '#'+t.order_id : '—')}</td>
          <td style="color:#aaa;font-size:.85rem;">${t.phone || '—'}</td>
          <td style="color:#aaa;font-size:.8rem;">${t.created_at ? t.created_at.substring(0,16) : '—'}</td>
          <td>
            <a href="/admin/payments/transactions/${t.id}" class="al-action-btn" style="font-size:.75rem;">Voir</a>
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('txns-pag');
    pag.innerHTML = '';
    if (data.last_page <= 1) return;
    const prev = document.createElement('button');
    prev.textContent = '← Préc.'; prev.className = 'al-action-btn'; prev.disabled = data.current_page <= 1;
    prev.onclick = () => load(data.current_page - 1);
    const next = document.createElement('button');
    next.textContent = 'Suiv. →'; next.className = 'al-action-btn'; next.disabled = data.current_page >= data.last_page;
    next.onclick = () => load(data.current_page + 1);
    const info = document.createElement('span');
    info.textContent = `Page ${data.current_page} / ${data.last_page} (${data.total})`;
    info.style.cssText = 'color:#aaa;font-size:.85rem;';
    pag.append(prev, info, next);
  }

  function load(p) {
    page = p || 1;
    const params = new URLSearchParams({ page, per_page: 20 });
    if (search) params.set('search', search);
    if (provider) params.set('provider', provider);
    if (status) params.set('status', status);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(() => AL.toast('Erreur chargement', false));
  }

  function loadStats() {
    fetch(`${DATA_URL}?per_page=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-total').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=succeeded`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-succeeded').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=failed`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-failed').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=pending`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-pending').textContent = d.total ?? '—'; });
  }

  document.addEventListener('DOMContentLoaded', function() {
    let t;
    document.getElementById('txns-search').addEventListener('input', function() {
      search = this.value; clearTimeout(t); t = setTimeout(() => load(1), 350);
    });
    document.getElementById('txns-provider').addEventListener('change', function() { provider = this.value; load(1); });
    document.getElementById('txns-status').addEventListener('change', function() { status = this.value; load(1); });
    document.getElementById('txns-date-from').addEventListener('change', function() { dateFrom = this.value; load(1); });
    document.getElementById('txns-date-to').addEventListener('change', function() { dateTo = this.value; load(1); });

    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
