@extends('layouts.admin')

@section('title', 'Commandes')
@section('page-title', 'Gestion des Commandes')
@section('page-subtitle', 'Gérer toutes les commandes de la plateforme')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'orders',
    'bulkActions' => [
        ['label' => 'Marquer Complète', 'endpoint' => route('admin.orders.bulk-complete'), 'confirm' => 'Marquer {n} commande(s) comme complète(s) ?'],
        ['label' => 'Annuler', 'endpoint' => route('admin.orders.bulk-cancel'), 'confirm' => 'Annuler {n} commande(s) ?', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Commandes</h5>
    <a href="{{ route('admin.orders.scan') }}" class="al-action-btn">Scanner QR</a>
  </div>

  {{-- Filtres --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" id="orders-search" class="al-filter-input" placeholder="N° commande, client…" style="min-width:220px;">
    <select id="orders-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="pending">En attente</option>
      <option value="processing">En traitement</option>
      <option value="shipped">Expédiée</option>
      <option value="completed">Complète</option>
      <option value="cancelled">Annulée</option>
      <option value="refunded">Remboursée</option>
    </select>
    <select id="orders-payment-status" class="al-filter-select">
      <option value="">Paiement (tous)</option>
      <option value="paid">Payée</option>
      <option value="pending">En attente</option>
      <option value="failed">Échouée</option>
      <option value="refunded">Remboursée</option>
    </select>
    <input type="date" id="orders-date-debut" class="al-filter-input" title="Date début">
    <input type="date" id="orders-date-fin" class="al-filter-input" title="Date fin">
  </div>

  {{-- Stats --}}
  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
    <div class="al-stat-item"><span id="stat-completed" style="color:#4ade80;">—</span><small>Complètes</small></div>
    <div class="al-stat-item"><span id="stat-cancelled" style="color:#f87171;">—</span><small>Annulées</small></div>
  </div>

  {{-- Tableau --}}
  <div class="table-responsive">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="orders-cb-all" class="al-cb"></th>
          <th>N° Commande</th>
          <th>Client</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Paiement</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="orders-tbody"><tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  <div class="al-pag-bar mt-3" id="orders-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.orders.data") }}';
  let page = 1, search = '', status = '', paymentStatus = '', dateDebut = '', dateFin = '', bulk;

  function statusBadge(s) {
    const map = {
      pending: ['#fbbf24','En attente'],
      processing: ['#60a5fa','Traitement'],
      shipped: ['#c084fc','Expédiée'],
      completed: ['#4ade80','Complète'],
      cancelled: ['#f87171','Annulée'],
      refunded: ['#fb923c','Remboursée'],
    };
    const [color, label] = map[s] || ['#aaa', s || '—'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function payBadge(s) {
    const map = { paid: ['#4ade80','Payée'], pending: ['#fbbf24','Attente'], failed: ['#f87171','Échouée'], refunded: ['#fb923c','Remboursée'] };
    const [color, label] = map[s] || ['#aaa', s || '—'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function fmt(amount, currency) {
    if (!amount) return '—';
    try { return new Intl.NumberFormat('fr-FR', { style:'currency', currency: currency||'XAF', minimumFractionDigits:0 }).format(amount); }
    catch(e) { return amount + ' ' + (currency||''); }
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('orders-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(o => `
        <tr>
          <td><input type="checkbox" class="al-row-cb al-cb" data-id="${o.id}"></td>
          <td><strong style="color:#ED5F1E;">#${o.order_number || o.id}</strong></td>
          <td style="color:#ccc;">${o.user ? o.user.name : (o.customer_name || '—')}</td>
          <td style="color:#eee;">${fmt(o.total_amount, o.currency)}</td>
          <td>${statusBadge(o.status)}</td>
          <td>${payBadge(o.payment_status)}</td>
          <td style="color:#aaa;font-size:.8rem;">${o.created_at ? o.created_at.substring(0,10) : '—'}</td>
          <td>
            <a href="/admin/orders/${o.id}" class="al-action-btn" style="font-size:.75rem;">Voir</a>
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('orders-pag');
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
    if (status) params.set('status', status);
    if (paymentStatus) params.set('payment_status', paymentStatus);
    if (dateDebut) params.set('date_debut', dateDebut);
    if (dateFin) params.set('date_fin', dateFin);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(() => AL.toast('Erreur chargement', false));
  }

  function loadStats() {
    fetch(`${DATA_URL}?per_page=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-total').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=pending`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-pending').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=completed`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-completed').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=cancelled`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-cancelled').textContent = d.total ?? '—'; });
  }

  document.addEventListener('DOMContentLoaded', function() {
    bulk = AL.initBulkBar({ listId:'orders', tbody: document.getElementById('orders-tbody'),
      cbAllId:'orders-cb-all', onSuccess: function(){ load(1); loadStats(); } });

    let t;
    document.getElementById('orders-search').addEventListener('input', function() {
      search = this.value; clearTimeout(t); t = setTimeout(() => load(1), 350);
    });
    document.getElementById('orders-status').addEventListener('change', function() { status = this.value; load(1); });
    document.getElementById('orders-payment-status').addEventListener('change', function() { paymentStatus = this.value; load(1); });
    document.getElementById('orders-date-debut').addEventListener('change', function() { dateDebut = this.value; load(1); });
    document.getElementById('orders-date-fin').addEventListener('change', function() { dateFin = this.value; load(1); });

    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
