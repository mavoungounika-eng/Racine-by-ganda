@extends('layouts.admin')
@section('title', 'Sessions POS — Admin')
@section('page-title', 'Sessions POS')
@section('page-subtitle', 'Supervision et contrôle des sessions de caisse')

@once
@push('styles')
<style nonce="{{ csp_nonce() }}">
.badge-open{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.badge-closed{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25)}
.badge-closing{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.badge-fantome{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.dot-live{width:6px;height:6px;border-radius:50%;background:#4ade80;box-shadow:0 0 6px #4ade80;display:inline-block;animation:pos-pulse 1.5s infinite}
@keyframes pos-pulse{0%,100%{opacity:1}50%{opacity:.3}}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1050;display:none;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#1a1008;border:1px solid rgba(237,95,30,.25);border-radius:16px;padding:1.75rem;width:min(560px,94vw);max-height:90vh;overflow-y:auto}
.modal-title{font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center}
.modal-close{background:none;border:none;color:#888;font-size:1.5rem;cursor:pointer;line-height:1;padding:0 .25rem}
.modal-close:hover{color:#eee}
.detail-row{display:flex;justify-content:space-between;padding:.45rem 0;font-size:.875rem;border-bottom:1px solid rgba(212,165,116,.06)}
.detail-label{color:#888}.detail-val{color:#e2e8f0;font-weight:500}
.sales-table{width:100%;border-collapse:collapse;font-size:.8rem;margin-top:.75rem}
.sales-table th{color:#888;font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;padding:.4rem .6rem;border-bottom:1px solid rgba(212,165,116,.1);text-align:left}
.sales-table td{padding:.45rem .6rem;border-bottom:1px solid rgba(212,165,116,.05);color:#e2e8f0}
.btn-close-session{background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.3);border-radius:6px;padding:4px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s}
.btn-close-session:hover:not(:disabled){background:rgba(239,68,68,.35)}
.btn-close-session:disabled{opacity:.4;cursor:not-allowed}
</style>
@endpush
@endonce

@section('content')
@include('admin.components.admin-list', [
    'listId' => 'sessions',
    'bulkActions' => [
        ['label' => 'Clôturer sélection', 'endpoint' => route('pos.interface.sessions.bulk-close'), 'confirm' => 'Clôturer {n} session(s) ouvertes ?', 'danger' => true],
    ],
])

<div class="al-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0" style="color:#ED5F1E;">Sessions POS</h5>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <a id="btn-export-global" href="#" class="al-action-btn">↓ Export CSV</a>
      <button id="btn-refresh" class="al-action-btn" title="Rafraîchir">⟳</button>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <select id="f-status" class="al-filter-select" aria-label="Statut">
      <option value="">Tous statuts</option>
      <option value="open">Ouvertes</option>
      <option value="closing">En clôture</option>
      <option value="closed">Fermées</option>
      <option value="fantomes">Fantômes</option>
    </select>
    <select id="f-operateur" class="al-filter-select" aria-label="Opérateur">
      <option value="">Tous opérateurs</option>
      @foreach($operateurs as $op)
      <option value="{{ $op->id }}">{{ $op->name }}</option>
      @endforeach
    </select>
    <input type="date" id="f-debut" class="al-filter-input" aria-label="Date début">
    <input type="date" id="f-fin"   class="al-filter-input" aria-label="Date fin">
    <select id="f-per-page" class="al-per-page" aria-label="Par page">
      <option value="10">10 / page</option>
      <option value="20" selected>20 / page</option>
      <option value="50">50 / page</option>
      <option value="100">100 / page</option>
    </select>
    <button id="btn-reset" class="al-btn-reset" onclick="POS.reset()">
      Réinitialiser<span class="al-filter-badge">0</span>
    </button>
  </div>

  <div class="al-stats mb-3">
    <div class="al-stat"><div class="al-stat-label">Sessions actives</div><div class="al-stat-value" style="color:#4ade80" id="stat-open">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Sessions fantômes</div><div class="al-stat-value" style="color:#f87171" id="stat-fantomes">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Ventes du jour</div><div class="al-stat-value" style="color:#ED5F1E" id="stat-ventes">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Total sessions (30j)</div><div class="al-stat-value" id="stat-total">—</div></div>
  </div>

  <div class="al-table-wrap">
    <table class="al-table w-100">
      <thead><tr>
        <th style="width:36px;"><input type="checkbox" id="sessions-cb-all" class="al-cb" aria-label="Tout sélectionner"></th>
        <th>#</th><th>Opérateur</th><th>Machine</th><th>Statut</th>
        <th>Ouverture</th><th>Durée</th><th>Ventes</th><th>Tickets</th><th>Fond caisse</th>
        <th class="al-sticky">Actions</th>
      </tr></thead>
      <tbody id="sessions-tbody"></tbody>
    </table>
  </div>
  <div class="al-pag-bar mt-3" id="sessions-pag"></div>
</div>

<div class="modal-overlay" id="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title-text">
  <div class="modal-box" id="modal-box">
    <div class="modal-title">
      <span id="modal-title-text">Session</span>
      <button class="modal-close" id="modal-close" aria-label="Fermer">&times;</button>
    </div>
    <div id="modal-body"></div>
  </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const POS = (function(){
  const h  = {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'};
  const hj = {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'X-Requested-With':'XMLHttpRequest'};
  let state, bulk, statsTimer, salesCache = {};
  const DEFAULTS = { status:'', operateur:'', debut:'', fin:'', page:1, per_page:20 };

  function activeFilters() {
    return [state.status, state.operateur, state.debut, state.fin].filter(Boolean).length;
  }
  function fmt(v){ try{ return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; }catch(e){ return (v||0)+' FCFA'; } }
  function fmtDate(d){ return d ? new Date(d).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '—'; }
  function fmtDateFull(d){ return d ? new Date(d).toLocaleString('fr-FR') : '—'; }
  function duree(s){ const from=new Date(s.opened_at), to=s.closed_at?new Date(s.closed_at):new Date(); const mins=Math.floor((to-from)/60000), hh=Math.floor(mins/60), mm=mins%60; return hh>0?(mm>0?hh+'h'+mm+'min':hh+'h'):mins+'min'; }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function badgeHtml(s){
    const fantome = s.status==='open' && !s.last_activity_at;
    if(fantome) return '<span class="badge badge-fantome">Fantôme</span>';
    if(s.status==='open') return '<span class="badge badge-open"><span class="dot-live"></span>Active</span>';
    if(s.status==='closing') return '<span class="badge badge-closing">Clôture</span>';
    return '<span class="badge badge-closed">Fermée</span>';
  }

  function buildParams() {
    const p = new URLSearchParams({ page:state.page, per_page:state.per_page });
    if (state.status==='fantomes') p.set('fantomes_only','1');
    else if (state.status) p.set('status', state.status);
    if (state.operateur) p.set('operateur_id', state.operateur);
    if (state.debut)     p.set('date_debut', state.debut);
    if (state.fin)       p.set('date_fin',   state.fin);
    return p;
  }

  function renderTable(data) {
    if (bulk) bulk.clear();
    const tbody = document.getElementById('sessions-tbody');
    if (!data.data || !data.data.length) {
      tbody.innerHTML = '<tr><td colspan="11" class="al-empty"><div class="al-empty-icon">🖥️</div>Aucune session trouvée</td></tr>';
    } else {
      tbody.innerHTML = data.data.map(function(s) { return (
        '<tr>'+
        '<td><input type="checkbox" class="al-row-cb al-cb" data-id="'+s.id+'" aria-label="Sélectionner session #'+s.id+'"></td>'+
        '<td style="color:#888;font-size:.8rem">'+esc(s.id)+'</td>'+
        '<td><div style="font-weight:600">'+esc(s.opener&&s.opener.name||'—')+'</div><div style="font-size:.75rem;color:#888">'+esc(s.opener&&s.opener.email||'')+'</div></td>'+
        '<td style="font-family:monospace;font-size:.78rem;color:#aaa">'+esc(s.machine_name||(s.machine_id?s.machine_id.slice(0,12)+'…':'—'))+'</td>'+
        '<td>'+badgeHtml(s)+'</td>'+
        '<td style="color:#aaa;font-size:.82rem">'+fmtDate(s.opened_at)+'</td>'+
        '<td style="color:#aaa;font-size:.82rem">'+duree(s)+'</td>'+
        '<td style="font-weight:600;color:#4ade80">'+fmt(s.total_ventes||0)+'</td>'+
        '<td style="color:#ccc;text-align:center">'+(s.nombre_tickets||0)+'</td>'+
        '<td style="color:#ccc">'+fmt(s.opening_cash||0)+'</td>'+
        '<td class="al-sticky" style="white-space:nowrap;">'+
          '<a href="/pos-terminal/sessions/'+s.id+'/detail" class="al-action-btn" style="font-size:.75rem;">Détail</a> '+
          ((s.status==='open'||s.status==='closing')
            ? '<button class="btn-close-session" style="font-size:.75rem;" onclick="POS.close('+s.id+',\''+esc(s.opener&&s.opener.name||'')+'\')">Clôturer</button>'
            : '')+
        '</td>'+
        '</tr>'
      ); }).join('');
    }
    AL.buildPager('sessions-pag', data, function(p){ load(p); });
    AL.syncUrl({ status:state.status, operateur:state.operateur, debut:state.debut, fin:state.fin, page:state.page, per_page:state.per_page });
    AL.updateResetBtn('btn-reset', activeFilters());
    updateExportLink();
  }

  function updateExportLink() {
    const btn = document.getElementById('btn-export-global');
    if (!btn) return;
    const p = buildParams();
    p.delete('page'); p.delete('per_page');
    btn.href = '/pos-terminal/sessions/export-global' + (p.toString() ? '?' + p.toString() : '');
  }

  function load(p) {
    state.page = p || 1;
    AL.skeleton(document.getElementById('sessions-tbody'), 11);
    fetch('/pos-terminal/sessions/data?' + buildParams(), { credentials:'same-origin', headers:h })
      .then(function(r){ return r.json(); }).then(renderTable)
      .catch(function(){ AL.toast('Erreur chargement sessions', false); });
  }

  function loadStats() {
    Promise.all([
      fetch('/pos-terminal/sessions/data?status=open&per_page=100',    {credentials:'same-origin',headers:h}).then(function(r){ return r.json(); }),
      fetch('/pos-terminal/sessions/data?fantomes_only=1&per_page=100', {credentials:'same-origin',headers:h}).then(function(r){ return r.json(); }),
      fetch('/pos-terminal/sessions/data?per_page=1',                   {credentials:'same-origin',headers:h}).then(function(r){ return r.json(); }),
    ]).then(function(results) {
      const ventes = (results[0].data||[]).reduce(function(s,x){ return s+parseFloat(x.total_ventes||0); }, 0);
      document.getElementById('stat-open').textContent     = results[0].total || 0;
      document.getElementById('stat-fantomes').textContent = results[1].total || 0;
      document.getElementById('stat-ventes').textContent   = fmt(ventes);
      document.getElementById('stat-total').textContent    = results[2].total || 0;
    }).catch(function(){});
  }

  function startAutoRefresh() {
    clearInterval(statsTimer);
    statsTimer = setInterval(function() { loadStats(); }, 60000);
  }

  async function openDetail(id) {
    const sessions = document.getElementById('sessions-tbody').querySelectorAll('.al-row-cb');
    let s = null;
    sessions.forEach(function(cb){ if(parseInt(cb.dataset.id,10)===id) s = id; });
    document.getElementById('modal-title-text').textContent = 'Session #'+id;
    document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:2rem;color:#888;">Chargement…</div>';
    document.getElementById('modal-overlay').classList.add('open');

    try {
      const r = await fetch('/pos-terminal/sessions/'+id+'/detail?json=1', { credentials:'same-origin', headers:h });
      if (!r.ok) throw new Error('HTTP '+r.status);
    } catch(e) {}

    const allSessions = Array.from(document.querySelectorAll('#sessions-tbody tr')).reduce(function(acc, tr) {
      const cb = tr.querySelector('.al-row-cb');
      if (cb && parseInt(cb.dataset.id,10)===id) return tr;
      return acc;
    }, null);

    const detailLink = '<div style="text-align:center;padding:1rem;"><a href="/pos-terminal/sessions/'+id+'/detail" class="al-action-btn">Voir page détail complète →</a></div>';
    const salesHtml = '<div style="margin-top:1.25rem;"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;"><span style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:#888;">Ventes</span><a href="/pos-terminal/sessions/'+id+'/export-csv" class="al-action-btn" style="font-size:.75rem;color:#4ade80;border-color:rgba(74,222,128,.3);">↓ CSV</a></div><div id="sales-container-'+id+'"><div style="text-align:center;padding:1rem;color:#888;font-size:.82rem;">Chargement ventes…</div></div></div>';

    document.getElementById('modal-body').innerHTML = detailLink + salesHtml;
    loadSales(id);
  }

  async function loadSales(id) {
    if (salesCache[id]) { renderSales(id, salesCache[id]); return; }
    try {
      const r = await fetch('/pos-terminal/sessions/'+id+'/sales', { credentials:'same-origin', headers:h });
      const d = await r.json();
      salesCache[id] = d.data || d || [];
      renderSales(id, salesCache[id]);
    } catch(e) {
      const c = document.getElementById('sales-container-'+id);
      if(c) c.innerHTML = '<div style="text-align:center;padding:1rem;color:#888;font-size:.82rem;">Erreur chargement ventes</div>';
    }
  }

  function renderSales(id, sales) {
    const c = document.getElementById('sales-container-'+id);
    if (!c) return;
    if (!sales.length) { c.innerHTML='<div style="text-align:center;padding:1rem;color:#666;font-size:.82rem;">Aucune vente pour cette session</div>'; return; }
    c.innerHTML = '<div style="max-height:220px;overflow-y:auto;"><table class="sales-table"><thead><tr><th>#</th><th>Date</th><th>Montant</th><th>Paiement</th><th>Statut</th></tr></thead><tbody>'+
      sales.map(function(s){ return '<tr><td style="color:#888">'+esc(s.id)+'</td><td>'+fmtDate(s.created_at)+'</td><td style="font-weight:600;color:#4ade80">'+fmt(s.total_amount||0)+'</td><td style="text-transform:capitalize">'+esc(s.payments&&s.payments[0]&&s.payments[0].method||'—')+'</td><td>'+esc(s.status||'—')+'</td></tr>'; }).join('')+
      '</tbody></table></div>';
  }

  async function forceClose(id, name) {
    if (!confirm('Clôturer session #'+id+' ('+name+') ? Action admin irréversible.')) return;
    const btn = event.target; if(btn) btn.disabled = true;
    try {
      const r = await fetch('/pos-terminal/sessions/'+id+'/force-close', { method:'POST', credentials:'same-origin', headers:hj });
      const d = await r.json().catch(function(){ return {}; });
      if (r.ok) { AL.toast('Session #'+id+' clôturée ✓'); load(); loadStats(); }
      else { AL.toast(d.message || 'Erreur clôture', false, r.status); }
    } catch(e) { AL.toast('Erreur réseau', false); }
    finally { if(btn) btn.disabled = false; }
  }

  function closeModal() { document.getElementById('modal-overlay').classList.remove('open'); }

  function init() {
    state = AL.readUrl(DEFAULTS);

    bulk = AL.initBulkBar({ listId:'sessions', tbody:document.getElementById('sessions-tbody'),
      cbAllId:'sessions-cb-all', onSuccess:function(){ load(); loadStats(); } });

    document.getElementById('btn-refresh').addEventListener('click', function(){ load(); loadStats(); });

    const fields = [['f-status','status'],['f-operateur','operateur'],['f-debut','debut'],['f-fin','fin']];
    fields.forEach(function(f) {
      const el = document.getElementById(f[0]); if(!el) return;
      el.value = state[f[1]] || '';
      el.addEventListener('change', function(){ state[f[1]]=this.value; load(1); });
    });
    const pp = document.getElementById('f-per-page'); if(pp){ pp.value=state.per_page; pp.addEventListener('change',function(){ state.per_page=parseInt(this.value,10); load(1); }); }

    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-overlay').addEventListener('click', function(e){ if(e.target===this) closeModal(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });

    load(parseInt(state.page,10)||1);
    loadStats();
    startAutoRefresh();
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) clearInterval(statsTimer); else { loadStats(); startAutoRefresh(); }
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    detail:     openDetail,
    close:      forceClose,
    closeModal: closeModal,
    reset: function() {
      state = Object.assign({}, DEFAULTS);
      ['f-status','f-operateur','f-debut','f-fin'].forEach(function(id){ const el=document.getElementById(id); if(el) el.value=''; });
      load(1);
    }
  };
})();
</script>
@endpush
