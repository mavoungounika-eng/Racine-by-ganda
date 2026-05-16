@extends('layouts.admin')

@section('title', 'KYC Créateurs')
@section('page-title', 'KYC Dashboard')
@section('page-subtitle', 'Vérification des comptes Stripe Connect des créateurs')

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'kyc',
    'bulkActions' => []
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="color:#ED5F1E;">Comptes KYC / Stripe Connect</h5>
  </div>

  {{-- Filtre statut --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    <select id="kyc-status" class="al-filter-select">
      <option value="">Tous les statuts</option>
      <option value="complete">Complets</option>
      <option value="pending">En attente</option>
      <option value="incomplete">Incomplets</option>
    </select>
  </div>

  {{-- Stats --}}
  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-complete" style="color:#4ade80;">—</span><small>Complets</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
    <div class="al-stat-item"><span id="stat-incomplete" style="color:#f87171;">—</span><small>Incomplets</small></div>
  </div>

  {{-- Tableau --}}
  <div class="table-responsive">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th>Créateur</th>
          <th>Email</th>
          <th>Compte Stripe</th>
          <th>Onboarding</th>
          <th>Virements</th>
          <th>Paiements</th>
          <th>Synchro</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="kyc-tbody"><tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Chargement…</td></tr></tbody>
    </table>
  </div>

  <div class="al-pag-bar mt-3" id="kyc-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
  const DATA_URL = '{{ route("admin.kyc.data") }}';
  let page = 1, status = '';

  function check(val) {
    return val
      ? '<span style="color:#4ade80;">✓</span>'
      : '<span style="color:#f87171;">✗</span>';
  }

  function onboardingBadge(s) {
    const map = { complete: ['#4ade80','Complet'], restricted: ['#fbbf24','Restreint'] };
    const [color, label] = map[s] || ['#f87171', s || 'Incomplet'];
    return `<span class="badge" style="background:rgba(0,0,0,.2);color:${color};border:1px solid ${color}40;">${label}</span>`;
  }

  function getName(a) {
    return a.creator_profile && a.creator_profile.user ? a.creator_profile.user.name : '—';
  }
  function getEmail(a) {
    return a.creator_profile && a.creator_profile.user ? a.creator_profile.user.email : '—';
  }

  function renderTable(data) {
    const tbody = document.getElementById('kyc-tbody');
    if (!data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(a => `
        <tr>
          <td><strong style="color:#eee;">${getName(a)}</strong></td>
          <td style="color:#aaa;font-size:.85rem;">${getEmail(a)}</td>
          <td><code style="color:#60a5fa;font-size:.75em;">${a.stripe_account_id || '—'}</code></td>
          <td>${onboardingBadge(a.onboarding_status)}</td>
          <td>${check(a.payouts_enabled)}</td>
          <td>${check(a.charges_enabled)}</td>
          <td style="color:#aaa;font-size:.8rem;">${a.last_synced_at ? a.last_synced_at.substring(0,16) : '—'}</td>
          <td>
            ${a.creator_profile && a.creator_profile.user
              ? `<a href="/admin/kyc/creator/${a.creator_profile.user.id}" class="al-action-btn" style="font-size:.75rem;">Détails</a>
                 <form method="POST" action="/admin/kyc/creator/${a.creator_profile.user.id}/sync" style="display:inline;">
                   <input type="hidden" name="_token" value="{{ csrf_token() }}">
                   <button type="submit" class="al-action-btn" style="font-size:.75rem;">Sync Stripe</button>
                 </form>`
              : '—'}
          </td>
        </tr>`).join('');
    }
    renderPag(data);
  }

  function renderPag(data) {
    const pag = document.getElementById('kyc-pag');
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
    if (status) params.set('status', status);
    fetch(`${DATA_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(renderTable).catch(() => AL.toast('Erreur chargement', false));
  }

  function loadStats() {
    fetch(`${DATA_URL}?per_page=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-total').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=complete`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-complete').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=pending`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-pending').textContent = d.total ?? '—'; });
    fetch(`${DATA_URL}?per_page=1&status=incomplete`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json()).then(d => { document.getElementById('stat-incomplete').textContent = d.total ?? '—'; });
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('kyc-status').addEventListener('change', function() { status = this.value; load(1); });
    load(1);
    loadStats();
  });
})();
</script>
@endpush
@endonce
