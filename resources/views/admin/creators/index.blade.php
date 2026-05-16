@extends('layouts.admin-master')

@section('title', 'Créateurs')
@section('page-title', 'Gestion des Créateurs')
@section('page-subtitle', 'Gérer les créateurs partenaires et leurs documents')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'creators',
    'bulkActions' => [
        ['label' => 'Vérifier', 'endpoint' => route('admin.creators.bulk-verify'), 'confirm' => 'Vérifier et activer {n} créateur(s) ?'],
        ['label' => 'Suspendre', 'endpoint' => route('admin.creators.bulk-suspend'), 'confirm' => 'Suspendre {n} créateur(s) ?', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Créateurs</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.creators.reports.validation') }}" class="al-action-btn">Rapport validation</a>
      <a href="{{ route('admin.creators.export.csv') }}" class="al-action-btn">Export CSV</a>
    </div>
  </div>

  {{-- Filtres --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" id="creators-search" class="al-filter-input" placeholder="Nom, marque, email…" style="min-width:220px;">
    <select id="creators-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="active">Actif</option>
      <option value="pending">En attente</option>
      <option value="suspended">Suspendu</option>
      <option value="rejected">Rejeté</option>
    </select>
    <select id="creators-verified" class="al-filter-select">
      <option value="">Vérification (tous)</option>
      <option value="1">Vérifiés</option>
      <option value="0">Non vérifiés</option>
    </select>
  </div>

  {{-- Stats --}}
  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-active" style="color:#4ade80;">—</span><small>Actifs</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
    <div class="al-stat-item"><span id="stat-suspended" style="color:#f87171;">—</span><small>Suspendus</small></div>
  </div>

  {{-- Tableau --}}
  <div class="table-responsive">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th style="width:36px;"><input type="checkbox" id="creators-cb-all" class="al-cb"></th>
          <th>Créateur</th>
          <th>Email</th>
          <th>Marque</th>
          <th>Statut</th>
          <th>Vérifié</th>
          <th>Documents</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="creators-tbody"><tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  <div class="al-pag-bar mt-3" id="creators-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.creators.data") }}';
  let page = 1, search = '', status = '', verified = '', bulk;

  function statusBadge(s) {
    const map = {
      active: ['#4ade80','Actif'],
      pending: ['#fbbf24','En attente'],
      suspended: ['#f87171','Suspendu'],
      rejected: ['#f87171','Rejeté'],
    };
    const [color, label] = map[s] || ['#aaa', s || '—'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function verifiedBadge(v) {
    return v
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">✓ Vérifié</span>'
      : '<span class="badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">Non vérifié</span>';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('creators-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(c => `
        <tr>
          <td><input type="checkbox" class="al-row-cb al-cb" data-id="${c.id}"></td>
          <td><strong style="color:#eee;">${c.user ? c.user.name : '—'}</strong></td>
          <td style="color:#aaa;font-size:.85rem;">${c.user ? c.user.email : '—'}</td>
          <td style="color:#ccc;">${c.brand_name || '—'}</td>
          <td>${statusBadge(c.status)}</td>
          <td>${verifiedBadge(c.is_verified)}</td>
          <td style="color:#ccc;">${c.documents_count ?? 0} docs</td>
          <td>
            <a href="/admin/creators/${c.id}" class="al-action-btn" style="font-size:.75rem;">Voir</a>
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('creators-pag');
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
    if (verified !== '') params.set('is_verified', verified);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(() => AL.toast('Erreur chargement', false));
  }

  function loadStats() {
    fetch(`${DATA_URL}?per_page=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-total').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=active`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-active').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=pending`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-pending').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=suspended`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-suspended').textContent = d.total ?? '—'; });
  }

  document.addEventListener('DOMContentLoaded', function() {
    bulk = AL.initBulkBar({ listId:'creators', tbody: document.getElementById('creators-tbody'),
      cbAllId:'creators-cb-all', onSuccess: function(){ load(1); loadStats(); } });

    let t;
    document.getElementById('creators-search').addEventListener('input', function() {
      search = this.value; clearTimeout(t); t = setTimeout(() => load(1), 350);
    });
    document.getElementById('creators-status').addEventListener('change', function() { status = this.value; load(1); });
    document.getElementById('creators-verified').addEventListener('change', function() { verified = this.value; load(1); });

    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
