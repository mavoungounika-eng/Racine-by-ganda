@extends('layouts.admin')
@section('title', 'Produits — Admin')
@section('page-title', 'Gestion des Produits')
@section('page-subtitle', 'Catalogue complet des produits de la plateforme')

@once
@push('styles')
<style nonce="{{ csp_nonce() }}">
.prod-img{width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid rgba(212,165,116,.15)}
.prod-img-ph{width:44px;height:44px;border-radius:8px;border:1px solid rgba(212,165,116,.1);background:rgba(22,13,12,.8);display:flex;align-items:center;justify-content:center;color:#555;font-size:.8rem;flex-shrink:0}
</style>
@endpush
@endonce

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'products',
    'bulkActions' => [
        ['label' => 'Désactiver', 'endpoint' => route('admin.products.bulk-disable'), 'confirm' => 'Désactiver {n} produit(s) ?', 'danger' => true],
        ['label' => 'Supprimer',  'endpoint' => route('admin.products.bulk-delete'),  'confirm' => 'Supprimer définitivement {n} produit(s) ? Action irréversible.', 'danger' => true],
    ],
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Produits</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.export.products') }}" id="products-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('admin.products.create') }}" class="al-action-btn" style="background:#ED5F1E;color:#fff;border-color:#ED5F1E;">+ Nouveau produit</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="f-search" class="al-filter-input" placeholder="Nom, SKU, code-barres…" aria-label="Recherche" style="min-width:200px;">
    <select id="f-category" class="al-filter-select" aria-label="Catégorie">
      <option value="">Toutes catégories</option>
      @foreach($categories as $cat)
      <option value="{{ $cat->id }}">{{ $cat->name }}</option>
      @endforeach
    </select>
    <select id="f-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="1">Actifs</option>
      <option value="0">Inactifs</option>
    </select>
    <select id="f-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="btn-reset" class="al-btn-reset" onclick="PROD.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat"><div class="al-stat-label">Total</div><div class="al-stat-value" id="s-total">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Actifs</div><div class="al-stat-value" style="color:#4ade80" id="s-actifs">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Inactifs</div><div class="al-stat-value" style="color:#94a3b8" id="s-inactifs">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Stock critique</div><div class="al-stat-value" style="color:#f87171" id="s-critique">—</div></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th style="width:36px;"><input type="checkbox" id="products-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
        <th style="width:52px;">Image</th>
        <th id="th-title" data-col="title">Nom</th>
        <th>SKU / Code-barres</th>
        <th>Catégorie</th>
        <th id="th-price" data-col="price">Prix</th>
        <th id="th-stock" data-col="stock">Stock</th>
        <th id="th-active" data-col="is_active">Statut</th>
        <th class="al-sticky">Actions</th>
      </tr></thead>
      <tbody id="products-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="prod-pag"></div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const PROD = (function(){
  const DATA_URL = '/admin/products/data';
  const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
  const hj   = { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest' };
  const hg   = { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' };
  const sortState = { by:'created_at', dir:'desc' };
  let state, bulk;
  const DEFAULTS = { search:'', category:'', status:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.category, state.status].filter(Boolean).length;
  }
  function fmt(v){ try { return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; } catch(e){ return (v||0)+' FCFA'; } }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function stockBadge(s){
    if(s>10)  return '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">'+s+'</span>';
    if(s>0)   return '<span class="badge" style="background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3);">'+s+'</span>';
    return '<span class="badge" style="background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);">0</span>';
  }
  function activeBadge(a){
    return a
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">Actif</span>'
      : '<span class="badge" style="background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25);">Inactif</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('products-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="al-empty"><div class="al-empty-icon">📦</div>Aucun produit trouvé</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(p) {
        const img = p.main_image
          ? '<img src="/storage/products/'+esc(p.main_image)+'" class="prod-img" loading="lazy" alt="'+esc(p.title)+'">'
          : '<div class="prod-img-ph">?</div>';
        const sku = p.erp_details&&p.erp_details.sku
          ? '<code style="color:#ED5F1E;font-size:.8rem">'+esc(p.erp_details.sku)+'</code>'
          : '<span style="color:#555">—</span>';
        const bc = p.erp_details&&p.erp_details.barcode
          ? '<div style="font-size:.75rem;color:#888;margin-top:.1rem"><code>'+esc(p.erp_details.barcode)+'</code></div>'
          : '';
        return (
          '<tr>'+
          '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+p.id+'" aria-label="Sélectionner '+esc(p.title)+'"></td>'+
          '<td>'+img+'</td>'+
          '<td>'+
            '<div style="font-weight:600;color:#e2e8f0">'+esc(p.title)+'</div>'+
            (p.description ? '<div style="font-size:.75rem;color:#888;margin-top:.1rem">'+esc(String(p.description).slice(0,50))+'…</div>' : '')+
          '</td>'+
          '<td>'+sku+bc+'</td>'+
          '<td style="color:#aaa;font-size:.82rem">'+(p.category?esc(p.category.name):'—')+'</td>'+
          '<td style="font-weight:600;color:#ED5F1E">'+fmt(p.price)+'</td>'+
          '<td>'+stockBadge(p.stock||0)+'</td>'+
          '<td>'+activeBadge(p.is_active)+'</td>'+
          '<td class="al-sticky" style="white-space:nowrap;">'+
            '<a href="/admin/products/'+p.id+'/edit" class="al-action-btn" style="font-size:.75rem;">Modifier</a> '+
            '<button class="al-action-btn" style="font-size:.75rem;color:#fbbf24;border-color:rgba(251,191,36,.3);" onclick="PROD.toggle('+p.id+','+p.is_active+')">'+(p.is_active?'Désactiver':'Activer')+'</button> '+
            '<button class="al-action-btn" style="font-size:.75rem;color:#f87171;border-color:rgba(239,68,68,.3);" onclick="PROD.del('+p.id+',\''+esc(p.title)+'\')">Supprimer</button>'+
          '</td>'+
          '</tr>'
        );
      }).join('');
    }
    AL.buildPager('prod-pag', data, load);
    AL.syncUrl({ search:state.search, category_id:state.category, is_active:state.status, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('btn-reset', activeFilters());
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('products-tbody'), 9);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)   params.set('search',      state.search);
    if (state.category) params.set('category_id', state.category);
    if (state.status !== '') params.set('is_active', state.status);
    fetch(DATA_URL+'?'+params, { credentials:'same-origin', headers:hg })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    Promise.all([
      fetch(DATA_URL+'?per_page=1',            {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=1&is_active=1', {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=200&is_active=1&sort_by=stock&sort_dir=asc', {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
    ]).then(function(results) {
      const total   = results[0].total||0;
      const actifs  = results[1].total||0;
      const critique= (results[2].data||[]).filter(function(p){ return (p.stock||0)<=0; }).length;
      document.getElementById('s-total').textContent    = total;
      document.getElementById('s-actifs').textContent   = actifs;
      document.getElementById('s-inactifs').textContent = Math.max(0, total - actifs);
      document.getElementById('s-critique').textContent = critique;
    }).catch(function(){});
  }

  async function toggleProduct(id, currentActive) {
    try {
      const r = await fetch('/admin/products/'+id, { method:'PATCH', credentials:'same-origin', headers:hj, body:JSON.stringify({is_active:!currentActive}) });
      if (r.ok) { AL.toast(currentActive?'Produit désactivé':'Produit activé'); load(); loadStats(); }
      else { const d = await r.json().catch(function(){ return {}; }); AL.toast(d.message||'Erreur', false, r.status); }
    } catch(e) { AL.toast('Erreur réseau', false); }
  }

  async function deleteProduct(id, title) {
    if (!confirm('Supprimer "'+title+'" ? Action irréversible.')) return;
    try {
      const r = await fetch('/admin/products/'+id, { method:'DELETE', credentials:'same-origin', headers:hj });
      if (r.ok) { AL.toast('Produit supprimé'); load(); loadStats(); }
      else { const d = await r.json().catch(function(){ return {}; }); AL.toast(d.message||'Erreur suppression', false, r.status); }
    } catch(e) { AL.toast('Erreur réseau', false); }
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by  = state.sort_by  || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    bulk = AL.initBulkBar({ listId:'products', tbody:document.getElementById('products-tbody'), cbAllId:'products-cb-all', onSuccess:function(){ load(); loadStats(); } });

    ['th-title','th-price','th-stock','th-active'].forEach(function(id) {
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const searchEl = document.getElementById('f-search');
    const catEl    = document.getElementById('f-category');
    const statusEl = document.getElementById('f-status');
    const ppEl     = document.getElementById('f-per-page');

    if(searchEl){ searchEl.value=state.search; let t; searchEl.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    if(catEl)   { catEl.value=state.category;    catEl.addEventListener('change',function(){ state.category=this.value; load(1); }); }
    if(statusEl){ statusEl.value=state.status;   statusEl.addEventListener('change',function(){ state.status=this.value; load(1); }); }
    if(ppEl)    { ppEl.value=state.per_page;     ppEl.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }

    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    page:   function(p){ load(p); },
    toggle: toggleProduct,
    del:    deleteProduct,
    reset:  function() {
      state = Object.assign({}, DEFAULTS);
      ['f-search','f-category','f-status'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
