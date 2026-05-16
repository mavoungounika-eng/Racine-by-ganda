@extends('layouts.admin')
@section('title', 'Produits — Admin')
@section('page-title', 'Gestion des Produits')

@push('styles')
<style nonce="{{ csp_nonce() }}">
.prod-img{width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid rgba(212,165,116,.15)}
.prod-img-ph{width:48px;height:48px;border-radius:8px;border:1px solid rgba(212,165,116,.1);background:rgba(22,13,12,.8);display:flex;align-items:center;justify-content:center;color:#555;font-size:.85rem}
.badge-active{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.badge-inactive{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25)}
.badge-stock-ok{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.badge-stock-low{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.badge-stock-zero{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.btn-edit{background:rgba(237,95,30,.15);color:#ED5F1E;border:1px solid rgba(237,95,30,.3)}
.btn-toggle{background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.btn-del{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.prod-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.btn-new{background:#ED5F1E;color:#fff;border:none;border-radius:8px;padding:.55rem 1.1rem;font-size:.875rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
.btn-new:hover{background:#d44f10;color:#fff}
</style>
@endpush

@section('content')

@include('admin.components.admin-list', [
    'listId' => 'products',
    'bulkActions' => [
        [
            'label'    => 'Désactiver',
            'endpoint' => '/admin/products/bulk-disable',
            'confirm'  => 'Désactiver {n} produit(s) ?',
        ],
        [
            'label'    => 'Supprimer',
            'endpoint' => '/admin/products/bulk-delete',
            'confirm'  => 'Supprimer définitivement {n} produit(s) ? Cette action est irréversible.',
            'danger'   => true,
        ],
    ],
])

<div id="prod-app">

  <div class="al-stats">
    <div class="al-stat"><div class="al-stat-label">Total produits</div><div class="al-stat-value" id="s-total">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Actifs</div><div class="al-stat-value" style="color:#4ade80" id="s-actifs">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Inactifs</div><div class="al-stat-value" style="color:#94a3b8" id="s-inactifs">—</div></div>
    <div class="al-stat"><div class="al-stat-label">Stock critique</div><div class="al-stat-value" style="color:#f87171" id="s-critique">—</div></div>
  </div>

  <div class="prod-header">
    <div style="display:flex;gap:.5rem;align-items:center">
      <h2 style="margin:0;font-size:1.1rem;color:#e2e8f0">Produits</h2>
      <button id="btn-refresh" style="background:none;border:none;color:#888;cursor:pointer;font-size:1.1rem" title="Rafraîchir">&#8635;</button>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
      <input type="text" class="al-filter-input" id="f-search" placeholder="Nom, SKU, code-barres…" style="width:200px">
      <select class="al-filter-select" id="f-category">
        <option value="">Toutes catégories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
      </select>
      <select class="al-filter-select" id="f-status">
        <option value="">Tous statuts</option>
        <option value="1">Actifs</option>
        <option value="0">Inactifs</option>
      </select>
      <button class="al-btn-reset" id="btn-reset">Réinitialiser</button>
      <a href="{{ route('admin.products.create') }}" class="btn-new">+ Nouveau produit</a>
    </div>
  </div>

  <div class="al-card">
    <div class="al-table-wrap">
      <table class="al-table">
        <thead><tr>
          <th style="width:36px;padding-right:.5rem"><input type="checkbox" id="products-cb-all" class="al-cb"></th>
          <th style="width:60px">Image</th>
          <th>Nom</th>
          <th>SKU / Code-barres</th>
          <th>Catégorie</th>
          <th>Prix</th>
          <th>Stock</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr></thead>
        <tbody id="products-tbody"><tr><td colspan="9" class="al-empty">Chargement…</td></tr></tbody>
      </table>
    </div>
    <div class="al-pag-bar" id="prod-pag-bar" style="display:none">
      <span id="prod-pag-info"></span>
      <div class="al-pag-btns" id="prod-pag-btns"></div>
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
let state = { page:1, products:[], meta:{total:0,current_page:1,last_page:1} };

function fmt(v){ return new Intl.NumberFormat('fr-FR').format(Math.round(v||0))+' FCFA'; }
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function filters(){
  return {
    search:   document.getElementById('f-search').value.trim(),
    category: document.getElementById('f-category').value,
    status:   document.getElementById('f-status').value,
  };
}

async function load(){
  const f = filters();
  const p = new URLSearchParams({page:state.page, per_page:20});
  if(f.search)   p.set('search', f.search);
  if(f.category) p.set('category_id', f.category);
  if(f.status!=='') p.set('is_active', f.status);

  document.getElementById('products-tbody').innerHTML = '<tr><td colspan="9" class="al-empty">Chargement…</td></tr>';

  try{
    const r = await fetch('/admin/products/data?'+p, {credentials:'same-origin',headers:h});
    const d = await r.json();
    state.products = d.data||[];
    state.meta = {total:d.total, current_page:d.current_page, last_page:d.last_page};
    renderTable();
    renderPag();
  }catch(e){
    document.getElementById('products-tbody').innerHTML='<tr><td colspan="9" class="al-empty">Erreur de chargement</td></tr>';
  }
}

async function loadStats(){
  try{
    const [all, actifs, critique] = await Promise.all([
      fetch('/admin/products/data?per_page=1',{credentials:'same-origin',headers:h}).then(r=>r.json()),
      fetch('/admin/products/data?per_page=1&is_active=1',{credentials:'same-origin',headers:h}).then(r=>r.json()),
      fetch('/admin/products/data?per_page=100&is_active=1',{credentials:'same-origin',headers:h}).then(r=>r.json()),
    ]);
    const total   = all.total||0;
    const nbActif = actifs.total||0;
    const nbCrit  = (critique.data||[]).filter(function(p){ return (p.stock||0)<=0; }).length;
    document.getElementById('s-total').textContent   = total;
    document.getElementById('s-actifs').textContent  = nbActif;
    document.getElementById('s-inactifs').textContent= Math.max(0, total - nbActif);
    document.getElementById('s-critique').textContent= nbCrit;
  }catch(e){}
}

function stockBadge(stock){
  if(stock>10) return '<span class="al-badge badge-stock-ok">'+stock+'</span>';
  if(stock>0)  return '<span class="al-badge badge-stock-low">'+stock+'</span>';
  return '<span class="al-badge badge-stock-zero">0</span>';
}

function renderTable(){
  if(bulk) bulk.clear();
  const tb = document.getElementById('products-tbody');
  if(!state.products.length){
    tb.innerHTML='<tr><td colspan="9" class="al-empty">Aucun produit trouvé</td></tr>';
    return;
  }
  tb.innerHTML = state.products.map(function(p){
    const img = p.main_image
      ? '<img src="/storage/'+esc(p.main_image)+'" class="prod-img" alt="">'
      : '<div class="prod-img-ph">?</div>';
    const sku = p.erp_details&&p.erp_details.sku ? '<code style="color:#ED5F1E;font-size:.8rem">'+esc(p.erp_details.sku)+'</code>' : '<span style="color:#555">—</span>';
    const bc  = p.erp_details&&p.erp_details.barcode ? '<div style="font-size:.75rem;color:#888;margin-top:.15rem"><code>'+esc(p.erp_details.barcode)+'</code></div>' : '';
    const cat = p.category ? '<span style="color:#aaa;font-size:.82rem">'+esc(p.category.name)+'</span>' : '<span style="color:#555">—</span>';
    const statBadge = p.is_active
      ? '<span class="al-badge badge-active">Actif</span>'
      : '<span class="al-badge badge-inactive">Inactif</span>';
    const toggleLabel = p.is_active ? 'Désactiver' : 'Activer';
    return (
      '<tr>' +
      '<td style="padding-right:.5rem"><input type="checkbox" class="al-row-cb al-cb" data-id="'+p.id+'"></td>' +
      '<td>'+img+'</td>' +
      '<td>' +
        '<div style="font-weight:600;color:#e2e8f0">'+esc(p.title)+'</div>' +
        (p.description ? '<div style="font-size:.75rem;color:#888;margin-top:.1rem">'+esc(String(p.description).slice(0,50))+'…</div>' : '') +
      '</td>' +
      '<td>'+sku+bc+'</td>' +
      '<td>'+cat+'</td>' +
      '<td style="font-weight:600;color:#ED5F1E">'+fmt(p.price)+'</td>' +
      '<td>'+stockBadge(p.stock||0)+'</td>' +
      '<td>'+statBadge+'</td>' +
      '<td>' +
        '<div style="display:flex;gap:.35rem;flex-wrap:wrap">' +
          '<a href="/admin/products/'+p.id+'/edit" class="al-action-btn btn-edit" style="text-decoration:none">Modifier</a>' +
          '<button class="al-action-btn btn-toggle" onclick="PROD.toggle('+p.id+','+p.is_active+')">'+toggleLabel+'</button>' +
          '<button class="al-action-btn btn-del" onclick="PROD.del('+p.id+',\''+esc(p.title)+'\')">Supprimer</button>' +
        '</div>' +
      '</td>' +
      '</tr>'
    );
  }).join('');
}

function renderPag(){
  const bar = document.getElementById('prod-pag-bar');
  const t = state.meta;
  if(!t.total){ bar.style.display='none'; return; }
  bar.style.display='flex';
  document.getElementById('prod-pag-info').textContent = t.total+' produit(s) — page '+t.current_page+'/'+t.last_page;
  const btns = document.getElementById('prod-pag-btns');
  let html = '<button class="al-pag-btn" '+(t.current_page<=1?'disabled':'')+' onclick="PROD.page('+(t.current_page-1)+')">&#8592;</button>';
  for(let i=Math.max(1,t.current_page-2);i<=Math.min(t.last_page,t.current_page+2);i++){
    html += '<button class="al-pag-btn'+(i===t.current_page?' active':'')+'" onclick="PROD.page('+i+')">'+i+'</button>';
  }
  html += '<button class="al-pag-btn" '+(t.current_page>=t.last_page?'disabled':'')+' onclick="PROD.page('+(t.current_page+1)+')">&#8594;</button>';
  btns.innerHTML = html;
}

async function toggleProduct(id, currentActive){
  try{
    const r = await fetch('/admin/products/'+id, {
      method:'PATCH', credentials:'same-origin', headers:hj,
      body:JSON.stringify({is_active: !currentActive})
    });
    if(r.ok){ AL.toast(currentActive?'Produit désactivé':'Produit activé'); load(); loadStats(); }
    else AL.toast('Erreur lors de la mise à jour', false);
  }catch(e){ AL.toast('Erreur réseau', false); }
}

async function deleteProduct(id, title){
  if(!confirm('Supprimer "'+title+'" ? Cette action est irréversible.')) return;
  try{
    const r = await fetch('/admin/products/'+id, {
      method:'DELETE', credentials:'same-origin', headers:hj
    });
    if(r.ok){ AL.toast('Produit supprimé'); load(); loadStats(); }
    else AL.toast('Erreur lors de la suppression', false);
  }catch(e){ AL.toast('Erreur réseau', false); }
}

window.PROD = {
  page:   function(p){ state.page=p; load(); },
  toggle: toggleProduct,
  del:    deleteProduct,
};

let searchTimer;
document.getElementById('f-search').addEventListener('input', function(){
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function(){ state.page=1; load(); }, 350);
});
['f-category','f-status'].forEach(function(id){
  document.getElementById(id).addEventListener('change', function(){ state.page=1; load(); });
});
document.getElementById('btn-refresh').addEventListener('click', function(){ load(); loadStats(); });
document.getElementById('btn-reset').addEventListener('click', function(){
  document.getElementById('f-search').value='';
  document.getElementById('f-category').value='';
  document.getElementById('f-status').value='';
  state.page=1; load();
});

bulk = AL.initBulkBar({
  listId:    'products',
  tbody:     document.getElementById('products-tbody'),
  cbAllId:   'products-cb-all',
  onSuccess: function(){ load(); loadStats(); }
});

load();
loadStats();
})();
</script>
@endpush
