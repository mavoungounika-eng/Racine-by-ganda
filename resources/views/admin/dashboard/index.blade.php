@extends('layouts.admin')

@section('title', 'Dashboard — RACINE BY GANDA')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Vue d\'ensemble de l\'activité')

@section('content')
<div class="db-root">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') || isset($error))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') ?? $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── HEADER ──────────────────────────────────────────────────────── --}}
    <div class="db-header">
        <div class="db-period-tabs" role="tablist" aria-label="Période">
            <button class="db-period-btn active" data-period="today" role="tab">Aujourd'hui</button>
            <button class="db-period-btn" data-period="week" role="tab">7 jours</button>
            <button class="db-period-btn" data-period="month" role="tab">30 jours</button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">
                <i class="fas fa-clock me-1"></i>{{ $last_updated ?? now()->format('H:i') }}
            </span>
            <form action="{{ route('admin.dashboard.refresh') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="db-refresh-btn" title="Rafraîchir le cache">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────────── --}}
    <div class="db-kpi-grid">
        <div class="db-kpi-card" id="kpi-revenue"><div class="db-kpi-skeleton"></div></div>
        <div class="db-kpi-card" id="kpi-orders"><div class="db-kpi-skeleton"></div></div>
        <div class="db-kpi-card" id="kpi-clients"><div class="db-kpi-skeleton"></div></div>
        <div class="db-kpi-card" id="kpi-pending"><div class="db-kpi-skeleton"></div></div>
    </div>

    {{-- ── CHART + SIDEBAR ─────────────────────────────────────────────── --}}
    <div class="db-mid-grid">

        <div class="db-card">
            <div class="db-card-header">
                <span class="db-card-title">
                    <i class="fas fa-chart-line me-2 db-orange"></i>Chiffre d'affaires — 30 jours
                </span>
            </div>
            <div class="db-chart-wrap">
                <div class="db-chart-skeleton" id="chart-skeleton"></div>
                <canvas id="revenue-chart" style="display:none"></canvas>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-header">
                <span class="db-card-title"><i class="fas fa-bell me-2 db-orange"></i>Alertes</span>
            </div>
            @php
                $al = $alerts ?? [];
                $lateOrders     = data_get($al, 'late_orders', 0);
                $criticalStock  = data_get($al, 'critical_stock', 0);
                $failedPayments = data_get($al, 'failed_payments', 0);
            @endphp
            @if($lateOrders || $criticalStock || $failedPayments)
                <div class="db-alerts-list">
                    @if($lateOrders > 0)
                    <a href="{{ route('admin.orders.index', ['status' => 'late']) }}" class="db-alert-item db-alert-warn">
                        <i class="fas fa-clock"></i>
                        <span>{{ $lateOrders }} commande{{ $lateOrders > 1 ? 's' : '' }} en retard</span>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>
                    @endif
                    @if($criticalStock > 0)
                    <a href="{{ route('erp.materials.index') }}" class="db-alert-item db-alert-danger">
                        <i class="fas fa-box-open"></i>
                        <span>{{ $criticalStock }} matière{{ $criticalStock > 1 ? 's' : '' }} en rupture</span>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>
                    @endif
                    @if($failedPayments > 0)
                    <a href="{{ route('admin.payments.index') }}" class="db-alert-item db-alert-danger">
                        <i class="fas fa-credit-card"></i>
                        <span>{{ $failedPayments }} paiement{{ $failedPayments > 1 ? 's' : '' }} échoué{{ $failedPayments > 1 ? 's' : '' }}</span>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>
                    @endif
                </div>
            @else
                <div class="db-alerts-empty">
                    <i class="fas fa-check-circle db-green fs-3"></i>
                    <p class="mt-2 mb-0 text-muted small">Aucune alerte active</p>
                </div>
            @endif

            {{-- ERP mini-stats --}}
            @php $erp = $erp ?? ['low_stock' => 0, 'pending_orders' => 0, 'stock_value' => 0]; @endphp
            <div class="db-erp-section">
                <div class="db-card-title mt-3 mb-2">
                    <i class="fas fa-industry me-2 db-orange"></i>ERP
                </div>
                <div class="db-erp-row">
                    <a href="{{ route('erp.materials.index') }}" class="db-erp-stat{{ $erp['low_stock'] > 0 ? ' db-erp-warn' : '' }}">
                        <span class="db-erp-num">{{ $erp['low_stock'] }}</span>
                        <span class="db-erp-lbl">Stock bas</span>
                    </a>
                    <a href="{{ route('erp.purchases.index', ['status' => 'ordered']) }}" class="db-erp-stat">
                        <span class="db-erp-num">{{ $erp['pending_orders'] }}</span>
                        <span class="db-erp-lbl">Commandes</span>
                    </a>
                    <div class="db-erp-stat">
                        <span class="db-erp-num">{{ number_format($erp['stock_value'] / 1000, 0) }}k</span>
                        <span class="db-erp-lbl">Val. XAF</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── BOTTOM GRID ──────────────────────────────────────────────────── --}}
    <div class="db-bottom-grid">

        <div class="db-card">
            <div class="db-card-header">
                <span class="db-card-title">
                    <i class="fas fa-shopping-bag me-2 db-orange"></i>Commandes récentes
                </span>
                <a href="{{ route('admin.orders.index') }}" class="db-see-all">Tout voir →</a>
            </div>
            <div id="recent-orders-wrap">
                <div class="db-table-skeleton">
                    @for($i = 0; $i < 5; $i++)<div class="db-table-skel-row"></div>@endfor
                </div>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-header">
                <span class="db-card-title">
                    <i class="fas fa-bolt me-2 db-orange"></i>Actions rapides
                </span>
            </div>
            <div class="db-quick-actions">
                <a href="{{ route('admin.orders.index') }}" class="db-action-btn">
                    <i class="fas fa-shopping-bag"></i><span>Commandes</span>
                </a>
                <a href="{{ route('admin.users.index') }}" class="db-action-btn">
                    <i class="fas fa-users"></i><span>Utilisateurs</span>
                </a>
                <a href="{{ route('erp.purchases.create') }}" class="db-action-btn">
                    <i class="fas fa-plus-circle"></i><span>Nouv. achat</span>
                </a>
                <a href="{{ route('erp.materials.index') }}" class="db-action-btn">
                    <i class="fas fa-boxes"></i><span>Matières</span>
                </a>
                <a href="{{ route('erp.suppliers.index') }}" class="db-action-btn">
                    <i class="fas fa-truck"></i><span>Fournisseurs</span>
                </a>
                <a href="{{ route('erp.reports.purchases') }}" class="db-action-btn">
                    <i class="fas fa-chart-bar"></i><span>Rapports</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style nonce="{{ csp_nonce() }}">
