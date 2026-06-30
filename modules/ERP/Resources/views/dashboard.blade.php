@extends('layouts.admin-master')
@section('title', 'ERP — Tableau de Bord')
@section('page-title', 'ERP — Tableau de Bord')
@section('page-subtitle', 'Stocks · Fournisseurs · Matières premières')

@push('styles')
<style nonce="{{ csp_nonce() }}">
/* ── ERP Dashboard ───────────────────────────────────────────────────── */
.erp-kpi{background:#1e1410;border:1px solid #2a1f1c;border-radius:8px;padding:1.25rem 1.5rem}
.erp-kpi-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#888;font-weight:600;margin-bottom:.5rem}
.erp-kpi-value{font-size:1.6rem;font-weight:700;color:#e2e8f0;line-height:1}
.erp-kpi-sub{font-size:.78rem;color:#666;margin-top:.35rem}
.erp-kpi-icon{width:44px;height:44px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.erp-icon-ok    {background:rgba(74,222,128,.15);color:#4ade80}
.erp-icon-warn  {background:rgba(251,191,36,.15);color:#fbbf24}
.erp-icon-danger{background:rgba(239,68,68,.15);color:#f87171}
.erp-icon-orange{background:rgba(237,95,30,.15);color:#ED5F1E}
.erp-icon-blue  {background:rgba(96,165,250,.15);color:#60a5fa}
.erp-c-ok    {color:#4ade80}
.erp-c-warn  {color:#fbbf24}
.erp-c-danger{color:#f87171}
.erp-c-orange{color:#ED5F1E}
.erp-c-blue  {color:#60a5fa}

.erp-section{background:#1e1410;border:1px solid #2a1f1c;border-radius:8px;margin-bottom:1.25rem}
.erp-section.h-100{margin-bottom:0}
.erp-section-header{display:flex;justify-content:space-between;align-items:center;padding:.875rem 1.25rem;border-bottom:1px solid #2a1f1c}
.erp-section-title{font-size:.9rem;font-weight:700;color:#e2e8f0;margin:0}
.erp-section-body{padding:1.25rem}

.erp-quick-btn{display:flex;align-items:center;gap:.5rem;padding:.625rem 1rem;background:#2a1f1c;border:1px solid #3a2f2c;border-radius:6px;color:#e2e8f0;font-size:.82rem;font-weight:600;text-decoration:none;transition:background .15s,border-color .15s;white-space:nowrap}
.erp-quick-btn:hover{background:rgba(237,95,30,.15);border-color:#ED5F1E;color:#ED5F1E;text-decoration:none}
.erp-quick-btn i{color:#ED5F1E}
.erp-quick-btn-primary{background:rgba(237,95,30,.15);border-color:#ED5F1E;color:#ED5F1E}
.erp-quick-btn-primary:hover{background:rgba(237,95,30,.25)}
.erp-quick-btn-primary i{color:#ED5F1E}

.erp-alert-row{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid #2a1f1c}
.erp-alert-row:last-child{border-bottom:0}
.erp-alert-name{font-size:.85rem;font-weight:600;color:#e2e8f0}

.erp-purchase-row{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid #2a1f1c;text-decoration:none;transition:background .12s}
.erp-purchase-row:last-child{border-bottom:0}
.erp-purchase-row:hover{background:rgba(237,95,30,.05)}
.erp-purchase-sup{font-size:.85rem;font-weight:600;color:#e2e8f0}
.erp-purchase-ref{font-size:.75rem;color:#666}
.erp-purchase-amt{font-size:.9rem;font-weight:700;color:#ED5F1E}
.erp-purchase-date{font-size:.75rem;color:#666}

.erp-top-row{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid #2a1f1c}
.erp-top-row:last-child{border-bottom:0}
.erp-top-name{font-size:.85rem;font-weight:600;color:#e2e8f0}
.erp-top-qty{font-size:.8rem;font-weight:700;color:#ED5F1E;background:rgba(237,95,30,.15);border:1px solid rgba(237,95,30,.3);padding:.2rem .6rem;border-radius:4px}

.erp-empty{padding:2.5rem 1rem;text-align:center;color:#555;font-size:.85rem}
.erp-link-sm{font-size:.78rem;color:#ED5F1E;text-decoration:none}
.erp-link-sm:hover{text-decoration:underline;color:#ED5F1E}

.al-badge{display:inline-block;padding:.2rem .55rem;border-radius:4px;font-size:.75rem;font-weight:600;line-height:1.4}
.al-badge-ok    {background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3)}
.al-badge-warn  {background:rgba(251,191,36,.15);color:#fbbf24;border:1px solid rgba(251,191,36,.3)}
.al-badge-danger{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
</style>
@endpush

@section('content')

{{-- KPI Row --}}
<div class="row g-3 mb-4">
  <div class="col-lg-3 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Valorisation Stock</div>
        <div class="erp-kpi-value erp-c-orange">{{ number_format($stats['stock_value_global'], 0, ',', ' ') }}</div>
        <div class="erp-kpi-sub">FCFA</div>
      </div>
      <div class="erp-kpi-icon erp-icon-orange"><i class="fas fa-coins"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Achats ce mois</div>
        <div class="erp-kpi-value">{{ $stats['purchases_month_count'] }}</div>
        <div class="erp-kpi-sub">{{ number_format($stats['purchases_month_sum'], 0, ',', ' ') }} FCFA</div>
      </div>
      <div class="erp-kpi-icon erp-icon-blue"><i class="fas fa-shopping-cart"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Entrées Auj.</div>
        <div class="erp-kpi-value erp-c-ok">+{{ $stats['flow_today_in'] }}</div>
        <div class="erp-kpi-sub">mouvements entrants</div>
      </div>
      <div class="erp-kpi-icon erp-icon-ok"><i class="fas fa-arrow-down"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Sorties Auj.</div>
        <div class="erp-kpi-value erp-c-danger">-{{ $stats['flow_today_out'] }}</div>
        <div class="erp-kpi-sub">mouvements sortants</div>
      </div>
      <div class="erp-kpi-icon erp-icon-danger"><i class="fas fa-arrow-up"></i></div>
    </div>
  </div>
</div>

{{-- Stock Health Row --}}
<div class="row g-3 mb-4">
  <div class="col-lg-4 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Produits Total</div>
        <div class="erp-kpi-value">{{ $stats['products_total'] }}</div>
      </div>
      <div class="erp-kpi-icon erp-icon-blue"><i class="fas fa-box"></i></div>
    </div>
  </div>
  <div class="col-lg-4 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Stock Faible (&lt; 5)</div>
        <div class="erp-kpi-value erp-c-warn">{{ $stats['products_low_stock'] }}</div>
      </div>
      <div class="erp-kpi-icon erp-icon-warn"><i class="fas fa-exclamation-triangle"></i></div>
    </div>
  </div>
  <div class="col-lg-4 col-md-6">
    <div class="erp-kpi d-flex justify-content-between align-items-start">
      <div>
        <div class="erp-kpi-label">Rupture de Stock</div>
        <div class="erp-kpi-value erp-c-danger">{{ $stats['products_out_of_stock'] }}</div>
      </div>
      <div class="erp-kpi-icon erp-icon-danger"><i class="fas fa-times-circle"></i></div>
    </div>
  </div>
</div>

{{-- Alertes + Actions Rapides --}}
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="erp-section h-100">
      <div class="erp-section-header">
        <h5 class="erp-section-title"><i class="fas fa-exclamation-triangle erp-c-warn me-2"></i>Alertes Stock</h5>
        <a href="{{ route('erp.stocks.index') }}" class="erp-link-sm">Voir tout →</a>
      </div>
      @if($low_stock_products->count() > 0)
        @foreach($low_stock_products as $product)
        <div class="erp-alert-row">
          <span class="erp-alert-name">{{ Str::limit($product->title, 30) }}</span>
          <div class="d-flex align-items-center gap-2">
            <span class="fw-bold {{ $product->stock <= 0 ? 'erp-c-danger' : 'erp-c-warn' }}">{{ $product->stock }}</span>
            @if($product->stock <= 0)
              <span class="al-badge al-badge-danger">Rupture</span>
            @else
              <span class="al-badge al-badge-warn">Critique</span>
            @endif
          </div>
        </div>
        @endforeach
      @else
        <div class="erp-empty"><i class="fas fa-check-circle erp-c-ok me-2"></i>Tous les stocks sont OK !</div>
      @endif
    </div>
  </div>

  <div class="col-lg-6">
    <div class="erp-section h-100">
      <div class="erp-section-header">
        <h5 class="erp-section-title"><i class="fas fa-bolt erp-c-orange me-2"></i>Actions Rapides</h5>
      </div>
      <div class="erp-section-body">
        <div class="row g-2">
          <div class="col-6"><a href="{{ route('erp.stocks.index') }}" class="erp-quick-btn w-100"><i class="fas fa-warehouse"></i>Stocks</a></div>
          <div class="col-6"><a href="{{ route('erp.suppliers.index') }}" class="erp-quick-btn w-100"><i class="fas fa-truck"></i>Fournisseurs</a></div>
          <div class="col-6"><a href="{{ route('erp.materials.index') }}" class="erp-quick-btn w-100"><i class="fas fa-cube"></i>Matières</a></div>
          <div class="col-6"><a href="{{ route('erp.purchases.index') }}" class="erp-quick-btn w-100"><i class="fas fa-shopping-cart"></i>Achats</a></div>
          <div class="col-6"><a href="{{ route('erp.materials.create') }}" class="erp-quick-btn erp-quick-btn-primary w-100"><i class="fas fa-plus"></i>+ Matière</a></div>
          <div class="col-6"><a href="{{ route('erp.purchases.create') }}" class="erp-quick-btn erp-quick-btn-primary w-100"><i class="fas fa-plus"></i>+ Commande</a></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Rapports --}}
<div class="erp-section mb-4">
  <div class="erp-section-header">
    <h5 class="erp-section-title"><i class="fas fa-chart-bar erp-c-orange me-2"></i>Rapports & Exports</h5>
  </div>
  <div class="erp-section-body">
    <div class="row g-2">
      <div class="col-md-3 col-6"><a href="{{ route('erp.reports.stock-valuation') }}" class="erp-quick-btn w-100"><i class="fas fa-coins"></i>Valorisation</a></div>
      <div class="col-md-3 col-6"><a href="{{ route('erp.reports.purchases') }}" class="erp-quick-btn w-100"><i class="fas fa-shopping-cart"></i>Rapport Achats</a></div>
      <div class="col-md-3 col-6"><a href="{{ route('erp.reports.stock-movements') }}" class="erp-quick-btn w-100"><i class="fas fa-exchange-alt"></i>Mouvements</a></div>
      <div class="col-md-3 col-6"><a href="{{ route('erp.reports.replenishment-suggestions') }}" class="erp-quick-btn w-100"><i class="fas fa-lightbulb"></i>Suggestions</a></div>
    </div>
  </div>
</div>

{{-- Top Matières + Derniers Achats --}}
<div class="row g-3">
  <div class="col-lg-6">
    <div class="erp-section h-100">
      <div class="erp-section-header">
        <h5 class="erp-section-title"><i class="fas fa-cube erp-c-orange me-2"></i>Top Matières (Achat)</h5>
      </div>
      @if($top_materials->count() > 0)
        @foreach($top_materials as $item)
        <div class="erp-top-row">
          <span class="erp-top-name">{{ $item->purchasable->name ?? 'Inconnu' }}</span>
          <span class="erp-top-qty"><i class="fas fa-boxes me-1"></i>{{ $item->total_qty }} u.</span>
        </div>
        @endforeach
      @else
        <div class="erp-empty"><i class="fas fa-cube me-2"></i>Pas assez de données</div>
      @endif
    </div>
  </div>

  <div class="col-lg-6">
    <div class="erp-section h-100">
      <div class="erp-section-header">
        <h5 class="erp-section-title"><i class="fas fa-shopping-cart erp-c-orange me-2"></i>Derniers Achats</h5>
        <a href="{{ route('erp.purchases.index') }}" class="erp-link-sm">Voir tout →</a>
      </div>
      @if($recent_purchases->count() > 0)
        @foreach($recent_purchases as $purchase)
        <a href="{{ route('erp.purchases.show', $purchase) }}" class="erp-purchase-row">
          <div>
            <div class="erp-purchase-sup">{{ $purchase->supplier->name }}</div>
            <div class="erp-purchase-ref"># {{ $purchase->reference }}</div>
          </div>
          <div class="text-end">
            <div class="erp-purchase-amt">{{ number_format($purchase->total_amount, 0, ',', ' ') }} FCFA</div>
            <div class="erp-purchase-date">{{ $purchase->purchase_date->format('d/m/Y') }}</div>
          </div>
        </a>
        @endforeach
      @else
        <div class="erp-empty"><i class="fas fa-shopping-cart me-2"></i>Aucun achat récent</div>
      @endif
    </div>
  </div>
</div>

@endsection
