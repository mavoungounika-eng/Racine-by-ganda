{{--
    Admin List Component — CSS + bulk bar + JS utilities
    Variables:
      $listId      (string) unique ID for this list instance
      $bulkActions (array)  [{label, endpoint, confirm?, danger?}]
--}}
@php $listId = $listId ?? 'list'; $bulkActions = $bulkActions ?? []; @endphp

@once
@push('styles')
<style nonce="{{ csp_nonce() }}">
.al-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.al-filters{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}
.al-filter-select,.al-filter-input{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.2);border-radius:8px;color:#e2e8f0;padding:.45rem .85rem;font-size:.85rem}
.al-filter-select:focus,.al-filter-input:focus{outline:none;border-color:#ED5F1E}
.al-btn-reset{background:transparent;color:#aaa;border:1px solid rgba(212,165,116,.2);border-radius:8px;padding:.45rem .85rem;font-size:.85rem;cursor:pointer}
.al-table-wrap{overflow-x:auto}
.al-table{width:100%;border-collapse:collapse;font-size:.875rem}
.al-table th{background:rgba(22,13,12,.9);color:#aaa;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;padding:.7rem 1rem;text-align:left;border-bottom:1px solid rgba(212,165,116,.1);white-space:nowrap}
.al-table td{padding:.8rem 1rem;border-bottom:1px solid rgba(212,165,116,.06);color:#e2e8f0;vertical-align:middle}
.al-table tr:hover td{background:rgba(237,95,30,.04)}
.al-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
.al-empty{text-align:center;padding:3rem;color:#666}
.al-pag-bar{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;font-size:.82rem;color:#888}
.al-pag-btns{display:flex;gap:.5rem}
.al-pag-btn{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.15);color:#ccc;border-radius:6px;padding:4px 12px;cursor:pointer;font-size:.8rem}
.al-pag-btn:disabled{opacity:.3;cursor:default}
.al-pag-btn.active{background:#ED5F1E;color:#fff;border-color:#ED5F1E}
.al-action-btn{border:none;border-radius:6px;padding:4px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:opacity .15s;text-decoration:none}
.al-action-btn:hover{opacity:.8}
.al-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem}
.al-stat{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:12px;padding:1rem 1.25rem}
.al-stat-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:#888;margin-bottom:.3rem}
.al-stat-value{font-size:1.5rem;font-weight:800;color:#e2e8f0}
.al-card{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:16px;overflow:hidden}
/* Checkboxes */
.al-cb{width:15px;height:15px;accent-color:#ED5F1E;cursor:pointer;vertical-align:middle;flex-shrink:0}
/* Floating bulk bar */
.al-bulk-bar{position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);z-index:9998;background:#1a1008;border:1px solid rgba(237,95,30,.4);border-radius:12px;padding:.65rem 1.25rem;display:none;align-items:center;gap:.85rem;box-shadow:0 8px 32px rgba(0,0,0,.6)}
.al-bulk-bar.show{display:flex;animation:al-slide-up .2s ease}
@keyframes al-slide-up{from{transform:translateX(-50%) translateY(12px);opacity:0}to{transform:translateX(-50%) translateY(0);opacity:1}}
.al-bulk-count{color:#e2e8f0;font-size:.875rem;font-weight:600;white-space:nowrap}
.al-bulk-btn{background:rgba(237,95,30,.2);color:#ED5F1E;border:1px solid rgba(237,95,30,.4);border-radius:8px;padding:.4rem .9rem;font-size:.82rem;font-weight:600;cursor:pointer;white-space:nowrap}
.al-bulk-btn:hover{background:rgba(237,95,30,.35)}
.al-bulk-btn.danger{background:rgba(239,68,68,.2);color:#f87171;border-color:rgba(239,68,68,.4)}
.al-bulk-btn.danger:hover{background:rgba(239,68,68,.35)}
.al-bulk-cancel{background:transparent;color:#888;border:1px solid rgba(212,165,116,.2);border-radius:8px;padding:.4rem .9rem;font-size:.82rem;cursor:pointer}
</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
window.AL = window.AL || {};

AL.toast = function(msg, ok) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:600;color:#fff;background:'+(ok!==false?'rgba(34,197,94,.9)':'rgba(239,68,68,.9)')+';box-shadow:0 4px 20px rgba(0,0,0,.4);transition:opacity .4s';
  document.body.appendChild(t);
  setTimeout(function(){ t.style.opacity='0'; setTimeout(function(){ t.remove(); },400); }, 2800);
};

// cfg: { listId, tbody, cbAllId? (optional), onSuccess? (optional) }
AL.initBulkBar = function(cfg) {
  const self = {};
  const barEl = document.getElementById('al-'+cfg.listId+'-bulk-bar');
  const countEl = document.getElementById('al-'+cfg.listId+'-bulk-count');
  const selected = new Set();

  function sync() {
    const n = selected.size;
    if(barEl) { if(n>0) barEl.classList.add('show'); else barEl.classList.remove('show'); }
    if(countEl) countEl.textContent = n + (n>1?' sélectionné(s)':' sélectionné');
    const allCb = cfg.cbAllId ? document.getElementById(cfg.cbAllId) : null;
    if(allCb) {
      const total = cfg.tbody.querySelectorAll('.al-row-cb').length;
      allCb.indeterminate = n>0 && n<total;
      allCb.checked = total>0 && n===total;
    }
  }

  cfg.tbody.addEventListener('change', function(e) {
    if(!e.target.classList.contains('al-row-cb')) return;
    const id = parseInt(e.target.dataset.id);
    if(e.target.checked) selected.add(id); else selected.delete(id);
    sync();
  });

  if(cfg.cbAllId) {
    const allCb = document.getElementById(cfg.cbAllId);
    if(allCb) allCb.addEventListener('change', function() {
      cfg.tbody.querySelectorAll('.al-row-cb').forEach(function(cb) {
        cb.checked = allCb.checked;
        const id = parseInt(cb.dataset.id);
        if(allCb.checked) selected.add(id); else selected.delete(id);
      });
      sync();
    });
  }

  // Wire cancel button
  const cancelEl = document.getElementById('al-'+cfg.listId+'-bulk-cancel');
  if(cancelEl) cancelEl.addEventListener('click', function() { self.clear(); });

  // Wire action buttons
  if(barEl) barEl.querySelectorAll('.al-bulk-btn[data-endpoint]').forEach(function(btn) {
    btn.addEventListener('click', async function() {
      const ids = self.getIds();
      if(!ids.length) return;
      const n = ids.length;
      const msg = (this.dataset.confirm||'Action sur {n} élément(s) ?').replace('{n}', n);
      if(!confirm(msg)) return;
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      try {
        const r = await fetch(this.dataset.endpoint, {
          method:'POST', credentials:'same-origin',
          headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
          body:JSON.stringify({ids})
        });
        const d = await r.json();
        if(r.ok) {
          AL.toast(d.message || 'Action effectuée ✓');
          self.clear();
          if(cfg.onSuccess) cfg.onSuccess();
        } else {
          AL.toast(d.message || 'Erreur', false);
        }
      } catch(e) { AL.toast('Erreur réseau', false); }
    });
  });

  self.selected = selected;
  self.getIds = function() { return [...selected]; };
  self.refresh = sync;
  self.clear = function() {
    selected.clear();
    cfg.tbody.querySelectorAll('.al-row-cb').forEach(function(cb){ cb.checked=false; });
    const allCb = cfg.cbAllId ? document.getElementById(cfg.cbAllId) : null;
    if(allCb) { allCb.checked=false; allCb.indeterminate=false; }
    sync();
  };
  return self;
};
</script>
@endpush
@endonce

{{-- Floating bulk bar --}}
<div id="al-{{ $listId }}-bulk-bar" class="al-bulk-bar">
  <span class="al-bulk-count" id="al-{{ $listId }}-bulk-count">0 sélectionné</span>
  @foreach($bulkActions as $action)
  <button class="al-bulk-btn {{ ($action['danger'] ?? false) ? 'danger' : '' }}"
          data-endpoint="{{ $action['endpoint'] }}"
          data-confirm="{{ $action['confirm'] ?? '' }}">
    {{ $action['label'] }}
  </button>
  @endforeach
  <button class="al-bulk-cancel" id="al-{{ $listId }}-bulk-cancel">Annuler</button>
</div>
