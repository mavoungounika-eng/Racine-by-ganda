{{--
    Admin List Component — CSS + bulk bar + JS utilities (v2)
    Variables:
      $listId      (string) unique ID for this list instance
      $bulkActions (array)  [{label, endpoint, confirm?, danger?}]
--}}
@php $listId = $listId ?? 'list'; $bulkActions = $bulkActions ?? []; @endphp

@once
@push('styles')
<style nonce="{{ csp_nonce() }}">
/* ── Layout ─────────────────────────────────────────────────────────── */
.al-card{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:16px;padding:1.5rem;overflow:hidden}
.al-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}

/* ── Filters ─────────────────────────────────────────────────────────── */
.al-filter-select,.al-filter-input{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.2);border-radius:8px;color:#e2e8f0;padding:.45rem .85rem;font-size:.85rem;transition:border-color .15s}
.al-filter-select:focus,.al-filter-input:focus{outline:none;border-color:#ED5F1E}
.al-filter-select.al-active,.al-filter-input.al-active{border-color:rgba(237,95,30,.6);box-shadow:0 0 0 2px rgba(237,95,30,.15)}
.al-btn-reset{background:transparent;color:#aaa;border:1px solid rgba(212,165,116,.2);border-radius:8px;padding:.45rem .85rem;font-size:.85rem;cursor:pointer;transition:all .15s;position:relative}
.al-btn-reset:hover{border-color:rgba(212,165,116,.5);color:#eee}
.al-btn-reset .al-filter-badge{position:absolute;top:-6px;right:-6px;background:#ED5F1E;color:#fff;border-radius:999px;width:16px;height:16px;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;display:none}
.al-btn-reset.has-filters .al-filter-badge{display:flex}
.al-per-page{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.2);border-radius:8px;color:#e2e8f0;padding:.45rem .75rem;font-size:.85rem;cursor:pointer}

/* ── Stats ───────────────────────────────────────────────────────────── */
.al-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin-bottom:1.5rem}
.al-stat,.al-stat-item{background:rgba(22,13,12,.6);border:1px solid rgba(212,165,116,.1);border-radius:12px;padding:.9rem 1.1rem;display:flex;flex-direction:column;gap:.25rem}
.al-stat-label,.al-stat-item small{font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:#888}
.al-stat-value,.al-stat-item span{font-size:1.4rem;font-weight:800;color:#e2e8f0;line-height:1}

/* ── Table ───────────────────────────────────────────────────────────── */
.al-table-wrap{overflow-x:auto;position:relative}
.al-table{width:100%;border-collapse:collapse;font-size:.875rem}
.al-table th{background:rgba(22,13,12,.95);color:#aaa;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;padding:.7rem 1rem;text-align:left;border-bottom:1px solid rgba(212,165,116,.1);white-space:nowrap;user-select:none}
.al-table td{padding:.8rem 1rem;border-bottom:1px solid rgba(212,165,116,.06);color:#e2e8f0;vertical-align:middle}
.al-table tr:hover td{background:rgba(237,95,30,.04)}
.al-table tr:hover td.al-sticky{background:rgba(26,14,11,.9)}

/* ── Sticky actions column ───────────────────────────────────────────── */
.al-table th.al-sticky,.al-table td.al-sticky{position:sticky;right:0;z-index:2;box-shadow:-4px 0 10px rgba(0,0,0,.25)}
.al-table th.al-sticky{background:rgba(22,13,12,.98)}
.al-table td.al-sticky{background:rgba(22,13,12,.92)}

/* ── Sort indicators ─────────────────────────────────────────────────── */
.al-sortable{cursor:pointer}
.al-sortable:hover{color:#ED5F1E}
.al-sortable .al-sort-icon{display:inline-block;margin-left:.3em;opacity:.4;font-size:.9em;transition:opacity .1s}
.al-sortable.asc .al-sort-icon,.al-sortable.desc .al-sort-icon{opacity:1;color:#ED5F1E}

/* ── Badges & buttons ────────────────────────────────────────────────── */
.al-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
.al-action-btn{display:inline-flex;align-items:center;border:1px solid rgba(212,165,116,.25);background:rgba(22,13,12,.6);border-radius:6px;padding:4px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;text-decoration:none;color:#e2e8f0;white-space:nowrap}
.al-action-btn:hover{border-color:#ED5F1E;color:#ED5F1E;background:rgba(237,95,30,.08)}
.al-action-btn:disabled{opacity:.4;cursor:not-allowed;pointer-events:none}

/* ── Skeleton loader ─────────────────────────────────────────────────── */
@keyframes al-pulse{0%,100%{opacity:.35}50%{opacity:.1}}
.al-skeleton td{padding:.8rem 1rem;border-bottom:1px solid rgba(212,165,116,.04)}
.al-skel-cell{height:14px;border-radius:6px;background:rgba(212,165,116,.15);animation:al-pulse 1.4s ease-in-out infinite}
.al-skel-cell.w-sm{width:40%}.al-skel-cell.w-md{width:65%}.al-skel-cell.w-lg{width:85%}.al-skel-cell.w-xs{width:20%}

/* ── Checkboxes ──────────────────────────────────────────────────────── */
.al-cb{width:15px;height:15px;accent-color:#ED5F1E;cursor:pointer;vertical-align:middle;flex-shrink:0}

/* ── Pagination ──────────────────────────────────────────────────────── */
.al-pag-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;padding:.75rem 0;font-size:.82rem;color:#888}
.al-pag-btns{display:flex;gap:.4rem;flex-wrap:wrap}
.al-pag-btn{background:rgba(22,13,12,.8);border:1px solid rgba(212,165,116,.15);color:#ccc;border-radius:6px;padding:4px 12px;cursor:pointer;font-size:.8rem;transition:all .12s}
.al-pag-btn:disabled{opacity:.3;cursor:default}
.al-pag-btn:hover:not(:disabled){border-color:#ED5F1E;color:#ED5F1E}
.al-pag-btn.al-current{background:#ED5F1E;color:#fff;border-color:#ED5F1E}

/* ── Floating bulk bar ───────────────────────────────────────────────── */
.al-bulk-bar{position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);z-index:9998;background:#1a1008;border:1px solid rgba(237,95,30,.4);border-radius:14px;padding:.7rem 1.25rem;display:none;align-items:center;gap:.85rem;box-shadow:0 8px 32px rgba(0,0,0,.6);max-width:calc(100vw - 2rem);flex-wrap:wrap;justify-content:center}
.al-bulk-bar.show{display:flex;animation:al-slide-up .2s ease}
@keyframes al-slide-up{from{transform:translateX(-50%) translateY(12px);opacity:0}to{transform:translateX(-50%) translateY(0);opacity:1}}
.al-bulk-count{color:#e2e8f0;font-size:.875rem;font-weight:700;white-space:nowrap;padding:.25rem .5rem;background:rgba(237,95,30,.15);border-radius:8px;border:1px solid rgba(237,95,30,.2)}
.al-bulk-btn{background:rgba(237,95,30,.2);color:#ED5F1E;border:1px solid rgba(237,95,30,.4);border-radius:8px;padding:.4rem .9rem;font-size:.82rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s}
.al-bulk-btn:hover:not(:disabled){background:rgba(237,95,30,.35)}
.al-bulk-btn:disabled{opacity:.4;cursor:not-allowed}
.al-bulk-btn.danger{background:rgba(239,68,68,.2);color:#f87171;border-color:rgba(239,68,68,.4)}
.al-bulk-btn.danger:hover:not(:disabled){background:rgba(239,68,68,.35)}
.al-bulk-cancel{background:transparent;color:#888;border:1px solid rgba(212,165,116,.2);border-radius:8px;padding:.4rem .9rem;font-size:.82rem;cursor:pointer;transition:all .15s}
.al-bulk-cancel:hover{color:#ccc;border-color:rgba(212,165,116,.4)}

/* ── Empty state ─────────────────────────────────────────────────────── */
.al-empty{text-align:center;padding:3rem 1rem;color:#666}
.al-empty-icon{font-size:2.5rem;margin-bottom:.5rem;opacity:.4}

/* ── ERP index utilities (replace inline style= everywhere) ─────────── */
.al-title{color:#ED5F1E}
.al-action-btn-primary{background:#ED5F1E!important;color:#fff!important;border-color:#ED5F1E!important}
.al-action-btn-sm{font-size:.75rem}
.al-filter-input-wide{min-width:180px}
.al-th-cb{width:36px}
.al-stat-ok{color:#4ade80}
.al-stat-warn{color:#fbbf24}
.al-stat-danger{color:#f87171}
.al-stat-muted{color:#94a3b8}
.al-stat-orange{color:#ED5F1E}
.al-row-name{font-weight:600;color:#e2e8f0}
.al-row-muted{color:#aaa;font-size:.82rem}
.al-row-price{color:#ED5F1E;font-weight:700}
.al-row-sub{font-size:.72rem;color:#555}
.al-code{color:#ED5F1E;font-size:.82rem;font-weight:700}
.al-icon-dim{opacity:.5}
.al-table td.al-sticky{white-space:nowrap}
.al-refresh-hint{font-size:.72rem;color:#555;text-align:right;margin-top:.5rem}
.al-badge-active  {background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3)}
.al-badge-inactive{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25)}
.al-badge-ok      {background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3)}
.al-badge-warn    {background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.al-badge-danger  {background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.al-badge-ordered  {background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.al-badge-received {background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3)}
.al-badge-partial  {background:rgba(139,92,246,.15);color:#a78bfa;border:1px solid rgba(139,92,246,.3)}
.al-badge-cancelled{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
window.AL = window.AL || {};

/* ─── Toast ────────────────────────────────────────────────────────── */
AL.toast = function(msg, ok, status) {
  let text = msg;
  if (status === 403) text = '⛔ ' + msg + ' (non autorisé)';
  else if (status === 422) text = '⚠ ' + msg + ' (données invalides)';
  else if (status >= 500) text = '🔥 ' + msg + ' (erreur serveur)';
  const t = document.createElement('div');
  t.textContent = text;
  t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:10000;padding:.8rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:600;color:#fff;max-width:360px;word-break:break-word;background:'+(ok!==false?'rgba(34,197,94,.92)':'rgba(239,68,68,.92)')+';box-shadow:0 4px 24px rgba(0,0,0,.5);transition:opacity .4s ease';
  document.body.appendChild(t);
  setTimeout(function(){ t.style.opacity='0'; setTimeout(function(){ t.remove(); },450); }, ok===false?4500:2800);
};

/* ─── Skeleton loader ────────────────────────────────────────────────── */
// AL.skeleton(tbody, colCount, rowCount=5)
AL.skeleton = function(tbody, cols, rows) {
  rows = rows || 5;
  const widths = ['w-lg','w-md','w-sm','w-xs','w-md'];
  let html = '';
  for (let r = 0; r < rows; r++) {
    html += '<tr class="al-skeleton">';
    for (let c = 0; c < cols; c++) {
      html += '<td><div class="al-skel-cell ' + widths[(c + r) % widths.length] + '"></div></td>';
    }
    html += '</tr>';
  }
  tbody.innerHTML = html;
};

/* ─── Standard pagination ───────────────────────────────────────────── */
// AL.buildPager(containerId, data, loadFn)
// data: Laravel paginator response {current_page, last_page, total, per_page}
AL.buildPager = function(containerId, data, loadFn) {
  const pag = document.getElementById(containerId);
  if (!pag) return;
  pag.innerHTML = '';
  if (data.last_page <= 1) return;

  const cp = data.current_page, lp = data.last_page;
  const info = document.createElement('span');
  info.textContent = 'Page ' + cp + ' / ' + lp + '  (' + data.total + ' entrée' + (data.total !== 1 ? 's' : '') + ')';
  info.style.cssText = 'color:#888;font-size:.82rem;flex-shrink:0';

  const btns = document.createElement('div');
  btns.className = 'al-pag-btns';

  function makeBtn(label, page, current) {
    const b = document.createElement('button');
    b.textContent = label;
    b.className = 'al-pag-btn' + (current ? ' al-current' : '');
    b.disabled = page === null;
    if (page !== null && !current) b.onclick = function() { loadFn(page); };
    return b;
  }

  btns.appendChild(makeBtn('← Préc.', cp > 1 ? cp - 1 : null, false));

  // Window of pages
  let start = Math.max(1, cp - 2), end = Math.min(lp, cp + 2);
  if (start > 1) { btns.appendChild(makeBtn('1', 1, false)); if (start > 2) { const e = document.createElement('span'); e.textContent='…'; e.style.cssText='color:#666;padding:0 .25rem;align-self:center'; btns.appendChild(e); } }
  for (let p = start; p <= end; p++) btns.appendChild(makeBtn(p, p, p === cp));
  if (end < lp) { if (end < lp - 1) { const e = document.createElement('span'); e.textContent='…'; e.style.cssText='color:#666;padding:0 .25rem;align-self:center'; btns.appendChild(e); } btns.appendChild(makeBtn(lp, lp, false)); }

  btns.appendChild(makeBtn('Suiv. →', cp < lp ? cp + 1 : null, false));

  pag.appendChild(info);
  pag.appendChild(btns);
};

/* ─── URL state sync ────────────────────────────────────────────────── */
// AL.syncUrl({key: value, ...}) — replaceState with active filters
AL.syncUrl = function(stateObj) {
  try {
    const url = new URL(window.location.href);
    Object.keys(stateObj).forEach(function(k) {
      const v = stateObj[k];
      if (v !== null && v !== undefined && v !== '' && v !== 1 && v !== '1') {
        url.searchParams.set(k, v);
      } else if (k === 'page' && v > 1) {
        url.searchParams.set(k, v);
      } else {
        url.searchParams.delete(k);
      }
    });
    history.replaceState(null, '', url.toString());
  } catch(e) {}
};

// AL.readUrl(defaults) — read query params, merge with defaults
AL.readUrl = function(defaults) {
  try {
    const p = new URLSearchParams(window.location.search);
    const out = Object.assign({}, defaults);
    Object.keys(defaults).forEach(function(k) {
      if (p.has(k)) out[k] = p.get(k);
    });
    return out;
  } catch(e) { return Object.assign({}, defaults); }
};

/* ─── Active filter badge on reset button ────────────────────────────── */
// AL.updateResetBtn(btnId, activeCount)
AL.updateResetBtn = function(btnId, count) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  if (count > 0) {
    btn.classList.add('has-filters');
    const badge = btn.querySelector('.al-filter-badge');
    if (badge) badge.textContent = count > 9 ? '9+' : count;
  } else {
    btn.classList.remove('has-filters');
  }
};

/* ─── Sort header wiring ─────────────────────────────────────────────── */
// AL.makeSortable(th, colKey, sortState, onSort)
// sortState: {by: '', dir: 'desc'} — mutated in place
// onSort: function(by, dir) called after state updated
AL.makeSortable = function(th, colKey, sortState, onSort) {
  th.classList.add('al-sortable');
  const icon = document.createElement('span');
  icon.className = 'al-sort-icon';
  icon.textContent = '↕';
  th.appendChild(icon);

  th.addEventListener('click', function() {
    // Clear sibling sort classes
    const table = th.closest('table');
    if (table) table.querySelectorAll('th.al-sortable').forEach(function(t) {
      if (t !== th) { t.classList.remove('asc','desc'); const i = t.querySelector('.al-sort-icon'); if(i) i.textContent='↕'; }
    });
    if (sortState.by === colKey) {
      sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
    } else {
      sortState.by = colKey;
      sortState.dir = 'desc';
    }
    th.classList.toggle('asc', sortState.dir === 'asc');
    th.classList.toggle('desc', sortState.dir === 'desc');
    icon.textContent = sortState.dir === 'asc' ? '↑' : '↓';
    onSort(sortState.by, sortState.dir);
  });
};

/* ─── CSV export helper ──────────────────────────────────────────────── */
// AL.exportCsv(exportUrl, params) — navigate to URL with active filters
AL.exportCsv = function(exportUrl, params) {
  const url = new URL(exportUrl, window.location.origin);
  if (params) Object.keys(params).forEach(function(k) {
    if (params[k] !== null && params[k] !== undefined && params[k] !== '') {
      url.searchParams.set(k, params[k]);
    }
  });
  window.location.href = url.toString();
};

/* ─── Bulk bar ───────────────────────────────────────────────────────── */
// cfg: { listId, tbody, cbAllId?, onSuccess? }
AL.initBulkBar = function(cfg) {
  const self = {};
  const barEl = document.getElementById('al-'+cfg.listId+'-bulk-bar');
  const countEl = document.getElementById('al-'+cfg.listId+'-bulk-count');
  const selected = new Set();

  function sync() {
    const n = selected.size;
    if (barEl) { if (n > 0) barEl.classList.add('show'); else barEl.classList.remove('show'); }
    if (countEl) countEl.textContent = n + (n > 1 ? ' sélectionnés' : ' sélectionné');
    const allCb = cfg.cbAllId ? document.getElementById(cfg.cbAllId) : null;
    if (allCb) {
      const total = cfg.tbody ? cfg.tbody.querySelectorAll('.al-row-cb').length : 0;
      allCb.indeterminate = n > 0 && n < total;
      allCb.checked = total > 0 && n === total;
    }
  }

  if (cfg.tbody) cfg.tbody.addEventListener('change', function(e) {
    if (!e.target.classList.contains('al-row-cb')) return;
    const id = parseInt(e.target.dataset.id, 10);
    if (e.target.checked) selected.add(id); else selected.delete(id);
    sync();
  });

  if (cfg.cbAllId) {
    const allCb = document.getElementById(cfg.cbAllId);
    if (allCb) allCb.addEventListener('change', function() {
      if (!cfg.tbody) return;
      cfg.tbody.querySelectorAll('.al-row-cb').forEach(function(cb) {
        cb.checked = allCb.checked;
        const id = parseInt(cb.dataset.id, 10);
        if (allCb.checked) selected.add(id); else selected.delete(id);
      });
      sync();
    });
  }

  // Cancel button
  const cancelEl = document.getElementById('al-'+cfg.listId+'-bulk-cancel');
  if (cancelEl) cancelEl.addEventListener('click', function() { self.clear(); });

  // Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && selected.size > 0) self.clear();
  });

  // Action buttons — with rate limiting + status-aware errors
  if (barEl) barEl.querySelectorAll('.al-bulk-btn[data-endpoint]').forEach(function(btn) {
    btn.addEventListener('click', async function() {
      const ids = self.getIds();
      if (!ids.length) return;
      const n = ids.length;
      const rawConfirm = this.dataset.confirm || 'Action sur {n} élément(s) ?';
      const msg = rawConfirm.replace('{n}', n);
      if (!confirm(msg)) return;

      // Rate limit: disable all bulk buttons during request
      const allBtns = barEl.querySelectorAll('.al-bulk-btn');
      allBtns.forEach(function(b) { b.disabled = true; });

      const csrf = document.querySelector('meta[name="csrf-token"]');
      try {
        const r = await fetch(this.dataset.endpoint, {
          method: 'POST', credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf ? csrf.content : ''
          },
          body: JSON.stringify({ ids })
        });
        const d = await r.json().catch(function() { return {}; });
        if (r.ok) {
          AL.toast(d.message || 'Action effectuée ✓', true);
          self.clear();
          if (cfg.onSuccess) cfg.onSuccess();
        } else {
          AL.toast(d.message || 'Erreur ' + r.status, false, r.status);
        }
      } catch(e) {
        AL.toast('Erreur réseau — vérifier la connexion', false);
      } finally {
        allBtns.forEach(function(b) { b.disabled = false; });
      }
    });
  });

  self.selected = selected;
  self.getIds = function() { return [...selected]; };
  self.refresh = sync;
  self.clear = function() {
    selected.clear();
    if (cfg.tbody) cfg.tbody.querySelectorAll('.al-row-cb').forEach(function(cb) { cb.checked = false; });
    const allCb = cfg.cbAllId ? document.getElementById(cfg.cbAllId) : null;
    if (allCb) { allCb.checked = false; allCb.indeterminate = false; }
    sync();
  };
  return self;
};

})();
</script>
@endpush
@endonce

{{-- Floating bulk bar --}}
@if(count($bulkActions) > 0)
<div id="al-{{ $listId }}-bulk-bar" class="al-bulk-bar" role="toolbar" aria-label="Actions groupées">
  <span class="al-bulk-count" id="al-{{ $listId }}-bulk-count" aria-live="polite">0 sélectionné</span>
  @foreach($bulkActions as $action)
  <button class="al-bulk-btn {{ ($action['danger'] ?? false) ? 'danger' : '' }}"
          data-endpoint="{{ $action['endpoint'] }}"
          data-confirm="{{ $action['confirm'] ?? '' }}"
          aria-label="{{ $action['label'] }}">
    {{ $action['label'] }}
  </button>
  @endforeach
  <button class="al-bulk-cancel" id="al-{{ $listId }}-bulk-cancel" aria-label="Annuler la sélection">Annuler (Esc)</button>
</div>
@endif
