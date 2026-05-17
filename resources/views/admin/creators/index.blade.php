@extends('layouts.admin-master')

@section('title', 'Créateurs')
@section('page-title', 'Gestion des Créateurs')
@section('page-subtitle', 'Gérer les créateurs partenaires et leurs documents')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'creators',
    'bulkActions' => [
        ['label' => 'Vérifier',   'endpoint' => route('admin.creators.bulk-verify'),  'confirm' => 'Vérifier et activer {n} créateur(s) ?'],
        ['label' => 'Suspendre',  'endpoint' => route('admin.creators.bulk-suspend'), 'confirm' => 'Suspendre {n} créateur(s) ?', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Créateurs</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.creators.export.csv') }}" id="creators-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('admin.creators.reports.validation') }}" class="al-action-btn">Rapport validation</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="creators-search" class="al-filter-input" placeholder="Nom, marque, email…" aria-label="Recherche" style="min-width:220px;">
    <select id="creators-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="active">Actif</option>
      <option value="pending">En attente</option>
      <option value="suspended">Suspendu</option>
      <option value="rejected">Rejeté</option>
    </select>
    <select id="creators-verified" class="al-filter-select" aria-label="Vérification">
      <option value="">Vérification (tous)</option>
      <option value="1">Vérifiés</option>
      <option value="0">Non vérifiés</option>
    </select>
    <select id="creators-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="creators-reset" class="al-btn-reset" onclick="CREATORS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actifs</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
    <div class="al-stat-item"><span id="stat-suspended" style="color:#f87171;">—</span><small>Suspendus</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="creators-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
          <th id="th-name" data-col="brand_name">Créateur</th>
          <th>Email</th>
          <th id="th-status" data-col="status">Statut</th>
          <th>Vérifié</th>
          <th id="th-docs" data-col="documents_count">Documents</th>
          <th id="th-created" data-col="created_at">Inscription</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="creators-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="creators-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const CREATORS = (function(){
  const DATA_URL   = '{{ route("admin.creators.data") }}';
  const EXPORT_URL = '{{ route("admin.creators.export.csv") }}';
  const sortState  = { by:'created_at', dir:'desc' };
  let state, bulk;
  const DEFAULTS = { search:'', status:'', is_verified:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.status, state.is_verified].filter(function(v){ return v!==''; }).length;
  }

  function statusBadge(s) {
    const m = { active:['#4ade80','Actif'], pending:['#fbbf24','En attente'], suspended:['#f87171','Suspendu'], rejected:['#f87171','Rejeté'] };
    const [c, l] = m[s] || ['#aaa', s||'—'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function verifiedBadge(v) {
    return v
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">✓ Vérifié</span>'
      : '<span class="badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">Non vérifié</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('creators-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="al-empty"><div class="al-empty-icon">🎨</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(c) { return (
        '<tr>'+
        '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+c.id+'" aria-label="Sélectionner '+(c.brand_name||'').replace(/"/g,'')+'"></td>'+
        '<td><strong style="color:#eee;">'+(c.user?c.user.name:'—')+'</strong>'+(c.brand_name?'<br><small style="color:#888;">'+c.brand_name+'</small>':'')+'</td>'+
        '<td style="color:#aaa;font-size:.85rem;">'+(c.user?c.user.email:'—')+'</td>'+
        '<td>'+statusBadge(c.status)+'</td>'+
        '<td>'+verifiedBadge(c.is_verified)+'</td>'+
        '<td style="color:#ccc;text-align:center;">'+(c.documents_count??0)+'</td>'+
        '<td style="color:#aaa;font-size:.8rem;">'+(c.created_at?c.created_at.substring(0,10):'—')+'</td>'+
        '<td class="al-sticky" style="white-space:nowrap;">'+
          '<a href="/admin/creators/'+c.id+'" class="al-action-btn" style="font-size:.75rem;">Voir</a>'+
        '</td>'+
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('creators-pag', data, load);
    AL.syncUrl({ search:state.search, status:state.status, is_verified:state.is_verified, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('creators-reset', activeFilters());
    const btn = document.getElementById('creators-export-btn');
    if (btn) { const p=new URLSearchParams(); if(state.search) p.set('search',state.search); if(state.status) p.set('status',state.status); btn.href=EXPORT_URL+(p.toString()?'?'+p.toString():''); }
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    const tbody = document.getElementById('creators-tbody');
    AL.skeleton(tbody, 8);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search)     params.set('search',      state.search);
    if (state.status)     params.set('status',      state.status);
    if (state.is_verified !== '') params.set('is_verified', state.is_verified);
    fetch(DATA_URL+'?'+params, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [['stat-total',''],['stat-active','&status=active'],['stat-pending','&status=pending'],['stat-suspended','&status=suspended']].forEach(function(pair) {
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
    bulk = AL.initBulkBar({ listId:'creators', tbody:document.getElementById('creators-tbody'),
      cbAllId:'creators-cb-all', onSuccess:function(){ load(1); loadStats(); } });
    ['th-name','th-status','th-docs','th-created'].forEach(function(id){ const th=document.getElementById(id); if(!th) return; AL.makeSortable(th,th.dataset.col,sortState,function(){ load(1); }); });
    const s=document.getElementById('creators-search'); if(s){ s.value=state.search; let t; s.addEventListener('input',function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    const st=document.getElementById('creators-status'); if(st){ st.value=state.status; st.addEventListener('change',function(){ state.status=this.value; load(1); }); }
    const v=document.getElementById('creators-verified'); if(v){ v.value=state.is_verified; v.addEventListener('change',function(){ state.is_verified=this.value; load(1); }); }
    const pp=document.getElementById('creators-per-page'); if(pp){ pp.value=state.per_page; pp.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);
  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      ['creators-search','creators-status','creators-verified'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
