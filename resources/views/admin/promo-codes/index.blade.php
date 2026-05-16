@extends('layouts.admin-master')

@section('title', 'Codes Promo')
@section('page-title', 'Codes Promo')
@section('page-subtitle', 'Gérez les codes de réduction appliqués au checkout')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'promos',
    'bulkActions' => [
        ['label' => 'Désactiver', 'endpoint' => route('admin.promo-codes.bulk-disable'), 'confirm' => 'Désactiver {n} code(s) ?', 'danger' => true],
        ['label' => 'Supprimer', 'endpoint' => route('admin.promo-codes.bulk-delete'), 'confirm' => 'Supprimer {n} code(s) inutilisés ? Les codes déjà utilisés seront ignorés.', 'danger' => true],
    ]
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Codes Promo</h5>
    <a href="{{ route('admin.promo-codes.create') }}" class="al-action-btn">+ Nouveau code</a>
  </div>

  {{-- Filtres --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" id="promos-search" class="al-filter-input" placeholder="Code ou nom…" style="min-width:200px;">
    <select id="promos-type" class="al-filter-select">
      <option value="">Tous les types</option>
      <option value="percentage">Pourcentage</option>
      <option value="fixed">Fixe</option>
      <option value="free_shipping">Livraison gratuite</option>
    </select>
    <select id="promos-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="1">Actifs</option>
      <option value="0">Inactifs</option>
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
          <th style="width:36px;"><input type="checkbox" id="promos-cb-all" class="al-cb"></th>
          <th>Code</th>
          <th>Nom</th>
          <th>Type</th>
          <th>Valeur</th>
          <th>Utilisations</th>
          <th>Limite</th>
          <th>Expire</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="promos-tbody"><tr><td colspan="10" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  <div class="al-pag-bar mt-3" id="promos-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.promo-codes.data") }}';
  let page = 1, search = '', type = '', status = '', bulk;

  function typeBadge(t) {
    const map = { percentage: ['#FFB800','%'], fixed: ['#60a5fa','Fixe'], free_shipping: ['#c084fc','Livraison'] };
    const [color, label] = map[t] || ['#aaa', t || '—'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function activeTag(active) {
    return active
      ? '<span class="badge" style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);">Actif</span>'
      : '<span class="badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">Inactif</span>';
  }

  function fmtValue(c) {
    if (c.type === 'percentage') return (c.discount_value || 0) + ' %';
    if (c.type === 'fixed') return (c.discount_value || 0) + ' ' + (c.currency || 'XAF');
    return '—';
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('promos-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(c => `
        <tr>
          <td><input type="checkbox" class="al-row-cb al-cb" data-id="${c.id}"></td>
          <td><code style="color:#ED5F1E;font-size:.85em;">${c.code}</code></td>
          <td style="color:#ccc;">${c.name || '—'}</td>
          <td>${typeBadge(c.type)}</td>
          <td style="color:#eee;">${fmtValue(c)}</td>
          <td style="color:#ccc;">${c.used_count ?? 0}</td>
          <td style="color:#ccc;">${c.max_uses ? c.max_uses : '∞'}</td>
          <td style="color:#aaa;font-size:.8rem;">${c.expires_at ? c.expires_at.substring(0,10) : '—'}</td>
          <td>${activeTag(c.is_active)}</td>
          <td>
            <a href="/admin/promo-codes/${c.id}/edit" class="al-action-btn" style="font-size:.75rem;">Éditer</a>
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('promos-pag');
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
    if (type) params.set('type', type);
    if (status !== '') params.set('is_active', status);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(() => AL.toast('Erreur chargement', false));
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
    bulk = AL.initBulkBar({ listId:'promos', tbody: document.getElementById('promos-tbody'),
      cbAllId:'promos-cb-all', onSuccess: function(){ load(1); loadStats(); } });

    let t;
    document.getElementById('promos-search').addEventListener('input', function() {
      search = this.value; clearTimeout(t); t = setTimeout(() => load(1), 350);
    });
    document.getElementById('promos-type').addEventListener('change', function() { type = this.value; load(1); });
    document.getElementById('promos-status').addEventListener('change', function() { status = this.value; load(1); });

    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
