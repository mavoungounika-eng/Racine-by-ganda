@extends('layouts.admin-master')
@section('title', 'ERP — Fournisseurs')
@section('page-title', 'Fournisseurs')
@section('page-subtitle', 'Gérer vos fournisseurs et partenaires')

@section('content')
@include('admin.components.admin-list', [
    'listId'      => 'suppliers',
    'bulkActions' => [
        ['label' => 'Supprimer', 'endpoint' => route('erp.suppliers.bulk-delete'), 'confirm' => 'Supprimer définitivement {n} fournisseur(s) ? Action irréversible.', 'danger' => true],
    ],
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;"><i class="fas fa-truck me-2"></i>Fournisseurs</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="#" id="sup-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('erp.suppliers.create') }}" class="al-action-btn" style="background:#ED5F1E;color:#fff;border-color:#ED5F1E;">+ Nouveau Fournisseur</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="sup-search" class="al-filter-input" placeholder="Nom, email, téléphone…" aria-label="Recherche" style="min-width:200px;">
    <select id="sup-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous les statuts</option>
      <option value="1">Actifs</option>
      <option value="0">Inactifs</option>
    </select>
    <select id="sup-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="sup-reset" class="al-btn-reset">Réinitialiser<span class="al-filter-badge">0</span></button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat"><div class="al-stat-label">Total</div><div class="al-stat-value" id="sup-s-total">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Actifs</div><div class="al-stat-value" style="color:#4ade80" id="sup-s-actifs">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Inactifs</div><div class="al-stat-value" style="color:#94a3b8" id="sup-s-inactifs">—</div></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th style="width:36px;"><input type="checkbox" id="sup-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
        <th id="sup-th-name"  data-col="name">Nom</th>
        <th id="sup-th-email" data-col="email">Email</th>
        <th>Téléphone</th>
        <th id="sup-th-active" data-col="is_active">Statut</th>
        <th>Matières</th>
        <th class="al-sticky">Actions</th>
      </tr></thead>
      <tbody id="sup-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="sup-pag"></div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const SUPS = (function(){
  const DATA_URL   = '{{ route("erp.suppliers.data") }}';
  const EXPORT_URL = '{{ route("erp.suppliers.export.csv") }}';
  const hg = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
  const sortState = { by:'name', dir:'asc' };
  let state, bulk;
  const DEFAULTS = { search:'', is_active:'', page:1, per_page:20, sort_by:'name', sort_dir:'asc' };

  function activeFilters() {
    return [state.search, state.is_active].filter(function(v){ return v!==''; }).length;
  }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function statusBadge(active){
    return active
      ? '<span class="al-badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">Actif</span>'
      : '<span class="al-badge" style="background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25);">Inactif</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('sup-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="al-empty"><div class="al-empty-icon">🏭</div>Aucun fournisseur trouvé</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(s){
        return '<tr>'+
          '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+s.id+'" aria-label="Sélectionner '+esc(s.name)+'"></td>'+
          '<td><div style="font-weight:600;color:#e2e8f0">'+esc(s.name)+'</div></td>'+
          '<td style="color:#aaa;font-size:.82rem">'+(s.email?'<i class="fas fa-envelope me-1" style="opacity:.5"></i>'+esc(s.email):'—')+'</td>'+
          '<td style="color:#aaa;font-size:.82rem">'+(s.phone?esc(s.phone):'—')+'</td>'+
          '<td>'+statusBadge(s.is_active)+'</td>'+
          '<td style="color:#888;font-size:.82rem">'+(s.raw_materials_count||0)+' matière(s)</td>'+
          '<td class="al-sticky" style="white-space:nowrap;">'+
            '<a href="/erp/fournisseurs/'+s.id+'" class="al-action-btn" style="font-size:.75rem;">Voir</a> '+
            '<a href="/erp/fournisseurs/'+s.id+'/edit" class="al-action-btn" style="font-size:.75rem;">Modifier</a>'+
          '</td>'+
          '</tr>';
      }).join('');
    }
    AL.buildPager('sup-pag', data, load);
    AL.syncUrl({ search:state.search, is_active:state.is_active, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('sup-reset', activeFilters());
    updateExportHref();
  }

  function updateExportHref(){
    const btn = document.getElementById('sup-export-btn'); if(!btn) return;
    const p = new URLSearchParams();
    if(state.search)    p.set('search', state.search);
    if(state.is_active !== '') p.set('is_active', state.is_active);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page     = p || 1;
    state.sort_by  = sortState.by;
    state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('sup-tbody'), 7);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)    params.set('search', state.search);
    if (state.is_active !== '') params.set('is_active', state.is_active);
    fetch(DATA_URL+'?'+params, { credentials:'same-origin', headers:hg })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    Promise.all([
      fetch(DATA_URL+'?per_page=1',            {credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
      fetch(DATA_URL+'?per_page=1&is_active=1',{credentials:'same-origin',headers:hg}).then(function(r){ return r.json(); }),
    ]).then(function(results){
      const total  = results[0].total||0;
      const actifs = results[1].total||0;
      document.getElementById('sup-s-total').textContent   = total;
      document.getElementById('sup-s-actifs').textContent  = actifs;
      document.getElementById('sup-s-inactifs').textContent= Math.max(0, total-actifs);
    }).catch(function(){});
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by  = state.sort_by  || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    bulk = AL.initBulkBar({ listId:'suppliers', tbody:document.getElementById('sup-tbody'), cbAllId:'sup-cb-all', onSuccess:function(){ load(); loadStats(); } });

    ['sup-th-name','sup-th-email','sup-th-active'].forEach(function(id){
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const searchEl = document.getElementById('sup-search');
    const statusEl = document.getElementById('sup-status');
    const ppEl     = document.getElementById('sup-per-page');
    const resetEl  = document.getElementById('sup-reset');

    if(searchEl){ searchEl.value=state.search;     let t; searchEl.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    if(statusEl){ statusEl.value=state.is_active;  statusEl.addEventListener('change',function(){ state.is_active=this.value; load(1); }); }
    if(ppEl)    { ppEl.value=state.per_page;       ppEl.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    if(resetEl) { resetEl.addEventListener('click',function(){ SUPS.reset(); }); }

    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    reset: function(){ state=Object.assign({},DEFAULTS); sortState.by='name'; sortState.dir='asc';
      document.getElementById('sup-search').value='';
      document.getElementById('sup-status').value='';
      document.getElementById('sup-per-page').value='20';
      load(1); loadStats();
    },
  };
})();
</script>
@endpush
