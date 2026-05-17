@extends('layouts.admin')

@section('title', 'Commandes')
@section('page-title', 'Gestion des Commandes')
@section('page-subtitle', 'Gérer toutes les commandes de la plateforme')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'orders',
    'bulkActions' => [
        ['label' => 'Marquer Complète', 'endpoint' => route('admin.orders.bulk-complete'), 'confirm' => 'Marquer {n} commande(s) comme complète(s) ?'],
        ['label' => 'Annuler',          'endpoint' => route('admin.orders.bulk-cancel'),   'confirm' => 'Annuler {n} commande(s) ?', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Commandes</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.orders.export.csv') }}" id="orders-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('admin.orders.scan') }}" class="al-action-btn">Scanner QR</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="orders-search" class="al-filter-input" placeholder="N° commande, client…" aria-label="Recherche" style="min-width:220px;">
    <select id="orders-status" class="al-filter-select" aria-label="Statut commande">
      <option value="">Tous statuts</option>
      <option value="pending">En attente</option>
      <option value="processing">En traitement</option>
      <option value="shipped">Expédiée</option>
      <option value="completed">Complète</option>
      <option value="cancelled">Annulée</option>
      <option value="refunded">Remboursée</option>
    </select>
    <select id="orders-payment-status" class="al-filter-select" aria-label="Statut paiement">
      <option value="">Paiement (tous)</option>
      <option value="paid">Payée</option>
      <option value="pending">En attente</option>
      <option value="failed">Échouée</option>
      <option value="refunded">Remboursée</option>
    </select>
    <input type="date" id="orders-date-debut" class="al-filter-input" aria-label="Date début">
    <input type="date" id="orders-date-fin"   class="al-filter-input" aria-label="Date fin">
    <select id="orders-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="orders-reset" class="al-btn-reset" onclick="ORDERS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
    <div class="al-stat-item"><span id="stat-completed" style="color:#4ade80;">—</span><small>Complètes</small></div>
    <div class="al-stat-item"><span id="stat-cancelled" style="color:#f87171;">—</span><small>Annulées</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="orders-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
          <th id="th-order-number" data-col="order_number">N° Commande</th>
          <th>Client</th>
          <th id="th-total" data-col="total_amount">Montant</th>
          <th id="th-status" data-col="status">Statut</th>
          <th>Paiement</th>
          <th id="th-created" data-col="created_at">Date</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="orders-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="orders-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const ORDERS = (function(){
  const DATA_URL   = '{{ route("admin.orders.data") }}';
  const EXPORT_URL = '{{ route("admin.orders.export.csv") }}';
  const sortState  = { by:'created_at', dir:'desc' };
  let state, bulk, statsTimer;
  const DEFAULTS = { search:'', status:'', payment_status:'', date_debut:'', date_fin:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.status, state.payment_status, state.date_debut, state.date_fin].filter(Boolean).length;
  }

  function statusBadge(s) {
    const m = { pending:['#fbbf24','En attente'], processing:['#60a5fa','Traitement'], shipped:['#c084fc','Expédiée'], completed:['#4ade80','Complète'], cancelled:['#f87171','Annulée'], refunded:['#fb923c','Remboursée'] };
    const [c, l] = m[s] || ['#aaa', s||'—'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function payBadge(s) {
    const m = { paid:['#4ade80','Payée'], pending:['#fbbf24','Attente'], failed:['#f87171','Échouée'], refunded:['#fb923c','Remboursée'] };
    const [c, l] = m[s] || ['#aaa', s||'—'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function fmt(amount, currency) {
    if (!amount && amount !== 0) return '—';
    try { return new Intl.NumberFormat('fr-FR',{style:'currency',currency:currency||'XAF',minimumFractionDigits:0}).format(amount); }
    catch(e) { return amount + ' ' + (currency||''); }
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('orders-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="al-empty"><div class="al-empty-icon">📦</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(o) { return (
        '<tr>'+
        '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+o.id+'" aria-label="Sélectionner commande #'+(o.order_number||o.id)+'"></td>'+
        '<td><strong style="color:#ED5F1E;">#'+(o.order_number||o.id)+'</strong></td>'+
        '<td style="color:#ccc;">'+(o.user?o.user.name:(o.customer_name||'—'))+'</td>'+
        '<td style="color:#eee;font-weight:600;">'+fmt(o.total_amount,o.currency)+'</td>'+
        '<td>'+statusBadge(o.status)+'</td>'+
        '<td>'+payBadge(o.payment_status)+'</td>'+
        '<td style="color:#aaa;font-size:.8rem;">'+(o.created_at?o.created_at.substring(0,10):'—')+'</td>'+
        '<td class="al-sticky" style="white-space:nowrap;">'+
          '<a href="/admin/orders/'+o.id+'" class="al-action-btn" style="font-size:.75rem;">Voir</a>'+
        '</td>'+
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('orders-pag', data, load);
    AL.syncUrl({ search:state.search, status:state.status, payment_status:state.payment_status, date_debut:state.date_debut, date_fin:state.date_fin, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('orders-reset', activeFilters());
    updateExportLink();
  }

  function updateExportLink() {
    const btn = document.getElementById('orders-export-btn');
    if (!btn) return;
    const p = new URLSearchParams();
    if (state.search)         p.set('search',         state.search);
    if (state.status)         p.set('status',         state.status);
    if (state.payment_status) p.set('payment_status', state.payment_status);
    if (state.date_debut)     p.set('date_debut',     state.date_debut);
    if (state.date_fin)       p.set('date_fin',       state.date_fin);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    const tbody = document.getElementById('orders-tbody');
    AL.skeleton(tbody, 8);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)         params.set('search',         state.search);
    if (state.status)         params.set('status',         state.status);
    if (state.payment_status) params.set('payment_status', state.payment_status);
    if (state.date_debut)     params.set('date_debut',     state.date_debut);
    if (state.date_fin)       params.set('date_fin',       state.date_fin);
    fetch(DATA_URL+'?'+params, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [
      ['stat-total',''],
      ['stat-pending','&status=pending'],
      ['stat-completed','&status=completed'],
      ['stat-cancelled','&status=cancelled']
    ].forEach(function(pair) {
      fetch(DATA_URL+'?per_page=1'+pair[1], { headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r){ return r.json(); })
        .then(function(d){ const el=document.getElementById(pair[0]); if(el) el.textContent=d.total??'—'; })
        .catch(function(){});
    });
  }

  function startAutoRefresh() {
    statsTimer = setInterval(function() { loadStats(); }, 60000);
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by = state.sort_by || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    bulk = AL.initBulkBar({ listId:'orders', tbody:document.getElementById('orders-tbody'),
      cbAllId:'orders-cb-all', onSuccess:function(){ load(1); loadStats(); } });

    ['th-order-number','th-total','th-status','th-created'].forEach(function(id) {
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const fields = [
      ['orders-search', 'search', true],
      ['orders-status', 'status', false],
      ['orders-payment-status', 'payment_status', false],
      ['orders-date-debut', 'date_debut', false],
      ['orders-date-fin', 'date_fin', false],
      ['orders-per-page', 'per_page', false, true]
    ];
    fields.forEach(function(f) {
      const el = document.getElementById(f[0]); if(!el) return;
      el.value = state[f[1]] || '';
      if (f[2]) {
        let t; el.addEventListener('input', function(){ state[f[1]]=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); });
      } else if (f[3]) {
        el.value = state[f[1]] || 20;
        el.addEventListener('change', function(){ state[f[1]]=parseInt(this.value,10); load(1); });
      } else {
        el.addEventListener('change', function(){ state[f[1]]=this.value; load(1); });
      }
    });

    load(parseInt(state.page,10)||1);
    loadStats();
    startAutoRefresh();

    document.addEventListener('visibilitychange', function() {
      if (document.hidden) { clearInterval(statsTimer); }
      else { loadStats(); startAutoRefresh(); }
    });
  }

  document.addEventListener('DOMContentLoaded', init);
  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      ['orders-search','orders-status','orders-payment-status','orders-date-debut','orders-date-fin'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
