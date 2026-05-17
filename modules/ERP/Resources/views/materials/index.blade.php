@extends('layouts.admin-master')
@section('title', 'ERP — Matières Premières')
@section('page-title', 'Matières Premières')
@section('page-subtitle', 'Gérer vos matières premières et composants')

@once
@push('styles')
<style nonce="{{ csp_nonce() }}">
.mat-stock-ok  { color:#4ade80 }
.mat-stock-low { color:#fbbf24 }
.mat-stock-out { color:#f87171 }
</style>
@endpush
@endonce

@section('content')
@include('admin.components.admin-list', [
    'listId'      => 'materials',
    'bulkActions' => [
        ['label' => 'Supprimer', 'endpoint' => route('erp.materials.bulk-delete'), 'confirm' => 'Supprimer définitivement {n} matière(s) ? Action irréversible.', 'danger' => true],
    ],
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;"><i class="fas fa-cube me-2"></i>Matières Premières</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="#" id="mat-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('erp.materials.create') }}" class="al-action-btn" style="background:#ED5F1E;color:#fff;border-color:#ED5F1E;">+ Nouvelle Matière</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="mat-search" class="al-filter-input" placeholder="SKU, nom…" aria-label="Recherche" style="min-width:180px;">
    <select id="mat-supplier" class="al-filter-select" aria-label="Fournisseur">
      <option value="">Tous les fournisseurs</option>
      @foreach($suppliers as $sup)
      <option value="{{ $sup->id }}">{{ $sup->name }}</option>
      @endforeach
    </select>
    <select id="mat-critique" class="al-filter-select" aria-label="Stock critique">
      <option value="">Tous les stocks</option>
      <option value="1">Stock critique seulement</option>
    </select>
    <select id="mat-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="mat-reset" class="al-btn-reset">Réinitialiser<span class="al-filter-badge">0</span></button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat"><div class="al-stat-label">Total</div><div class="al-stat-value" id="mat-s-total">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Stock critique</div><div class="al-stat-value mat-stock-low" id="mat-s-critique">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Valeur totale</div><div class="al-stat-value" style="font-size:1.1rem" id="mat-s-valeur">—</div></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th style="width:36px;"><input type="checkbox" id="mat-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
        <th id="mat-th-sku"   data-col="sku">SKU</th>
        <th id="mat-th-name"  data-col="name">Nom</th>
        <th>Fournisseur</th>
        <th>Unité</th>
        <th id="mat-th-stock" data-col="current_stock">Stock</th>
        <th id="mat-th-price" data-col="unit_price">Prix Unit.</th>
        <th class="al-sticky">Actions</th>
      </tr></thead>
      <tbody id="mat-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="mat-pag"></div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const MATS = (function(){
  const DATA_URL   = '{{ route("erp.materials.data") }}';
  const EXPORT_URL = '{{ route("erp.materials.export.csv") }}';
  const hg = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
  const sortState = { by:'name', dir:'asc' };
  let state, bulk;
  const DEFAULTS = { search:'', supplier_id:'', stock_critique:'', page:1, per_page:20, sort_by:'name', sort_dir:'asc' };

  function activeFilters() {
    return [state.search, state.supplier_id, state.stock_critique].filter(Boolean).length;
  }
  function fmt(v){ try { return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; } catch(e){ return (v||0)+' FCFA'; } }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function stockBadge(current, min){
    const cls = current <= 0 ? 'mat-stock-out' : (current <= min ? 'mat-stock-low' : 'mat-stock-ok');
    const label = current <= 0 ? '0 — Rupture' : (current <= min ? current+' — Faible' : current+' — OK');
    return '<span class="'+cls+'" style="font-weight:700">'+esc(label)+'</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('mat-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="al-empty"><div class="al-empty-icon">🧱</div>Aucune matière première trouvée</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(m){
        return '<tr>'+
          '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+m.id+'" aria-label="Sélectionner '+esc(m.name)+'"></td>'+
          '<td><code style="color:#ED5F1E;font-size:.82rem">'+(m.sku?esc(m.sku):'—')+'</code></td>'+
          '<td><div style="font-weight:600;color:#e2e8f0">'+esc(m.name)+'</div></td>'+
          '<td style="color:#aaa;font-size:.82rem">'+(m.supplier?esc(m.supplier.name):'—')+'</td>'+
          '<td style="color:#aaa;font-size:.82rem">'+(m.unit?esc(m.unit):'—')+'</td>'+
          '<td>'+stockBadge(m.current_stock||0, m.min_stock_alert||0)+'</td>'+
          '<td style="color:#ED5F1E;font-weight:700">'+(m.unit_price?fmt(m.unit_price):'—')+'</td>'+
          '<td class="al-sticky" style="white-space:nowrap;">'+
            '<a href="/erp/matieres/'+m.id+'" class="al-action-btn" style="font-size:.75rem;">Voir</a> '+
            '<a href="/erp/matieres/'+m.id+'/edit" class="al-action-btn" style="font-size:.75rem;">Modifier</a>'+
          '</td>'+
          '</tr>';
      }).join('');
    }
    AL.buildPager('mat-pag', data, load);
    AL.syncUrl({ search:state.search, supplier_id:state.supplier_id, stock_critique:state.stock_critique, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('mat-reset', activeFilters());
    updateExportHref();
  }

  function updateExportHref(){
    const btn = document.getElementById('mat-export-btn'); if(!btn) return;
    const p = new URLSearchParams();
    if(state.search)      p.set('search', state.search);
    if(state.supplier_id) p.set('supplier_id', state.supplier_id);
    if(state.stock_critique) p.set('stock_critique', state.stock_critique);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page     = p || 1;
    state.sort_by  = sortState.by;
    state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('mat-tbody'), 8);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)       params.set('search',       state.search);
    if (state.supplier_id)  params.set('supplier_id',  state.supplier_id);
    if (state.stock_critique) params.set('stock_critique', state.stock_critique);
    fetch(DATA_URL+'?'+params, { credentials:'same-origin', headers:hg })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    Promise.all([
      fetch(DATA_URL+'?per_page=1', {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=1&stock_critique=1', {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=200', {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
    ]).then(function(results){
      document.getElementById('mat-s-total').textContent   = results[0].total||0;
      document.getElementById('mat-s-critique').textContent = results[1].total||0;
      let valeur = 0;
      (results[2].data||[]).forEach(function(m){ valeur += (m.current_stock||0)*(m.unit_price||0); });
      document.getElementById('mat-s-valeur').textContent = new Intl.NumberFormat('fr-FR').format(Math.round(valeur))+' FCFA';
    }).catch(function(){});
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by  = state.sort_by  || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    bulk = AL.initBulkBar({ listId:'materials', tbody:document.getElementById('mat-tbody'), cbAllId:'mat-cb-all', onSuccess:function(){ load(); loadStats(); } });

    ['mat-th-sku','mat-th-name','mat-th-stock','mat-th-price'].forEach(function(id){
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const searchEl   = document.getElementById('mat-search');
    const supplierEl = document.getElementById('mat-supplier');
    const critiqueEl = document.getElementById('mat-critique');
    const ppEl       = document.getElementById('mat-per-page');
    const resetEl    = document.getElementById('mat-reset');

    if(searchEl)  { searchEl.value=state.search;         let t; searchEl.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    if(supplierEl){ supplierEl.value=state.supplier_id;  supplierEl.addEventListener('change',function(){ state.supplier_id=this.value; load(1); }); }
    if(critiqueEl){ critiqueEl.value=state.stock_critique; critiqueEl.addEventListener('change',function(){ state.stock_critique=this.value; load(1); }); }
    if(ppEl)      { ppEl.value=state.per_page;           ppEl.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    if(resetEl)   { resetEl.addEventListener('click',function(){ MATS.reset(); }); }

    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    reset: function(){ state=Object.assign({},DEFAULTS); sortState.by='name'; sortState.dir='asc';
      document.getElementById('mat-search').value='';
      document.getElementById('mat-supplier').value='';
      document.getElementById('mat-critique').value='';
      document.getElementById('mat-per-page').value='20';
      load(1); loadStats();
    },
  };
})();
</script>
@endpush
