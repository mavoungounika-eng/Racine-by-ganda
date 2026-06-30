@extends('layouts.admin-master')

@section('title', 'Transactions - Payments Hub')
@section('page-title', 'Transactions')
@section('page-subtitle', 'Liste et gestion des transactions de paiement')

@section('content')
@include('admin.components.admin-list', ['listId' => 'txns', 'bulkActions' => []])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Transactions</h5>
    <a href="{{ route('admin.payments.transactions.export.csv') }}" id="txns-export-btn" class="al-action-btn">↓ Export CSV</a>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="txns-search" class="al-filter-input" placeholder="Réf, transaction ID, téléphone…" aria-label="Recherche" style="min-width:240px;">
    <select id="txns-provider" class="al-filter-select" aria-label="Provider">
      <option value="">Tous providers</option>
      <option value="stripe">Stripe</option>
      <option value="monetbil">Monetbil</option>
    </select>
    <select id="txns-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="succeeded">Réussie</option>
      <option value="pending">En attente</option>
      <option value="processing">En cours</option>
      <option value="failed">Échouée</option>
      <option value="canceled">Annulée</option>
      <option value="refunded">Remboursée</option>
    </select>
    <input type="date" id="txns-date-from" class="al-filter-input" aria-label="Date début">
    <input type="date" id="txns-date-to"   class="al-filter-input" aria-label="Date fin">
    <select id="txns-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="txns-reset" class="al-btn-reset" onclick="TXNS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-succeeded" style="color:#4ade80;">—</span><small>Réussies</small></div>
    <div class="al-stat-item"><span id="stat-failed" style="color:#f87171;">—</span><small>Échouées</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th id="th-ref" data-col="payment_ref">Référence</th>
          <th id="th-provider" data-col="provider">Provider</th>
          <th id="th-amount" data-col="amount">Montant</th>
          <th id="th-status" data-col="status">Statut</th>
          <th>Commande</th>
          <th>Téléphone</th>
          <th id="th-created" data-col="created_at">Date</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="txns-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="txns-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const TXNS = (function(){
  const DATA_URL   = '{{ route("admin.payments.transactions.data") }}';
  const EXPORT_URL = '{{ route("admin.payments.transactions.export.csv") }}';
  const sortState  = { by:'created_at', dir:'desc' };
  let state;
  const DEFAULTS = { search:'', provider:'', status:'', date_from:'', date_to:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.provider, state.status, state.date_from, state.date_to].filter(Boolean).length;
  }

  function statusBadge(s) {
    const m = { succeeded:['#4ade80','Réussie'], pending:['#fbbf24','Attente'], processing:['#60a5fa','En cours'], failed:['#f87171','Échouée'], canceled:['#f87171','Annulée'], refunded:['#fb923c','Remboursée'] };
    const [c, l] = m[s] || ['#aaa', s||'—'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function providerBadge(p) {
    if (p==='stripe')   return '<span class="badge" style="background:rgba(96,165,250,.15);color:#60a5fa;border:1px solid rgba(96,165,250,.3);">Stripe</span>';
    if (p==='monetbil') return '<span class="badge" style="background:rgba(255,184,0,.15);color:#FFB800;border:1px solid rgba(255,184,0,.3);">Monetbil</span>';
    return '<span class="badge" style="color:#aaa;">'+( p||'—')+'</span>';
  }
  function fmt(amount, currency) {
    if (!amount && amount!==0) return '—';
    try { return new Intl.NumberFormat('fr-FR',{style:'currency',currency:currency||'XAF',minimumFractionDigits:0}).format(amount); }
    catch(e) { return amount+' '+(currency||''); }
  }

  function renderTable(data) {
    const tbody = document.getElementById('txns-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="al-empty"><div class="al-empty-icon">💳</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(t) { return (
        '<tr>'+
        '<td><code style="color:#ED5F1E;font-size:.75em;">'+(t.payment_ref||t.transaction_id||'—')+'</code></td>'+
        '<td>'+providerBadge(t.provider)+'</td>'+
        '<td style="color:#eee;font-weight:600;">'+fmt(t.amount,t.currency)+'</td>'+
        '<td>'+statusBadge(t.status)+'</td>'+
        '<td style="color:#aaa;font-size:.85rem;">'+(t.order?'#'+t.order.order_number:(t.order_id?'#'+t.order_id:'—'))+'</td>'+
        '<td style="color:#aaa;font-size:.85rem;">'+(t.phone||'—')+'</td>'+
        '<td style="color:#aaa;font-size:.8rem;">'+(t.created_at?t.created_at.substring(0,16):'—')+'</td>'+
        '<td class="al-sticky" style="white-space:nowrap;">'+
          '<a href="/admin/payments/transactions/'+t.id+'" class="al-action-btn" style="font-size:.75rem;">Voir</a>'+
        '</td>'+
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('txns-pag', data, load);
    AL.syncUrl({ search:state.search, provider:state.provider, status:state.status, date_from:state.date_from, date_to:state.date_to, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('txns-reset', activeFilters());
    const btn=document.getElementById('txns-export-btn'); if(btn){ const p=new URLSearchParams(); if(state.search) p.set('search',state.search); if(state.provider) p.set('provider',state.provider); if(state.status) p.set('status',state.status); if(state.date_from) p.set('date_from',state.date_from); if(state.date_to) p.set('date_to',state.date_to); btn.href=EXPORT_URL+(p.toString()?'?'+p.toString():''); }
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('txns-tbody'), 8);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)    params.set('search',    state.search);
    if (state.provider)  params.set('provider',  state.provider);
    if (state.status)    params.set('status',    state.status);
    if (state.date_from) params.set('date_from', state.date_from);
    if (state.date_to)   params.set('date_to',   state.date_to);
    fetch(DATA_URL+'?'+params, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [['stat-total',''],['stat-succeeded','&status=succeeded'],['stat-failed','&status=failed'],['stat-pending','&status=pending']].forEach(function(pair) {
      fetch(DATA_URL+'?per_page=1'+pair[1], { headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r){ return r.json(); })
        .then(function(d){ const el=document.getElementById(pair[0]); if(el) el.textContent=d.total??'—'; })
        .catch(function(){});
    });
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by = state.sort_by || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;
    ['th-ref','th-provider','th-amount','th-status','th-created'].forEach(function(id){ const th=document.getElementById(id); if(!th) return; AL.makeSortable(th,th.dataset.col,sortState,function(){ load(1); }); });
    const fields = [
      ['txns-search','search',true],['txns-provider','provider',false],['txns-status','status',false],
      ['txns-date-from','date_from',false],['txns-date-to','date_to',false]
    ];
    fields.forEach(function(f){
      const el=document.getElementById(f[0]); if(!el) return;
      el.value=state[f[1]]||'';
      if(f[2]){ let t; el.addEventListener('input',function(){ state[f[1]]=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
      else { el.addEventListener('change',function(){ state[f[1]]=this.value; load(1); }); }
    });
    const pp=document.getElementById('txns-per-page'); if(pp){ pp.value=state.per_page; pp.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);
  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      ['txns-search','txns-provider','txns-status','txns-date-from','txns-date-to'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
