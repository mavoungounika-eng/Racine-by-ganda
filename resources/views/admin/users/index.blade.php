@extends('layouts.admin')

@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des Utilisateurs')
@section('page-subtitle', 'Gérer les comptes utilisateurs de la plateforme')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'users',
    'bulkActions' => [
        ['label' => 'Désactiver', 'endpoint' => route('admin.users.bulk-disable'), 'confirm' => 'Désactiver {n} utilisateur(s) ?', 'danger' => true],
        ['label' => 'Supprimer',  'endpoint' => route('admin.users.bulk-delete'),  'confirm' => 'Supprimer définitivement {n} utilisateur(s) ? Action irréversible.', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Utilisateurs</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.users.export.csv') }}" id="users-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('admin.users.create') }}" class="al-action-btn">+ Nouvel utilisateur</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="users-search" class="al-filter-input" placeholder="Nom ou email…" aria-label="Recherche" style="min-width:220px;">
    <select id="users-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="active">Actif</option>
      <option value="inactive">Inactif</option>
      <option value="suspended">Suspendu</option>
    </select>
    <select id="users-role" class="al-filter-select" aria-label="Rôle">
      <option value="">Tous rôles</option>
      <option value="admin">Admin</option>
      <option value="createur">Créateur</option>
      <option value="client">Client</option>
    </select>
    <select id="users-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="users-reset" class="al-btn-reset" onclick="USERS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actifs</small></div>
    <div class="al-stat-item"><span id="stat-inactive" style="color:#f87171;">—</span><small>Inactifs</small></div>
    <div class="al-stat-item"><span id="stat-admin" style="color:#ED5F1E;">—</span><small>Admins</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="users-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
          <th id="th-name" data-col="name">Nom</th>
          <th id="th-email" data-col="email">Email</th>
          <th>Rôle</th>
          <th id="th-status" data-col="status">Statut</th>
          <th id="th-created" data-col="created_at">Inscription</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="users-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="users-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const USERS = (function(){
  const DATA_URL  = '{{ route("admin.users.data") }}';
  const EXPORT_URL = '{{ route("admin.users.export.csv") }}';
  const sortState = { by:'created_at', dir:'desc' };
  let state, bulk;
  const DEFAULTS = { search:'', status:'', role:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function activeFilters() {
    return [state.search, state.status, state.role].filter(Boolean).length;
  }

  function statusBadge(s) {
    const m = { active:['#4ade80','Actif'], inactive:['#f87171','Inactif'], suspended:['#fbbf24','Suspendu'] };
    const [c, l] = m[s] || ['#aaa', s||'—'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function roleBadge(r) {
    const m = { admin:'#ED5F1E', createur:'#FFB800', client:'#60a5fa' };
    const c = m[r]||'#aaa';
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+(r||'—')+'</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('users-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="al-empty"><div class="al-empty-icon">👤</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(u) { return (
        '<tr>' +
        '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+u.id+'" aria-label="Sélectionner '+((u.name||'').replace(/"/g,''))+'"></td>'+
        '<td><strong style="color:#eee;">'+(u.name||'—')+'</strong></td>'+
        '<td style="color:#aaa;font-size:.85rem;">'+(u.email||'—')+'</td>'+
        '<td>'+roleBadge(u.role)+'</td>'+
        '<td>'+statusBadge(u.status)+'</td>'+
        '<td style="color:#aaa;font-size:.8rem;">'+(u.created_at?u.created_at.substring(0,10):'—')+'</td>'+
        '<td class="al-sticky" style="white-space:nowrap;">'+
          '<a href="/admin/users/'+u.id+'" class="al-action-btn" style="font-size:.75rem;">Voir</a> '+
          '<a href="/admin/users/'+u.id+'/edit" class="al-action-btn" style="font-size:.75rem;">Éditer</a>'+
        '</td>'+
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('users-pag', data, load);
    AL.syncUrl({ search:state.search, status:state.status, role:state.role, page:state.page, per_page:state.per_page, sort_by:sortState.by, sort_dir:sortState.dir });
    AL.updateResetBtn('users-reset', activeFilters());
    updateExportLink();
  }

  function updateExportLink() {
    const btn = document.getElementById('users-export-btn');
    if (!btn) return;
    const p = new URLSearchParams();
    if (state.search) p.set('search', state.search);
    if (state.status) p.set('status', state.status);
    if (state.role)   p.set('role',   state.role);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    const tbody = document.getElementById('users-tbody');
    AL.skeleton(tbody, 7);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page, sort_by:state.sort_by, sort_dir:state.sort_dir });
    if (state.search) params.set('search', state.search);
    if (state.status) params.set('status', state.status);
    if (state.role)   params.set('role',   state.role);
    fetch(DATA_URL + '?' + params, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [
      ['stat-total',''],
      ['stat-active','&status=active'],
      ['stat-inactive','&status=inactive'],
      ['stat-admin','&role=admin']
    ].forEach(function(pair) {
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

    bulk = AL.initBulkBar({ listId:'users', tbody:document.getElementById('users-tbody'),
      cbAllId:'users-cb-all', onSuccess:function(){ load(1); loadStats(); } });

    ['th-name','th-email','th-status','th-created'].forEach(function(id) {
      const th = document.getElementById(id); if(!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function(){ load(1); });
    });

    const searchEl = document.getElementById('users-search');
    const statusEl = document.getElementById('users-status');
    const roleEl   = document.getElementById('users-role');
    const ppEl     = document.getElementById('users-per-page');

    if(searchEl){ searchEl.value=state.search; let t; searchEl.addEventListener('input', function(){ state.search=this.value; clearTimeout(t); t=setTimeout(function(){ load(1); },350); }); }
    if(statusEl){ statusEl.value=state.status; statusEl.addEventListener('change', function(){ state.status=this.value; load(1); }); }
    if(roleEl)  { roleEl.value=state.role;     roleEl.addEventListener('change',   function(){ state.role=this.value;   load(1); }); }
    if(ppEl)    { ppEl.value=state.per_page;   ppEl.addEventListener('change',     function(){ state.per_page=parseInt(this.value,10); load(1); }); }

    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);
  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      ['users-search','users-status','users-role'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
