@extends('layouts.admin')
@section('title', 'Sessions POS — Contrôle Admin')
@section('page-title', 'Sessions POS')

@push('styles')
<style nonce="{{ csp_nonce() }}">
.sessions-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.sessions-filters{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}
.filter-select,.filter-input{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.2);border-radius:8px;color:#e2e8f0;padding:.45rem .85rem;font-size:.85rem}
.filter-select:focus,.filter-input:focus{outline:none;border-color:#ED5F1E}
.btn-reset{background:transparent;color:#aaa;border:1px solid rgba(212,165,116,.2);border-radius:8px;padding:.45rem .85rem;font-size:.85rem;cursor:pointer}
.sessions-table-wrap{overflow-x:auto}
.sessions-table{width:100%;border-collapse:collapse;font-size:.875rem}
.sessions-table th{background:rgba(22,13,12,.9);color:#aaa;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;padding:.7rem 1rem;text-align:left;border-bottom:1px solid rgba(212,165,116,.1);white-space:nowrap}
.sessions-table td{padding:.8rem 1rem;border-bottom:1px solid rgba(212,165,116,.06);color:#e2e8f0;vertical-align:middle}
.sessions-table tr:hover td{background:rgba(237,95,30,.04)}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
.badge-open{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.badge-closed{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25)}
.badge-closing{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.badge-fantome{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.dot-live{width:6px;height:6px;border-radius:50%;background:#4ade80;box-shadow:0 0 6px #4ade80;display:inline-block;animation:pulse-dot 1.5s infinite}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.3}}
.btn-action{border:none;border-radius:6px;padding:4px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:opacity .15s}
.btn-action:hover{opacity:.8}
.btn-close-session{background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.btn-detail{background:rgba(237,95,30,.15);color:#ED5F1E;border:1px solid rgba(237,95,30,.3)}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-card{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:12px;padding:1rem 1.25rem}
.stat-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:#888;margin-bottom:.3rem}
.stat-value{font-size:1.6rem;font-weight:800;color:#e2e8f0}
.stat-value.orange{color:#ED5F1E}.stat-value.red{color:#f87171}.stat-value.green{color:#4ade80}
.empty-state{text-align:center;padding:3rem;color:#666}
.pag-bar{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;font-size:.82rem;color:#888}
.pag-btns{display:flex;gap:.5rem}
.pag-btn{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.15);color:#ccc;border-radius:6px;padding:4px 12px;cursor:pointer;font-size:.8rem}
.pag-btn:disabled{opacity:.3;cursor:default}
.pag-btn.active{background:#ED5F1E;color:#fff;border-color:#ED5F1E}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;display:none;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#1a1008;border:1px solid rgba(237,95,30,.25);border-radius:16px;padding:1.75rem;width:min(560px,94vw);max-height:90vh;overflow-y:auto}
.modal-title{font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center}
.modal-close{background:none;border:none;color:#888;font-size:1.4rem;cursor:pointer;line-height:1}
.detail-row{display:flex;justify-content:space-between;padding:.45rem 0;font-size:.875rem;border-bottom:1px solid rgba(212,165,116,.06)}
.detail-label{color:#888}.detail-val{color:#e2e8f0;font-weight:500}
.sales-table{width:100%;border-collapse:collapse;font-size:.8rem;margin-top:.75rem}
.sales-table th{color:#888;font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;padding:.4rem .6rem;border-bottom:1px solid rgba(212,165,116,.1);text-align:left}
.sales-table td{padding:.45rem .6rem;border-bottom:1px solid rgba(212,165,116,.05);color:#e2e8f0}
.btn-export{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3);border-radius:6px;padding:5px 14px;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn-export:hover{background:rgba(34,197,94,.25)}
.sales-empty{text-align:center;padding:1rem;color:#666;font-size:.82rem}
</style>
@endpush

@section('content')

@include('admin.components.admin-list', [
    'listId' => 'sessions',
    'bulkActions' => [
        [
            'label'    => 'Clotures selection',
            'endpoint' => '/pos-terminal/sessions/bulk-close',
            'confirm'  => 'Cloturer {n} session(s) ouvertes ?',
        ],
    ],
])

<div id="pos-app">

  <div class="stats-row">
    <div class="stat-card"><div class="stat-label">Sessions actives</div><div class="stat-value green" id="stat-open">&#8212;</div></div>
    <div class="stat-card"><div class="stat-label">Sessions fantomes</div><div class="stat-value red" id="stat-fantomes">&#8212;</div></div>
    <div class="stat-card"><div class="stat-label">Ventes du jour</div><div class="stat-value orange" id="stat-ventes">&#8212;</div></div>
    <div class="stat-card"><div class="stat-label">Total sessions (30j)</div><div class="stat-value" id="stat-total">&#8212;</div></div>
  </div>

  <div class="sessions-header">
    <div style="display:flex;gap:.5rem;align-items:center">
      <h2 style="margin:0;font-size:1.1rem;color:#e2e8f0">Sessions POS</h2>
      <button id="btn-refresh" style="background:none;border:none;color:#888;cursor:pointer;font-size:1.1rem" title="Rafraichir">&#8635;</button>
    </div>
    <div class="sessions-filters">
      <select class="filter-select" id="f-status">
        <option value="">Tous statuts</option>
        <option value="open">Ouvertes</option>
        <option value="closing">En cloture</option>
        <option value="closed">Fermees</option>
        <option value="fantomes">Fantomes</option>
      </select>
      <select class="filter-select" id="f-operateur">
        <option value="">Tous operateurs</option>
        @foreach($operateurs as $op)
        <option value="{{ $op->id }}">{{ $op->name }}</option>
        @endforeach
      </select>
      <input type="date" class="filter-input" id="f-debut">
      <input type="date" class="filter-input" id="f-fin">
      <button class="btn-reset" id="btn-reset">Reinitialiser</button>
      <a id="btn-export-global" class="btn-export" href="#" target="_blank">&#8595; Export CSV</a>
    </div>
  </div>

  <div style="background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:16px;overflow:hidden">
    <div class="sessions-table-wrap">
      <table class="sessions-table">
        <thead><tr>
          <th style="width:36px;padding-right:.5rem"><input type="checkbox" id="sessions-cb-all" class="al-cb"></th>
          <th>#</th><th>Operateur</th><th>Machine</th><th>Statut</th>
          <th>Ouverture</th><th>Duree</th><th>Ventes</th><th>Tickets</th><th>Fond caisse</th><th>Actions</th>
        </tr></thead>
        <tbody id="sessions-tbody"><tr><td colspan="11" class="empty-state">Chargement&hellip;</td></tr></tbody>
      </table>
    </div>
    <div class="pag-bar" id="pag-bar" style="display:none">
      <span id="pag-info"></span>
      <div class="pag-btns" id="pag-btns"></div>
    </div>
  </div>

  <div class="modal-overlay" id="modal-overlay">
    <div class="modal-box" id="modal-box">
      <div class="modal-title">
        <span id="modal-title-text">Session</span>
        <button class="modal-close" id="modal-close">&times;</button>
      </div>
      <div id="modal-body"></div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const h = {'Accept':'application/json','X-CSRF-TOKEN':csrf};
const hj = {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf};

let bulk;
let state = { page:1, sessions:[], meta:{total:0,current_page:1,last_page:1}, salesCache:{} };

function fmt(v){ return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; }
function fmtDate(d){ return d ? new Date(d).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '—'; }
function fmtDateFull(d){ return d ? new Date(d).toLocaleString('fr-FR') : '—'; }
function duree(s){
  const from=new Date(s.opened_at), to=s.closed_at?new Date(s.closed_at):new Date();
  const mins=Math.floor((to-from)/60000), h=Math.floor(mins/60), m=mins%60;
  return h>0?(m>0?h+'h'+m+'min':h+'h'):mins+'min';
}
function badgeHtml(s){
  const fantome = s.status==='open' && !s.last_activity_at;
  if(fantome) return '<span class="badge badge-fantome">Fantôme</span>';
  if(s.status==='open') return '<span class="badge badge-open"><span class="dot-live"></span>Active</span>';
  if(s.status==='closing') return '<span class="badge badge-closing">Clôture</span>';
  return '<span class="badge badge-closed">Fermée</span>';
}
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function filters(){
  return {
    status: document.getElementById('f-status').value,
    operateur: document.getElementById('f-operateur').value,
    debut: document.getElementById('f-debut').value,
    fin: document.getElementById('f-fin').value,
  };
}

async function load(){
  const f = filters();
  const p = new URLSearchParams({page: state.page, per_page:20});
  if(f.status==='fantomes') p.set('fantomes_only','1');
  else if(f.status) p.set('status',f.status);
  if(f.operateur) p.set('operateur_id',f.operateur);
  if(f.debut) p.set('date_debut',f.debut);
  if(f.fin) p.set('date_fin',f.fin);

  document.getElementById('sessions-tbody').innerHTML = '<tr><td colspan="11" class="empty-state">Chargement…</td></tr>';

  try{
    const r = await fetch('/pos-terminal/sessions/data?'+p, {credentials:'same-origin',headers:h});
    const d = await r.json();
    state.sessions = d.data||[];
    state.meta = {total:d.total, current_page:d.current_page, last_page:d.last_page};
    renderTable();
    renderPag();
  }catch(e){ document.getElementById('sessions-tbody').innerHTML='<tr><td colspan="11" class="empty-state">Erreur de chargement</td></tr>'; }
}

async function loadStats(){
  try{
    const [od,fd,ad] = await Promise.all([
      fetch('/pos-terminal/sessions/data?status=open&per_page=100',{credentials:'same-origin',headers:h}).then(r=>r.json()),
      fetch('/pos-terminal/sessions/data?fantomes_only=1&per_page=100',{credentials:'same-origin',headers:h}).then(r=>r.json()),
      fetch('/pos-terminal/sessions/data?per_page=1',{credentials:'same-origin',headers:h}).then(r=>r.json()),
    ]);
    const ventes = (od.data||[]).reduce((s,x)=>s+parseFloat(x.total_ventes||0),0);
    document.getElementById('stat-open').textContent = od.total||0;
    document.getElementById('stat-fantomes').textContent = fd.total||0;
    document.getElementById('stat-ventes').textContent = fmt(ventes);
    document.getElementById('stat-total').textContent = ad.total||0;
  }catch(e){}
}

function renderTable(){
  if(bulk) bulk.clear();
  const tb = document.getElementById('sessions-tbody');
  if(!state.sessions.length){ tb.innerHTML='<tr><td colspan="11" class="empty-state">Aucune session trouvée</td></tr>'; return; }
  tb.innerHTML = state.sessions.map(function(s){ return (
    '<tr>' +
    '<td style="padding-right:.5rem"><input type="checkbox" class="al-row-cb al-cb" data-id="'+s.id+'"></td>' +
    '<td style="color:#888;font-size:.8rem">'+esc(s.id)+'</td>' +
    '<td><div style="font-weight:600">'+esc(s.opener&&s.opener.name||'—')+'</div><div style="font-size:.75rem;color:#888">'+esc(s.opener&&s.opener.email||'')+'</div></td>' +
    '<td style="font-family:monospace;font-size:.78rem;color:#aaa">'+esc(s.machine_name||(s.machine_id?s.machine_id.slice(0,12)+'…':'—'))+'</td>' +
    '<td>'+badgeHtml(s)+'</td>' +
    '<td>'+fmtDate(s.opened_at)+'</td>' +
    '<td>'+duree(s)+'</td>' +
    '<td>'+fmt(s.total_ventes||0)+'</td>' +
    '<td>'+(s.nombre_tickets||0)+'</td>' +
    '<td>'+fmt(s.opening_cash||0)+'</td>' +
    '<td><div style="display:flex;gap:.4rem;flex-wrap:wrap">' +
      '<a href="/pos-terminal/sessions/'+s.id+'/detail" class="btn-action btn-detail" style="text-decoration:none">Détail</a>' +
      ((s.status==='open'||s.status==='closing')?'<button class="btn-action btn-close-session" onclick="POS.close('+s.id+',\''+esc(s.opener&&s.opener.name||'')+'\')">Clôturer</button>':'') +
    '</div></td>' +
    '</tr>'
  ); }).join('');
}

function renderPag(){
  const bar = document.getElementById('pag-bar');
  const t = state.meta;
  if(!t.total){ bar.style.display='none'; return; }
  bar.style.display='flex';
  document.getElementById('pag-info').textContent = t.total+' session(s) — page '+t.current_page+'/'+t.last_page;
  const btns = document.getElementById('pag-btns');
  let html = '<button class="pag-btn" '+(t.current_page<=1?'disabled':'')+' onclick="POS.page('+(t.current_page-1)+')">&#8592;</button>';
  for(let i=Math.max(1,t.current_page-2);i<=Math.min(t.last_page,t.current_page+2);i++){
    html += '<button class="pag-btn '+(i===t.current_page?'active':'')+'" onclick="POS.page('+i+')">'+i+'</button>';
  }
  html += '<button class="pag-btn" '+(t.current_page>=t.last_page?'disabled':'')+' onclick="POS.page('+(t.current_page+1)+')">&#8594;</button>';
  btns.innerHTML = html;
}

async function openDetail(id){
  const s = state.sessions.find(function(x){ return x.id===id; });
  if(!s) return;
  document.getElementById('modal-title-text').textContent = 'Session #'+s.id;
  document.getElementById('modal-body').innerHTML = '<div class="sales-empty">Chargement…</div>';
  document.getElementById('modal-overlay').classList.add('open');

  const closable = s.status==='open'||s.status==='closing';
  let closeBtn = closable
    ? '<button class="btn-action btn-close-session" style="width:100%;padding:.6rem;margin-top:.5rem" onclick="POS.close('+s.id+',\''+esc(s.opener&&s.opener.name||'')+'\');POS.closeModal()">Clôturer cette session (admin)</button>'
    : '';

  document.getElementById('modal-body').innerHTML =
    '<div class="detail-row"><span class="detail-label">Opérateur</span><span class="detail-val">'+esc(s.opener&&s.opener.name||'—')+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Email</span><span class="detail-val">'+esc(s.opener&&s.opener.email||'—')+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Machine ID</span><span class="detail-val" style="font-family:monospace;font-size:.8rem">'+esc(s.machine_id||'—')+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Machine nom</span><span class="detail-val">'+esc(s.machine_name||'—')+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Statut</span><span class="detail-val">'+badgeHtml(s)+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Ouverture</span><span class="detail-val">'+fmtDateFull(s.opened_at)+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Clôture</span><span class="detail-val">'+fmtDateFull(s.closed_at)+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Fond de caisse</span><span class="detail-val">'+fmt(s.opening_cash)+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Ventes session</span><span class="detail-val">'+fmt(s.total_ventes||0)+'</span></div>' +
    '<div class="detail-row"><span class="detail-label">Tickets émis</span><span class="detail-val">'+(s.nombre_tickets||0)+'</span></div>' +
    '<div style="margin-top:1.25rem">' +
      '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">' +
        '<span style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:#888">Ventes</span>' +
        '<a href="/pos-terminal/sessions/'+s.id+'/export-csv" class="btn-export" target="_blank">&#8595; CSV</a>' +
      '</div>' +
      '<div id="sales-container-'+s.id+'"><div class="sales-empty">Chargement ventes…</div></div>' +
    '</div>' +
    closeBtn;

  await loadSales(s.id);
}

async function loadSales(id){
  if(state.salesCache[id]){ renderSales(id, state.salesCache[id]); return; }
  try{
    const r = await fetch('/pos-terminal/sessions/'+id+'/sales',{credentials:'same-origin',headers:h});
    const d = await r.json();
    state.salesCache[id] = d.data||d||[];
    renderSales(id, state.salesCache[id]);
  }catch(e){
    const c=document.getElementById('sales-container-'+id);
    if(c) c.innerHTML='<div class="sales-empty">Erreur chargement ventes</div>';
  }
}

function renderSales(id, sales){
  const c = document.getElementById('sales-container-'+id);
  if(!c) return;
  if(!sales.length){ c.innerHTML='<div class="sales-empty">Aucune vente pour cette session</div>'; return; }
  let rows = sales.map(function(s){
    return '<tr>' +
      '<td style="color:#888">'+esc(s.id)+'</td>' +
      '<td>'+fmtDate(s.created_at)+'</td>' +
      '<td style="font-weight:600;color:#4ade80">'+fmt(s.total_amount||0)+'</td>' +
      '<td style="text-transform:capitalize">'+esc(s.payments&&s.payments[0]&&s.payments[0].method||'—')+'</td>' +
      '<td>'+esc(s.status||'—')+'</td>' +
    '</tr>';
  }).join('');
  c.innerHTML = '<div style="max-height:220px;overflow-y:auto"><table class="sales-table"><thead><tr><th>#</th><th>Date</th><th>Montant</th><th>Paiement</th><th>Statut</th></tr></thead><tbody>'+rows+'</tbody></table></div>';
}

async function forceClose(id, name){
  if(!confirm('Clôturer session #'+id+' ('+name+') ?')) return;
  try{
    const r = await fetch('/pos-terminal/sessions/'+id+'/force-close',{method:'POST',credentials:'same-origin',headers:hj});
    if(r.ok){ AL.toast('Session #'+id+' clôturée ✓'); load(); loadStats(); }
    else AL.toast('Erreur lors de la clôture', false);
  }catch(e){ AL.toast('Erreur réseau', false); }
}

window.POS = {
  detail: openDetail,
  close: forceClose,
  page: function(p){ state.page=p; load(); },
  closeModal: function(){ document.getElementById('modal-overlay').classList.remove('open'); }
};

document.getElementById('btn-refresh').addEventListener('click', function(){ load(); loadStats(); });
document.getElementById('btn-reset').addEventListener('click', function(){
  document.getElementById('f-status').value='';
  document.getElementById('f-operateur').value='';
  document.getElementById('f-debut').value='';
  document.getElementById('f-fin').value='';
  state.page=1; load();
});
document.getElementById('modal-close').addEventListener('click', POS.closeModal);
document.getElementById('modal-overlay').addEventListener('click', function(e){ if(e.target===this) POS.closeModal(); });
['f-status','f-operateur','f-debut','f-fin'].forEach(function(id){
  document.getElementById(id).addEventListener('change', function(){ state.page=1; load(); });
});
document.getElementById('btn-export-global').addEventListener('click', function(e){
  e.preventDefault();
  const f = filters();
  const p = new URLSearchParams();
  if(f.status==='fantomes') p.set('fantomes_only','1');
  else if(f.status) p.set('status',f.status);
  if(f.operateur) p.set('operateur_id',f.operateur);
  if(f.debut) p.set('date_debut',f.debut);
  if(f.fin) p.set('date_fin',f.fin);
  window.open('/pos-terminal/sessions/export-global?'+p, '_blank');
});

bulk = AL.initBulkBar({
  listId: 'sessions',
  tbody: document.getElementById('sessions-tbody'),
  cbAllId: 'sessions-cb-all',
  onSuccess: function(){ load(); loadStats(); }
});

load();
loadStats();
})();
</script>
@endpush
