@extends('layouts.admin-master')

@section('title', 'Catégories')
@section('page-title', 'Gestion des Catégories')
@section('page-subtitle', 'Organiser vos catégories de produits')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'cats',
    'bulkActions' => [
        ['label' => 'Activer', 'endpoint' => route('admin.categories.bulk-activate'), 'confirm' => 'Activer {n} catégorie(s) ?'],
        ['label' => 'Désactiver', 'endpoint' => route('admin.categories.bulk-deactivate'), 'confirm' => 'Désactiver {n} catégorie(s) ?', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Catégories</h5>
    <a href="{{ route('admin.categories.create') }}" class="al-action-btn">+ Nouvelle catégorie</a>
  </div>

  {{-- Filtres --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" id="cats-search" class="al-filter-input" placeholder="Rechercher..." style="min-width:200px;">
    <select id="cats-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="1">Actives</option>
      <option value="0">Inactives</option>
    </select>
  </div>

  {{-- Stats --}}
  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actives</small></div>
    <div class="al-stat-item"><span id="stat-inactive" style="color:#f87171;">—</span><small>Inactives</small></div>
  </div>

  {{-- Tableau --}}
  <div class="table-responsive">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="cats-cb-all" class="al-cb"></th>
          <th>Nom</th>
          <th>Slug</th>
          <th>Parent</th>
          <th>Produits</th>
          <th>Sous-cat.</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="cats-tbody"><tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div class="al-pag-bar mt-3" id="cats-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.categories.data") }}';
  let page = 1, search = '', status = '', bulk;

  function badge(active) {
    return active
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">Active</span>'
      : '<span class="badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">Inactive</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('cats-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(c => `
        <tr>
          <td><input type="checkbox" class="al-row-cb al-cb" data-id="${c.id}"></td>
          <td><strong style="color:#eee;">${c.name}</strong></td>
          <td><code style="color:#aaa;font-size:.8em;">${c.slug}</code></td>
          <td style="color:#ccc;">${c.parent ? c.parent.name : '—'}</td>
          <td style="color:#ccc;">${c.products_count ?? 0}</td>
          <td style="color:#ccc;">${c.children_count ?? 0}</td>
          <td>${badge(c.is_active)}</td>
          <td>
            <a href="/admin/categories/${c.id}/edit" class="al-action-btn" style="font-size:.75rem;">Éditer</a>
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('cats-pag');
    pag.innerHTML = '';
    if (data.last_page <= 1) return;
    const prev = document.createElement('button');
    prev.textContent = '← Préc.'; prev.className = 'al-action-btn'; prev.disabled = data.current_page <= 1;
    prev.onclick = () => load(data.current_page - 1);
    const next = document.createElement('button');
    next.textContent = 'Suiv. →'; next.className = 'al-action-btn'; next.disabled = data.current_page >= data.last_page;
    next.onclick = () => load(data.current_page + 1);
    const info = document.createElement('span');
    info.textContent = `Page ${data.current_page} / ${data.last_page} (${data.total} entrées)`;
    info.style.cssText = 'color:#aaa;font-size:.85rem;';
    pag.append(prev, info, next);
  }

  function load(p) {
    page = p || 1;
    const params = new URLSearchParams({ page, per_page: 20 });
    if (search) params.set('search', search);
    if (status !== '') params.set('is_active', status);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(err => { console.error(err); AL.toast('Erreur chargement', false); });
  }

  function loadStats() {
    fetch(`${DATA_URL}?per_page=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-total').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&is_active=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-active').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&is_active=0`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-inactive').textContent = d.total ?? '—'; });
  }

  document.addEventListener('DOMContentLoaded', function() {
    bulk = AL.initBulkBar({ listId:'cats', tbody: document.getElementById('cats-tbody'),
      cbAllId:'cats-cb-all', onSuccess: function(){ load(1); loadStats(); } });

    let t;
    document.getElementById('cats-search').addEventListener('input', function() {
      search = this.value; clearTimeout(t); t = setTimeout(() => load(1), 350);
    });
    document.getElementById('cats-status').addEventListener('change', function() {
      status = this.value; load(1);
    });

    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
