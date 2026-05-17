@extends('layouts.admin-master')

@section('title', 'Codes Promo')
@section('page-title', 'Codes Promo')
@section('page-subtitle', 'Gérez les codes de réduction appliqués au checkout')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'promos',
    'bulkActions' => [
        ['label' => 'Désactiver', 'endpoint' => route('admin.promo-codes.bulk-disable'), 'confirm' => 'Désactiver {n} code(s) ?', 'danger' => true],
        ['label' => 'Supprimer',  'endpoint' => route('admin.promo-codes.bulk-delete'),  'confirm' => 'Supprimer {n} code(s) inutilisés ? Les codes déjà utilisés seront ignorés.', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Codes Promo</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.promo-codes.export.csv') }}" id="promos-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('admin.promo-codes.create') }}" class="al-action-btn">+ Nouveau code</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="promos-search" class="al-filter-input" placeholder="Code ou nom…" aria-label="Recherche" style="min-width:200px;">
    <select id="promos-type" class="al-filter-select" aria-label="Type">
      <option value="">Tous types</option>
      <option value="percentage">Pourcentage</option>
      <option value="fixed">Fixe</option>
      <option value="free_shipping">Livraison gratuite</option>
    </select>
    <select id="promos-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="1">Actifs</option>
      <option value="0">Inactifs</option>
    </select>
    <select id="promos-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="promos-reset" class="al-btn-reset" onclick="PROMOS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actifs</small></div>
    <div class="al-stat-item"><span id="stat-inactive" style="color:#f87171;">—</span><small>Inactifs</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="promos-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
          <th id="th-code" data-col="code">Code</th>
          <th>Nom</th>
          <th id="th-type" data-col="type">Type</th>
          <th>Valeur</th>
          <th id="th-used" data-col="used_count">Utilisations</th>
          <th>Limite</th>
          <th id="th-expires" data-col="expires_at">Expire</th>
          <th id="th-active" data-col="is_active">Statut</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="promos-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="promos-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const PROMOS = (function(){
  const DATA_URL   = '{{ route("admin.promo-codes.data") }}';
  const EXPORT_URL = '{{ route("admin.promo-codes.export.csv") }}';
  const sortState  = { by:'created_at', dir:'desc' };
  let state, bulk;
  const DEFAULTS = { search:'', type:'', is_active:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.type, state.is_active].filter(function(v){ return v!==''; }).length;
  }

  function typeBadge(t) {
    const m = { percentage:['#FFB800','%'], fixed:['#60a5fa','Fixe'], free_shipping:['#c084fc','Livraison'] };
    const [c, l] = m[t] || ['#aaa', t||'—'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function activeBadge(v) {
    return v
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">Actif</span>'
      : '<span class="badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">Inactif</span>';
  }
  function fmtVal(c) {
    if (c.type==='percentage') return (c.discount_value||0)+' %';
    if (c.type==='fixed') return (c.discount_value||0)+' '+(c.currency||'XAF');
    return '—';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('promos-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="10" class="al-empty"><div class="al-empty-icon">🎟</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(c) { return (
        '<tr>'+
        '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+c.id+'" aria-label="Sélectionner '+c.code+'"></td>'+
        '<td><code style="color:#ED5F1E;font-size:.85em;">'+c.code+'</code></td>'+
        '<td style="color:#ccc;">'+(c.name||'—')+'</td>'+
        '<td>'+typeBadge(c.type)+'</td>'+
        '<td style="color:#eee;">'+fmtVal(c)+'</td>'+
        '<td style="color:#ccc;text-align:center;">'+(c.used_count??0)+'</td>'+
        '<td style="color:#ccc;text-align:center;">'+(c.max_uses?c.max_uses:'∞')+'</td>'+
        '<td style="color:#aaa;font-size:.8rem;">'+(c.expires_at?c.expires_at.substring(0,10):'—')+'</td>'+
        '<td>'+activeBadge(c.is_active)+'</td>'+
        '<td class="al-sticky" style="white-space:nowrap;">'+
          '<a href="/admin/promo-codes/'+c.id+'/edit" class="al-action-btn" style="font-size:.75rem;">Éditer</a>'+
        '</td>'+
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('promos-pag', data, load);
    AL.syncUrl({ search:state.search, type:state.type, is_active:state.is_active, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('promos-reset', activeFilters());
    const btn=document.getElementById('promos-export-btn'); if(btn){ const p=new URLSearchParams(); if(state.search) p.set('search',state.search); if(state.type) p.set('type',state.type); if(state.is_active!=='') p.set('is_active',state.is_active); btn.href=EXPORT_URL+(p.toString()?'?'+p.toString():''); }
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('promos-tbody'), 10);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)     params.set('search',    state.search);
    if (state.type)       params.set('type',      state.type);
    if (state.is_active !== '') params.set('is_active', state.is_active);
    fetch(DATA_URL+'?'+params, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [['stat-total',''],['stat-active','&is_active=1'],['stat-inactive','&is_active=0']].forEach(function(pair) {
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
    bulk = AL.initBulkBar({ listId:'promos', tbody:document.getElementById('promos-tbody'),
      cbAllId:'promos-cb-all', onSuccess:function(){ load(1); loadStats(); } });
    ['th-code','th-type','th-used','th-expires','th-active'].forEach(function(id){ const th=document.getElementById(id); if(!th) return; AL.makeSortable(th,th.dataset.col,sortState,function(){ load(1); }); });
    const s=document.getElementById('promos-search'); if(s){ s.value=state.search; let t; s.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    const ty=document.getElementById('promos-type'); if(ty){ ty.value=state.type; ty.addEventListener('change',function(){ state.type=this.value; load(1); }); }
    const st=document.getElementById('promos-status'); if(st){ st.value=state.is_active; st.addEventListener('change',function(){ state.is_active=this.value; load(1); }); }
    const pp=document.getElementById('promos-per-page'); if(pp){ pp.value=state.per_page; pp.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);
  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      ['promos-search','promos-type','promos-status'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
