@extends('layouts.admin')

@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des Utilisateurs')
@section('page-subtitle', 'Gérer les comptes utilisateurs de la plateforme')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'users',
    'bulkActions' => [
        ['label' => 'Désactiver', 'endpoint' => route('admin.users.bulk-disable'), 'confirm' => 'Désactiver {n} utilisateur(s) ?', 'danger' => true],
        ['label' => 'Supprimer', 'endpoint' => route('admin.users.bulk-delete'), 'confirm' => 'Supprimer définitivement {n} utilisateur(s) ? Cette action est irréversible.', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Utilisateurs</h5>
    <a href="{{ route('admin.users.create') }}" class="al-action-btn">+ Nouvel utilisateur</a>
  </div>

  {{-- Filtres --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" id="users-search" class="al-filter-input" placeholder="Nom ou email…" style="min-width:220px;">
    <select id="users-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="active">Actif</option>
      <option value="inactive">Inactif</option>
      <option value="suspended">Suspendu</option>
    </select>
    <select id="users-role" class="al-filter-select">
      <option value="">Tous les rôles</option>
      <option value="admin">Admin</option>
      <option value="createur">Créateur</option>
      <option value="client">Client</option>
    </select>
  </div>

  {{-- Stats --}}
  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actifs</small></div>
    <div class="al-stat-item"><span id="stat-inactive" style="color:#f87171;">—</span><small>Inactifs</small></div>
  </div>

  {{-- Tableau --}}
  <div class="table-responsive">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="users-cb-all" class="al-cb"></th>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Statut</th>
          <th>Inscription</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="users-tbody"><tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  <div class="al-pag-bar mt-3" id="users-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.users.data") }}';
  let page = 1, search = '', status = '', role = '', bulk;

  function statusBadge(s) {
    const map = {
      active: ['#4ade80','Active'],
      inactive: ['#f87171','Inactive'],
      suspended: ['#fbbf24','Suspendu'],
    };
    const [color, label] = map[s] || ['#aaa', s || '—'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function roleBadge(r) {
    const map = { admin: '#ED5F1E', createur: '#FFB800', client: '#60a5fa' };
    const color = map[r] || '#aaa';
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${r || '—'}</span>`;
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('users-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(u => `
        <tr>
          <td><input type="checkbox" class="al-row-cb al-cb" data-id="${u.id}"></td>
          <td><strong style="color:#eee;">${u.name}</strong></td>
          <td style="color:#aaa;font-size:.85rem;">${u.email}</td>
          <td>${roleBadge(u.role)}</td>
          <td>${statusBadge(u.status)}</td>
          <td style="color:#aaa;font-size:.8rem;">${u.created_at ? u.created_at.substring(0,10) : '—'}</td>
          <td>
            <a href="/admin/users/${u.id}" class="al-action-btn" style="font-size:.75rem;">Voir</a>
            <a href="/admin/users/${u.id}/edit" class="al-action-btn" style="font-size:.75rem;">Éditer</a>
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('users-pag');
    pag.innerHTML = '';
    if (data.last_page <= 1) return;
    const prev = document.createElement('button');
    prev.textContent = '← Préc.'; prev.className = 'al-action-btn'; prev.disabled = data.current_page <= 1;
    prev.onclick = () => load(data.current_page - 1);
    const next = document.createElement('button');
    next.textContent = 'Suiv. →'; next.className = 'al-action-btn'; next.disabled = data.current_page >= data.last_page;
    next.onclick = () => load(data.current_page + 1);
    const info = document.createElement('span');
    info.textContent = `Page ${data.current_page} / ${data.last_page} (${data.total})`;
    info.style.cssText = 'color:#aaa;font-size:.85rem;';
    pag.append(prev, info, next);
  }

  function load(p) {
    page = p || 1;
    const params = new URLSearchParams({ page, per_page: 20 });
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (role) params.set('role', role);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(() => AL.toast('Erreur chargement', false));
  }

  function loadStats() {
    fetch(`${DATA_URL}?per_page=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-total').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=active`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-active').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=inactive`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-inactive').textContent = d.total ?? '—'; });
  }

  document.addEventListener('DOMContentLoaded', function() {
    bulk = AL.initBulkBar({ listId:'users', tbody: document.getElementById('users-tbody'),
      cbAllId:'users-cb-all', onSuccess: function(){ load(1); loadStats(); } });

    let t;
    document.getElementById('users-search').addEventListener('input', function() {
      search = this.value; clearTimeout(t); t = setTimeout(() => load(1), 350);
    });
    document.getElementById('users-status').addEventListener('change', function() {
      status = this.value; load(1);
    });
    document.getElementById('users-role').addEventListener('change', function() {
      role = this.value; load(1);
    });

    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
