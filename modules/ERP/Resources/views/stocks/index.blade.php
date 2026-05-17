@extends('layouts.admin-master')
@section('title', 'ERP — Gestion des Stocks')
@section('page-title', 'Gestion des Stocks')
@section('page-subtitle', 'Suivre et gérer les niveaux de stock')

@section('content')
@include('admin.components.admin-list', [
    'listId'      => 'stocks',
    'bulkActions' => [],
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;"><i class="fas fa-warehouse me-2"></i>Stocks Produits</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="#" id="stk-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('erp.stocks.movements') }}" class="al-action-btn">
        <i class="fas fa-history me-1"></i>Mouvements
      </a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="stk-search" class="al-filter-input" placeholder="Nom, SKU…" aria-label="Recherche" style="min-width:180px;">
    <select id="stk-status" class="al-filter-select" aria-label="Statut stock">
      <option value="">Tous les stocks</option>
      <option value="ok">OK (≥5)</option>
      <option value="low">Faible (1-4)</option>
      <option value="out">Rupture (0)</option>
    </select>
    <select id="stk-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="stk-reset" class="al-btn-reset">Réinitialiser<span class="al-filter-badge">0</span></button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat"><div class="al-stat-label">Total</div><div class="al-stat-value" id="stk-s-total">{{ $stats['total'] }}</div></div>
    <div class="al-stat"><div class="al-stat-label">OK</div><div class="al-stat-value" style="color:#4ade80" id="stk-s-ok">{{ $stats['ok'] }}</div></div>
    <div class="al-stat"><div class="al-stat-label">Faible</div><div class="al-stat-value" style="color:#fbbf24" id="stk-s-low">{{ $stats['low'] }}</div></div>
    <div class="al-stat"><div class="al-stat-label">Rupture</div><div class="al-stat-value" style="color:#f87171" id="stk-s-out">{{ $stats['out'] }}</div></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th id="stk-th-title" data-col="title">Produit</th>
        <th id="stk-th-price" data-col="price">Prix</th>
        <th id="stk-th-stock" data-col="stock">Stock</th>
        <th>Statut</th>
        <th class="al-sticky">Actions</th>
      </tr></thead>
      <tbody id="stk-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="stk-pag"></div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const STKS = (function(){
  const DATA_URL   = '{{ route("erp.stocks.data") }}';
  const EXPORT_URL = '{{ route("erp.stocks.export.csv") }}';
  const hg = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
  const sortState = { by:'stock', dir:'asc' };
  let state;
  const DEFAULTS = { search:'', status:'', page:1, per_page:20, sort_by:'stock', sort_dir:'asc' };

  function activeFilters() {
    return [state.search, state.status].filter(Boolean).length;
  }
  function fmt(v){ try { return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; } catch(e){ return (v||0)+' FCFA'; } }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function stockBadge(s){
    if(s<=0)  return '<span class="al-badge" style="background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);">'+s+' — Rupture</span>';
    if(s<5)   return '<span class="al-badge" style="background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3);">'+s+' — Faible</span>';
    return '<span class="al-badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">'+s+' — OK</span>';
  }

  function renderTable(data) {
    const tbody = document.getElementById('stk-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="al-empty"><div class="al-empty-icon">📦</div>Aucun produit trouvé</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(p){
        return '<tr>'+
          '<td>'+
            '<div style="font-weight:600;color:#e2e8f0">'+esc(p.title)+'</div>'+
            '<div style="font-size:.72rem;color:#555">#'+p.id+'</div>'+
          '</td>'+
          '<td style="color:#ED5F1E;font-weight:700">'+fmt(p.price)+'</td>'+
          '<td style="font-weight:700">'+stockBadge(p.stock||0)+'</td>'+
          '<td></td>'+
          '<td class="al-sticky" style="white-space:nowrap;">'+
            '<a href="/erp/stocks/'+p.id+'/adjust" class="al-action-btn" style="font-size:.75rem;">Ajuster</a> '+
            '<a href="/admin/products/'+p.id+'/edit" class="al-action-btn" style="font-size:.75rem;">Modifier</a>'+
          '</td>'+
          '</tr>';
      }).join('');
    }
    AL.buildPager('stk-pag', data, load);
    AL.syncUrl({ search:state.search, status:state.status, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('stk-reset', activeFilters());
    updateExportHref();
  }

  function updateExportHref(){
    const btn = document.getElementById('stk-export-btn'); if(!btn) return;
    const p = new URLSearchParams();
    if(state.search) p.set('search', state.search);
    if(state.status) p.set('status', state.status);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page     = p || 1;
    state.sort_by  = sortState.by;
    state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('stk-tbody'), 5);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search) params.set('search', state.search);
    if (state.status) params.set('status', state.status);
    fetch(DATA_URL+'?'+params, { credentials:'same-origin', headers:hg })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by  = state.sort_by  || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    ['stk-th-title','stk-th-price','stk-th-stock'].forEach(function(id){
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const searchEl = document.getElementById('stk-search');
    const statusEl = document.getElementById('stk-status');
    const ppEl     = document.getElementById('stk-per-page');
    const resetEl  = document.getElementById('stk-reset');

    if(searchEl){ searchEl.value=state.search;  let t; searchEl.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    if(statusEl){ statusEl.value=state.status;  statusEl.addEventListener('change',function(){ state.status=this.value; load(1); }); }
    if(ppEl)    { ppEl.value=state.per_page;    ppEl.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    if(resetEl) { resetEl.addEventListener('click',function(){ STKS.reset(); }); }

    load(parseInt(state.page,10)||1);
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    reset: function(){ state=Object.assign({},DEFAULTS); sortState.by='stock'; sortState.dir='asc';
      document.getElementById('stk-search').value='';
      document.getElementById('stk-status').value='';
      document.getElementById('stk-per-page').value='20';
      load(1);
    },
  };
})();
</script>
@endpush
