@extends('layouts.admin-master')

@section('title', 'Catégories')
@section('page-title', 'Gestion des Catégories')
@section('page-subtitle', 'Organiser vos catégories de produits')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'cats',
    'bulkActions' => [
        ['label' => 'Activer',    'endpoint' => route('admin.categories.bulk-activate'),   'confirm' => 'Activer {n} catégorie(s) ?'],
        ['label' => 'Désactiver', 'endpoint' => route('admin.categories.bulk-deactivate'), 'confirm' => 'Désactiver {n} catégorie(s) ?', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Catégories</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.categories.export.csv') }}" id="cats-export-btn" class="al-action-btn">↓ Export CSV</a>
      <a href="{{ route('admin.categories.create') }}" class="al-action-btn">+ Nouvelle catégorie</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <input type="text" id="cats-search" class="al-filter-input" placeholder="Rechercher…" aria-label="Recherche" style="min-width:200px;">
    <select id="cats-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="1">Actives</option>
      <option value="0">Inactives</option>
    </select>
    <select id="cats-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="cats-reset" class="al-btn-reset" onclick="CATS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actives</small></div>
    <div class="al-stat-item"><span id="stat-inactive" style="color:#f87171;">—</span><small>Inactives</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100" id="cats-table">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="cats-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
          <th id="th-name" data-col="name">Nom</th>
          <th>Slug</th>
          <th>Parent</th>
          <th id="th-products" data-col="products_count">Produits</th>
          <th>Sous-cat.</th>
          <th id="th-active" data-col="is_active">Statut</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="cats-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="cats-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const CATS = (function(){
  const DATA_URL = '{{ route("admin.categories.data") }}';
  const EXPORT_URL = '{{ route("admin.categories.export.csv") }}';
  const sortState = { by: 'name', dir: 'asc' };
  let state, bulk;

  const DEFAULTS = { search:'', status:'', page:1, per_page:20, sort_by:'name', sort_dir:'asc' };

  function activeFilters() {
    let n = 0;
    if (state.search) n++;
    if (state.status !== '') n++;
    return n;
  }

  function badge(active) {
    return active
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">Active</span>'
      : '<span class="badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">Inactive</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('cats-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="al-empty"><div class="al-empty-icon">📂</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(c) { return (
        '<tr>' +
        '<td><input type="checkbox" class="al-row-cb al-cb" data-id="' + c.id + '" aria-label="Sélectionner ' + (c.name||'').replace(/"/g,'') + '"></td>' +
        '<td><strong style="color:#eee;">' + (c.name||'—') + '</strong></td>' +
        '<td><code style="color:#aaa;font-size:.8em;">' + (c.slug||'—') + '</code></td>' +
        '<td style="color:#ccc;">' + (c.parent ? c.parent.name : '—') + '</td>' +
        '<td style="color:#ccc;text-align:center;">' + (c.products_count ?? 0) + '</td>' +
        '<td style="color:#ccc;text-align:center;">' + (c.children_count ?? 0) + '</td>' +
        '<td>' + badge(c.is_active) + '</td>' +
        '<td class="al-sticky" style="white-space:nowrap;">' +
          '<a href="/admin/categories/' + c.id + '/edit" class="al-action-btn" style="font-size:.75rem;">Éditer</a>' +
        '</td>' +
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('cats-pag', data, load);
    AL.syncUrl({ search: state.search, status: state.status, page: state.page, per_page: state.per_page, sort_by: sortState.by, sort_dir: sortState.dir });
    AL.updateResetBtn('cats-reset', activeFilters());
    updateExportLink();
  }

  function updateExportLink() {
    const btn = document.getElementById('cats-export-btn');
    if (!btn) return;
    const p = new URLSearchParams();
    if (state.search) p.set('search', state.search);
    if (state.status !== '') p.set('is_active', state.status);
    btn.href = EXPORT_URL + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by;
    state.sort_dir = sortState.dir;
    const tbody = document.getElementById('cats-tbody');
    AL.skeleton(tbody, 8);
    const params = new URLSearchParams({ page: state.page, per_page: state.per_page, sort_by: state.sort_by, sort_dir: state.sort_dir });
    if (state.search) params.set('search', state.search);
    if (state.status !== '') params.set('is_active', state.status);
    fetch(DATA_URL + '?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.json(); })
      .then(renderTable)
      .catch(function() { AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [['stat-total',''],['stat-active','&is_active=1'],['stat-inactive','&is_active=0']].forEach(function(pair) {
      fetch(DATA_URL + '?per_page=1' + pair[1], { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) { const el = document.getElementById(pair[0]); if(el) el.textContent = d.total ?? '—'; })
        .catch(function(){});
    });
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    sortState.by = state.sort_by || DEFAULTS.sort_by;
    sortState.dir = state.sort_dir || DEFAULTS.sort_dir;

    bulk = AL.initBulkBar({ listId:'cats', tbody: document.getElementById('cats-tbody'),
      cbAllId:'cats-cb-all', onSuccess: function(){ load(1); loadStats(); } });

    // Sort headers
    ['th-name','th-products','th-active'].forEach(function(id) {
      const th = document.getElementById(id);
      if (!th) return;
      AL.makeSortable(th, th.dataset.col, sortState, function() { load(1); });
    });

    // Filters
    const searchEl = document.getElementById('cats-search');
    const statusEl = document.getElementById('cats-status');
    const perPageEl = document.getElementById('cats-per-page');

    if (searchEl) { searchEl.value = state.search; let t; searchEl.addEventListener('input', function() { state.search = this.value; clearTimeout(t); t = setTimeout(function() { load(1); }, 350); }); }
    if (statusEl) { statusEl.value = state.status; statusEl.addEventListener('change', function() { state.status = this.value; load(1); }); }
    if (perPageEl) { perPageEl.value = state.per_page; perPageEl.addEventListener('change', function() { state.per_page = parseInt(this.value, 10); load(1); }); }

    load(parseInt(state.page, 10) || 1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      const searchEl = document.getElementById('cats-search'); if(searchEl) searchEl.value='';
      const statusEl = document.getElementById('cats-status'); if(statusEl) statusEl.value='';
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