:root {
    --db-orange: #ED5F1E;
    --db-black:  #160D0C;
    --db-card:   #FFFFFF;
    --db-border: rgba(22,13,12,.08);
    --db-radius: 14px;
    --db-shadow: 0 2px 12px rgba(22,13,12,.07);
}
.db-root { padding: .25rem 0 2rem; }
.db-orange { color: var(--db-orange) !important; }
.db-green  { color: #22c55e !important; }

.db-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.db-period-tabs { display:inline-flex; background:var(--db-card); border:1px solid var(--db-border); border-radius:10px; padding:3px; gap:2px; }
.db-period-btn { border:none; background:transparent; padding:.4rem .9rem; border-radius:8px; font-size:.82rem; font-weight:500; color:#555; cursor:pointer; transition:all .15s; }
.db-period-btn.active { background:var(--db-orange); color:#fff; box-shadow:0 1px 4px rgba(237,95,30,.35); }
.db-refresh-btn { border:1px solid var(--db-border); background:var(--db-card); color:#555; border-radius:8px; padding:.4rem .7rem; cursor:pointer; transition:all .15s; }
.db-refresh-btn:hover { border-color:var(--db-orange); color:var(--db-orange); }

.db-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem; }
@media (max-width:992px) { .db-kpi-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:576px) { .db-kpi-grid { grid-template-columns:1fr; } }
.db-kpi-card { background:var(--db-card); border-radius:var(--db-radius); padding:1.4rem 1.5rem; box-shadow:var(--db-shadow); transition:transform .2s; min-height:110px; }
.db-kpi-card:hover { transform:translateY(-2px); }
.db-kpi-skeleton { height:70px; background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; border-radius:8px; animation:db-shimmer 1.4s infinite; }
.db-kpi-icon { font-size:1.4rem; margin-bottom:.4rem; }
.db-kpi-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.5px; color:#888; font-weight:600; }
.db-kpi-value { font-size:1.6rem; font-weight:700; color:var(--db-black); line-height:1.1; margin:.25rem 0; }
.db-kpi-change { font-size:.78rem; font-weight:600; }
.db-kpi-change.up   { color:#22c55e; }
.db-kpi-change.down { color:var(--db-orange); }
.db-kpi-change.flat { color:#aaa; }

.db-mid-grid    { display:grid; grid-template-columns:1fr 340px; gap:1rem; margin-bottom:1.25rem; }
.db-bottom-grid { display:grid; grid-template-columns:1fr 300px; gap:1rem; }
@media (max-width:992px) { .db-mid-grid,.db-bottom-grid { grid-template-columns:1fr; } }

.db-card { background:var(--db-card); border-radius:var(--db-radius); padding:1.25rem 1.5rem; box-shadow:var(--db-shadow); }
.db-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
.db-card-title { font-weight:600; font-size:.93rem; color:var(--db-black); display:flex; align-items:center; }
.db-see-all { font-size:.8rem; color:var(--db-orange); text-decoration:none; font-weight:500; }
.db-see-all:hover { text-decoration:underline; }

.db-chart-wrap { position:relative; height:220px; }
.db-chart-skeleton { position:absolute; inset:0; background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; border-radius:8px; animation:db-shimmer 1.4s infinite; }

.db-alerts-list { display:flex; flex-direction:column; gap:.5rem; }
.db-alert-item { display:flex; align-items:center; gap:.6rem; padding:.6rem .8rem; border-radius:8px; font-size:.83rem; text-decoration:none; font-weight:500; transition:filter .15s; }
.db-alert-item:hover { filter:brightness(.97); }
.db-alert-warn   { background:#fff8e1; color:#92610a; }
.db-alert-danger { background:#fff0ec; color:var(--db-orange); }
.db-alerts-empty { text-align:center; padding:1.25rem 0; }

.db-erp-section { margin-top:.25rem; }
.db-erp-row { display:flex; gap:.5rem; }
.db-erp-stat { flex:1; background:#fafafa; border:1px solid var(--db-border); border-radius:10px; padding:.7rem .4rem; text-align:center; text-decoration:none; color:inherit; transition:border-color .15s; }
.db-erp-stat:hover { border-color:var(--db-orange); color:inherit; }
.db-erp-stat.db-erp-warn { border-color:#f59e0b; background:#fffbeb; }
.db-erp-num { display:block; font-size:1.2rem; font-weight:700; color:var(--db-black); }
.db-erp-lbl { display:block; font-size:.68rem; text-transform:uppercase; color:#888; letter-spacing:.3px; }

.db-orders-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.db-orders-table th { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:#888; padding:.4rem .5rem; border-bottom:1px solid var(--db-border); }
.db-orders-table td { padding:.5rem .5rem; border-bottom:1px solid var(--db-border); }
.db-orders-table tr:last-child td { border-bottom:none; }
.db-orders-table tr:hover td { background:#fafafa; }
.db-badge { display:inline-block; padding:.18rem .5rem; border-radius:999px; font-size:.7rem; font-weight:600; }
.db-badge-pending    { background:#fef9c3; color:#854d0e; }
.db-badge-processing { background:#dbeafe; color:#1d4ed8; }
.db-badge-completed  { background:#dcfce7; color:#166534; }
.db-badge-cancelled  { background:#fee2e2; color:#991b1b; }
.db-table-skeleton { display:flex; flex-direction:column; gap:.5rem; }
.db-table-skel-row { height:34px; background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%); background-size:200% 100%; border-radius:6px; animation:db-shimmer 1.4s infinite; }

.db-quick-actions { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; }
.db-action-btn { display:flex; flex-direction:column; align-items:center; gap:.4rem; padding:.85rem .5rem; background:#fafafa; border:1px solid var(--db-border); border-radius:10px; text-decoration:none; color:var(--db-black); font-size:.76rem; font-weight:500; transition:all .15s; }
.db-action-btn i { font-size:1.15rem; color:var(--db-orange); }
.db-action-btn:hover { border-color:var(--db-orange); color:var(--db-orange); transform:translateY(-1px); box-shadow:0 2px 8px rgba(237,95,30,.15); }

@keyframes db-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script nonce="{{ csp_nonce() }}">
(function () {
    'use strict';

    var KPIS_URL   = {{ Js::from(route('admin.dashboard.kpis')) }};
    var CHART_URL  = {{ Js::from(route('admin.dashboard.chart')) }};
    var ORDERS_URL = {{ Js::from(route('admin.orders.data')) }};

    var currentPeriod = 'today';
    var chartInstance = null;

    document.querySelectorAll('.db-period-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.db-period-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentPeriod = btn.dataset.period;
            loadKpis(currentPeriod);
        });
    });

    // ── KPIs ──────────────────────────────────────────────────────────────
    function loadKpis(period) {
        showKpiSkeletons();
        fetch(KPIS_URL + '?period=' + period, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(renderKpis)
            .catch(renderKpisError);
    }

    function showKpiSkeletons() {
        ['kpi-revenue','kpi-orders','kpi-clients','kpi-pending'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.textContent = '';
            var sk = document.createElement('div');
            sk.className = 'db-kpi-skeleton';
            el.appendChild(sk);
        });
    }

    function buildKpiCard(el, icon, label, value, changePct) {
        el.textContent = '';
        var iconEl = document.createElement('div');
        iconEl.className = 'db-kpi-icon';
        iconEl.textContent = icon;
        var labelEl = document.createElement('div');
        labelEl.className = 'db-kpi-label';
        labelEl.textContent = label;
        var valueEl = document.createElement('div');
        valueEl.className = 'db-kpi-value';
        valueEl.textContent = value;
        var changeEl = document.createElement('div');
        var dir = changePct > 0 ? 'up' : (changePct < 0 ? 'down' : 'flat');
        changeEl.className = 'db-kpi-change ' + dir;
        var arrow = changePct > 0 ? '↑ ' : (changePct < 0 ? '↓ ' : '→ ');
        changeEl.textContent = arrow + Math.abs(changePct) + '% vs période préc.';
        el.appendChild(iconEl);
        el.appendChild(labelEl);
        el.appendChild(valueEl);
        el.appendChild(changeEl);
    }

    function renderKpis(data) {
        var rev = document.getElementById('kpi-revenue');
        var ord = document.getElementById('kpi-orders');
        var cli = document.getElementById('kpi-clients');
        var pen = document.getElementById('kpi-pending');
        if (rev) buildKpiCard(rev, '💰', 'Chiffre d\'affaires', data.revenue.formatted, data.revenue.change_pct);
        if (ord) buildKpiCard(ord, '🛒', 'Commandes',           String(data.orders.value),  data.orders.change_pct);
        if (cli) buildKpiCard(cli, '👥', 'Nouveaux clients',    String(data.clients.value), data.clients.change_pct);
        if (pen) buildKpiCard(pen, '⏳', 'En attente',          String(data.pending_orders), 0);
    }

    function renderKpisError() {
        ['kpi-revenue','kpi-orders','kpi-clients','kpi-pending'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) { el.textContent = ''; var p = document.createElement('p'); p.className = 'text-muted small text-center pt-3'; p.textContent = '—'; el.appendChild(p); }
        });
    }

    // ── Chart ─────────────────────────────────────────────────────────────
    function loadChart() {
        fetch(CHART_URL + '?days=30', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(renderChart)
            .catch(function () { var sk = document.getElementById('chart-skeleton'); if (sk) sk.style.display = 'none'; });
    }

    function renderChart(data) {
        var skeleton = document.getElementById('chart-skeleton');
        var canvas   = document.getElementById('revenue-chart');
        if (!canvas) return;
        if (skeleton) skeleton.style.display = 'none';
        canvas.style.display = 'block';
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'CA (XAF)',
                    data: data.datasets[0].data,
                    borderColor: '#ED5F1E',
                    backgroundColor: 'rgba(237,95,30,.08)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    fill: true,
                    tension: .35,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (ctx) { return ' ' + ctx.parsed.y.toLocaleString('fr-FR') + ' XAF'; } } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 11 } } },
                    y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 }, callback: function (v) { return (v/1000).toFixed(0) + 'k'; } } }
                }
            }
        });
    }

    // ── Recent orders — DOM-built table to avoid innerHTML with user data ──
    function loadRecentOrders() {
        fetch(ORDERS_URL + '?per_page=6&sort_by=created_at&sort_dir=desc', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderRecentOrders(data.data || []); })
            .catch(function () {
                var w = document.getElementById('recent-orders-wrap');
                if (w) { w.textContent = ''; var p = document.createElement('p'); p.className = 'text-muted small text-center py-3'; p.textContent = 'Impossible de charger les commandes.'; w.appendChild(p); }
            });
    }

    var STATUS_LABELS  = { pending:'En attente', processing:'En cours', completed:'Terminé', cancelled:'Annulé' };
    var STATUS_CLASSES = { pending:'db-badge-pending', processing:'db-badge-processing', completed:'db-badge-completed', cancelled:'db-badge-cancelled' };

    function renderRecentOrders(rows) {
        var wrap = document.getElementById('recent-orders-wrap');
        if (!wrap) return;
        wrap.textContent = '';
        if (!rows.length) {
            var p = document.createElement('p');
            p.className = 'text-muted small text-center py-3';
            p.textContent = 'Aucune commande récente.';
            wrap.appendChild(p);
            return;
        }
        var table = document.createElement('table');
        table.className = 'db-orders-table';
        var thead = table.createTHead();
        var hr = thead.insertRow();
        ['#','Client','Montant','Statut','Date'].forEach(function (h) {
            var th = document.createElement('th');
            th.textContent = h;
            hr.appendChild(th);
        });
        var tbody = table.createTBody();
        rows.forEach(function (o) {
            var tr = tbody.insertRow();
            // id cell with link
            var tdId = tr.insertCell();
            var link = document.createElement('a');
            link.href = '/admin/orders/' + o.id;
            link.textContent = '#' + o.id;
            tdId.appendChild(link);
            // client
            var tdCli = tr.insertCell();
            tdCli.textContent = o.user ? (o.user.name || o.user.email || '—') : '—';
            // amount
            var tdAmt = tr.insertCell();
            tdAmt.textContent = o.total_amount ? parseFloat(o.total_amount).toLocaleString('fr-FR') + ' XAF' : '—';
            // status badge
            var tdSt = tr.insertCell();
            var badge = document.createElement('span');
            badge.className = 'db-badge ' + (STATUS_CLASSES[o.status] || 'db-badge-pending');
            badge.textContent = STATUS_LABELS[o.status] || o.status;
            tdSt.appendChild(badge);
            // date
            var tdDt = tr.insertCell();
            tdDt.textContent = o.created_at ? o.created_at.substring(0, 10) : '—';
        });
        wrap.appendChild(table);
    }

    // boot
    loadKpis(currentPeriod);
    loadChart();
    loadRecentOrders();
}());
</script>
@endpush
