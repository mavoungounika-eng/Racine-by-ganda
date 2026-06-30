@extends('layouts.admin-master')
@section('title', 'ERP — Gestion des Achats')
@section('page-title', 'Gestion des Achats')
@section('page-subtitle', 'Commandes fournisseurs et achats')

@section('content')
@include('admin.components.admin-list', [
    'listId'      => 'purchases',
    'bulkActions' => [
        ['label' => 'Supprimer brouillons', 'endpoint' => route('erp.purchases.bulk-delete'), 'confirm' => 'Supprimer {n} commande(s) en attente ? Action irréversible.', 'danger' => true],
    ],
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0 al-title"><i class="fas fa-shopping-cart me-2"></i>Commandes Fournisseurs</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="#" id="po-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('erp.purchases.create') }}" class="al-action-btn al-action-btn-primary">+ Nouvelle Commande</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="po-search" class="al-filter-input al-filter-input-wide" placeholder="Référence…" aria-label="Recherche">
    <select id="po-supplier" class="al-filter-select" aria-label="Fournisseur">
      <option value="">Tous les fournisseurs</option>
      @foreach($suppliers as $sup)
      <option value="{{ $sup->id }}">{{ $sup->name }}</option>
      @endforeach
    </select>
    <select id="po-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous les statuts</option>
      <option value="ordered">Commandé</option>
      <option value="received">Reçu</option>
      <option value="cancelled">Annulé</option>
    </select>
    <input type="date" id="po-date-debut" class="al-filter-input" aria-label="Date début">
    <input type="date" id="po-date-fin"   class="al-filter-input" aria-label="Date fin">
    <select id="po-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="po-reset" class="al-btn-reset">Réinitialiser<span class="al-filter-badge">0</span></button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat"><div class="al-stat-label">Total</div><div class="al-stat-value" id="po-s-total">—</div></div>
    <div class="al-stat"><div class="al-stat-label">En attente</div><div class="al-stat-value al-stat-warn" id="po-s-ordered">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Reçues</div><div class="al-stat-value al-stat-ok" id="po-s-received">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Montant total</div><div class="al-stat-value al-stat-orange" id="po-s-montant">—</div></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th class="al-th-cb"><input type="checkbox" id="po-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
        <th id="po-th-ref"     data-col="reference">Référence</th>
        <th>Fournisseur</th>
        <th id="po-th-date"    data-col="purchase_date">Date</th>
        <th id="po-th-amount"  data-col="total_amount">Montant</th>
        <th id="po-th-status"  data-col="status">Statut</th>
        <th class="al-sticky">Actions</th>
      </tr></thead>
      <tbody id="po-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="po-pag"></div>
  <div class="al-refresh-hint">
    <span id="po-refresh-indicator">Auto-refresh 60s</span>
  </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const POS_ERP = (function(){
  const DATA_URL   = '{{ route("erp.purchases.data") }}';
  const EXPORT_URL = '{{ route("erp.purchases.export.csv") }}';
  const hg = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
  const sortState = { by:'created_at', dir:'desc' };
  let state, bulk, refreshTimer;
  const DEFAULTS = { search:'', supplier_id:'', status:'', date_debut:'', date_fin:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.supplier_id, state.status, state.date_debut, state.date_fin].filter(Boolean).length;
  }
  function fmt(v){ try { return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; } catch(e){ return (v||0)+' FCFA'; } }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function statusBadge(st){
    const cfg = {
      ordered:   { cls:'al-badge-ordered',   label:'Commandé' },
      received:  { cls:'al-badge-received',  label:'Reçu' },
      cancelled: { cls:'al-badge-cancelled', label:'Annulé' },
    };
    const c = cfg[st] || cfg.ordered;
    return '<span class="al-badge '+c.cls+'">'+c.label+'</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('po-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="al-empty"><div class="al-empty-icon">🛒</div>Aucune commande trouvée</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(p){
        return '<tr>'+
          '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+p.id+'" aria-label="Sélectionner '+esc(p.reference)+'"></td>'+
          '<td><code class="al-code">'+esc(p.reference||'')+'</code></td>'+
          '<td class="al-row-muted">'+(p.supplier?esc(p.supplier.name):'—')+'</td>'+
          '<td class="al-row-muted">'+(p.purchase_date?p.purchase_date.split('T')[0]:'—')+'</td>'+
          '<td class="al-row-price">'+fmt(p.total_amount)+'</td>'+
          '<td>'+statusBadge(p.status)+'</td>'+
          '<td class="al-sticky">'+
            '<a href="/erp/achats/'+p.id+'" class="al-action-btn al-action-btn-sm">Voir</a> '+
            '<a href="/erp/achats/'+p.id+'/pdf" class="al-action-btn al-action-btn-sm">PDF</a>'+
          '</td>'+
          '</tr>';
      }).join('');
    }
    AL.buildPager('po-pag', data, load);
    AL.syncUrl({ search:state.search, supplier_id:state.supplier_id, status:state.status, date_debut:state.date_debut, date_fin:state.date_fin, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('po-reset', activeFilters());
    updateExportHref();
  }

  function updateExportHref(){
    const btn = document.getElementById('po-export-btn'); if(!btn) return;
    const p = new URLSearchParams();
    if(state.search)      p.set('search',      state.search);
    if(state.supplier_id) p.set('supplier_id', state.supplier_id);
    if(state.status)      p.set('status',      state.status);
    if(state.date_debut)  p.set('date_debut',  state.date_debut);
    if(state.date_fin)    p.set('date_fin',     state.date_fin);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page     = p || 1;
    state.sort_by  = sortState.by;
    state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('po-tbody'), 7);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)      params.set('search',      state.search);
    if (state.supplier_id) params.set('supplier_id', state.supplier_id);
    if (state.status)      params.set('status',      state.status);
    if (state.date_debut)  params.set('date_debut',  state.date_debut);
    if (state.date_fin)    params.set('date_fin',     state.date_fin);
    fetch(DATA_URL+'?'+params, { credentials:'same-origin', headers:hg })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    Promise.all([
      fetch(DATA_URL+'?per_page=1',                   {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=1&status=ordered',    {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=1&status=received',   {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=200',                 {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
    ]).then(function(results){
      document.getElementById('po-s-total').textContent    = results[0].total||0;
      document.getElementById('po-s-ordered').textContent  = results[1].total||0;
      document.getElementById('po-s-received').textContent = results[2].total||0;
      let montant = 0;
      (results[3].data||[]).forEach(function(p){ montant += p.total_amount||0; });
      document.getElementById('po-s-montant').textContent = new Intl.NumberFormat('fr-FR').format(Math.round(montant))+' FCFA';
      const ind = document.getElementById('po-refresh-indicator');
      if(ind) ind.textContent = 'Mis à jour '+new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
    }).catch(function(){});
  }

  function startAutoRefresh(){
    clearInterval(refreshTimer);
    refreshTimer = setInterval(function(){
      if (!document.hidden) { load(); loadStats(); }
    }, 60000);
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by  = state.sort_by  || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    bulk = AL.initBulkBar({ listId:'purchases', tbody:document.getElementById('po-tbody'), cbAllId:'po-cb-all', onSuccess:function(){ load(); loadStats(); } });

    ['po-th-ref','po-th-date','po-th-amount','po-th-status'].forEach(function(id){
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const searchEl   = document.getElementById('po-search');
    const supplierEl = document.getElementById('po-supplier');
    const statusEl   = document.getElementById('po-status');
    const dateDebEl  = document.getElementById('po-date-debut');
    const dateFinEl  = document.getElementById('po-date-fin');
    const ppEl       = document.getElementById('po-per-page');
    const resetEl    = document.getElementById('po-reset');

    if(searchEl)  { searchEl.value=state.search;         let t; searchEl.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    if(supplierEl){ supplierEl.value=state.supplier_id;  supplierEl.addEventListener('change',function(){ state.supplier_id=this.value; load(1); }); }
    if(statusEl)  { statusEl.value=state.status;         statusEl.addEventListener('change',function(){ state.status=this.value; load(1); }); }
    if(dateDebEl) { dateDebEl.value=state.date_debut;    dateDebEl.addEventListener('change',function(){ state.date_debut=this.value; load(1); }); }
    if(dateFinEl) { dateFinEl.value=state.date_fin;      dateFinEl.addEventListener('change',function(){ state.date_fin=this.value; load(1); }); }
    if(ppEl)      { ppEl.value=state.per_page;           ppEl.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    if(resetEl)   { resetEl.addEventListener('click',function(){ POS_ERP.reset(); }); }

    document.addEventListener('visibilitychange', function(){
      if (document.hidden) clearInterval(refreshTimer); else startAutoRefresh();
    });

    load(parseInt(state.page,10)||1);
    loadStats();
    startAutoRefresh();
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    reset: function(){ state=Object.assign({},DEFAULTS); sortState.by='created_at'; sortState.dir='desc';
      document.getElementById('po-search').value='';
      document.getElementById('po-supplier').value='';
      document.getElementById('po-status').value='';
      document.getElementById('po-date-debut').value='';
      document.getElementById('po-date-fin').value='';
      document.getElementById('po-per-page').value='20';
      load(1); loadStats();
    },
  };
})();
</script>
@endpush
