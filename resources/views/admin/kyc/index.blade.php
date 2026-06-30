@extends('layouts.admin')

@section('title', 'KYC Créateurs')
@section('page-title', 'KYC Dashboard')
@section('page-subtitle', 'Vérification des comptes Stripe Connect des créateurs')

@section('content')
@include('admin.components.admin-list', ['listId' => 'kyc', 'bulkActions' => []])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Comptes KYC / Stripe Connect</h5>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <select id="kyc-status" class="al-filter-select" aria-label="Statut KYC">
      <option value="">Tous statuts</option>
      <option value="complete">Complets</option>
      <option value="pending">En attente</option>
      <option value="incomplete">Incomplets</option>
    </select>
    <select id="kyc-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="kyc-reset" class="al-btn-reset" onclick="KYC.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat-item"><span id="stat-total">—</span><small>Total</small></div>
    <div class="al-stat-item"><span id="stat-complete" style="color:#4ade80;">—</span><small>Complets</small></div>
    <div class="al-stat-item"><span id="stat-pending" style="color:#fbbf24;">—</span><small>En attente</small></div>
    <div class="al-stat-item"><span id="stat-incomplete" style="color:#f87171;">—</span><small>Incomplets</small></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead>
        <tr>
          <th>Créateur</th>
          <th>Email</th>
          <th>Compte Stripe</th>
          <th id="th-onboarding" data-col="onboarding_status">Onboarding</th>
          <th id="th-payouts" data-col="payouts_enabled">Virements</th>
          <th id="th-charges" data-col="charges_enabled">Paiements</th>
          <th id="th-synced" data-col="last_synced_at">Synchro</th>
          <th class="al-sticky">Actions</th>
        </tr>
      </thead>
      <tbody id="kyc-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="kyc-pag"></div>
</div>
@endsection

@once
@push('scripts')
<script nonce="{{ csp_nonce() }}">
const KYC = (function(){
  const DATA_URL  = '{{ route("admin.kyc.data") }}';
  const CSRF_TOKEN = '{{ csrf_token() }}';
  const sortState = { by:'created_at', dir:'desc' };
  let state;
  const DEFAULTS = { status:'', page:1, per_page:20, sort_by:'created_at', sort_dir:'desc' };

  function chk(v) { return v ? '<span style="color:#4ade80;font-weight:700;">✓</span>' : '<span style="color:#f87171;">✗</span>'; }
  function onboardingBadge(s) {
    const m = { complete:['#4ade80','Complet'], restricted:['#fbbf24','Restreint'] };
    const [c, l] = m[s] || ['#f87171', s||'Incomplet'];
    return '<span class="badge" style="background:rgba(0,0,0,.2);color:'+c+';border:1px solid '+c+'40;">'+l+'</span>';
  }
  function getName(a) { return a.creator_profile&&a.creator_profile.user?a.creator_profile.user.name:'—'; }
  function getEmail(a) { return a.creator_profile&&a.creator_profile.user?a.creator_profile.user.email:'—'; }
  function getUserId(a) { return a.creator_profile&&a.creator_profile.user?a.creator_profile.user.id:null; }

  function renderTable(data) {
    const tbody = document.getElementById('kyc-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="al-empty"><div class="al-empty-icon">🔐</div>Aucun résultat</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(a) {
        const uid = getUserId(a);
        return (
          '<tr>'+
          '<td><strong style="color:#eee;">'+getName(a)+'</strong></td>'+
          '<td style="color:#aaa;font-size:.85rem;">'+getEmail(a)+'</td>'+
          '<td><code style="color:#60a5fa;font-size:.75em;">'+(a.stripe_account_id||'—')+'</code></td>'+
          '<td>'+onboardingBadge(a.onboarding_status)+'</td>'+
          '<td style="text-align:center;">'+chk(a.payouts_enabled)+'</td>'+
          '<td style="text-align:center;">'+chk(a.charges_enabled)+'</td>'+
          '<td style="color:#aaa;font-size:.8rem;">'+(a.last_synced_at?a.last_synced_at.substring(0,16):'—')+'</td>'+
          '<td class="al-sticky" style="white-space:nowrap;">'+(uid
            ? '<a href="/admin/kyc/creator/'+uid+'" class="al-action-btn" style="font-size:.75rem;">Détails</a> '+
              '<form method="POST" action="/admin/kyc/creator/'+uid+'/sync" style="display:inline;">'+
              '<input type="hidden" name="_token" value="'+CSRF_TOKEN+'">'+
              '<button type="submit" class="al-action-btn" style="font-size:.75rem;" onclick="return confirm(\'Synchroniser le compte Stripe de '+getName(a).replace(/'/g,"\\'")+'?\')">Sync</button>'+
              '</form>'
            : '—')+'</td>'+
          '</tr>'
        );
      }).join('');
    }
    AL.buildPager('kyc-pag', data, load);
    AL.syncUrl({ status:state.status, page:state.page, per_page:state.per_page });
    AL.updateResetBtn('kyc-reset', state.status ? 1 : 0);
  }

  function load(p) {
    state.page = p || 1;
    state.sort_by = sortState.by; state.sort_dir = sortState.dir;
    AL.skeleton(document.getElementById('kyc-tbody'), 8);
    const params = new URLSearchParams({ page:state.page, per_page:state.per_page });
    if (state.status) params.set('status', state.status);
    fetch(DATA_URL+'?'+params, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement données', false); });
  }

  function loadStats() {
    [['stat-total',''],['stat-complete','&status=complete'],['stat-pending','&status=pending'],['stat-incomplete','&status=incomplete']].forEach(function(pair) {
      fetch(DATA_URL+'?per_page=1'+pair[1], { headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r){ return r.json(); })
        .then(function(d){ const el=document.getElementById(pair[0]); if(el) el.textContent=d.total??'—'; })
        .catch(function(){});
    });
  }

  function init() {
    state = AL.readUrl(DEFAULTS);
    ['th-onboarding','th-payouts','th-charges','th-synced'].forEach(function(id){ const th=document.getElementById(id); if(!th) return; AL.makeSortable(th,th.dataset.col,sortState,function(){ load(1); }); });
    const st=document.getElementById('kyc-status'); if(st){ st.value=state.status; st.addEventListener('change',function(){ state.status=this.value; load(1); }); }
    const pp=document.getElementById('kyc-per-page'); if(pp){ pp.value=state.per_page; pp.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }
    load(parseInt(state.page,10)||1);
    loadStats();
  }

  document.addEventListener('DOMContentLoaded', init);
  return {
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      const st=document.getElementById('kyc-status'); if(st) st.value='';
      load(1);
    }
  };
})();
</script>
@endpush
@endonce
