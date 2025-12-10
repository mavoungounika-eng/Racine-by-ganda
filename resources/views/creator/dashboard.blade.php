@extends('layouts.creator')

@section('title', 'Tableau de Bord Créateur - RACINE BY GANDA')
@section('page-title', 'Tableau de bord')

@push('styles')
<style>
    /* ===== HERO SECTION ===== */
    .creator-hero {
        background: linear-gradient(135deg, var(--racine-black) 0%, var(--racine-black-soft) 100%);
        padding: 2.5rem 0;
        margin: -2rem -2rem 2rem -2rem;
        position: relative;
        overflow: hidden;
        border-bottom: 2px solid rgba(237, 95, 30, 0.3);
    }
    
    .creator-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4A574' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .creator-hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        flex-wrap: wrap;
    }
    
    .creator-hero-left {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex: 1;
    }
    
    .creator-hero-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .creator-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-orange);
        border: 3px solid rgba(237, 95, 30, 0.3);
        flex-shrink: 0;
    }
    
    .creator-avatar span {
        color: white;
        font-size: 2.75rem;
        font-weight: 700;
        font-family: var(--font-heading);
    }
    
    .creator-info h2 {
        font-family: var(--font-heading);
        font-size: 1.75rem;
        font-weight: 400;
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .creator-info .page-subtitle {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
        font-weight: 400;
    }
    
    .creator-info p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: var(--radius-full);
        font-size: 0.875rem;
        font-weight: 600;
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .status-badge.pending {
        background: rgba(255, 184, 0, 0.15);
        color: var(--racine-yellow);
        border-color: rgba(255, 184, 0, 0.3);
    }
    
    .status-badge.suspended {
        background: rgba(255, 107, 107, 0.15);
        color: #ff6b6b;
        border-color: rgba(255, 107, 107, 0.3);
    }
    
    .hero-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
        border-radius: var(--radius-lg);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: var(--transition-fast);
        box-shadow: var(--shadow-orange);
        border: none;
    }
    
    .hero-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(237, 95, 30, 0.4);
        color: white;
        text-decoration: none;
    }
    
    /* ===== STATS CARDS ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .stat-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        transition: var(--transition-fast);
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--stat-color-1), var(--stat-color-2));
    }
    
    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--shadow-xl);
    }
    
    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    
    .stat-card-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #8B7355;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-card-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--racine-black);
        font-family: var(--font-heading);
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    
    .stat-card-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--icon-bg-1), var(--icon-bg-2));
        box-shadow: var(--shadow-md);
    }
    
    .stat-card-icon i {
        font-size: 1.75rem;
        color: white;
    }
    
    .stat-card-subtitle {
        font-size: 0.875rem;
        color: #8B7355;
        margin-top: 0.5rem;
    }
    
    .stat-card.products {
        --stat-color-1: #D4A574;
        --stat-color-2: #8B5A2B;
        --icon-bg-1: #D4A574;
        --icon-bg-2: #8B5A2B;
    }
    
    .stat-card.sales {
        --stat-color-1: #22C55E;
        --stat-color-2: #16A34A;
        --icon-bg-1: #22C55E;
        --icon-bg-2: #16A34A;
    }
    
    .stat-card.revenue {
        --stat-color-1: #3B82F6;
        --stat-color-2: #2563EB;
        --icon-bg-1: #3B82F6;
        --icon-bg-2: #2563EB;
    }
    
    .stat-card.pending {
        --stat-color-1: var(--racine-yellow);
        --stat-color-2: var(--racine-orange);
        --icon-bg-1: var(--racine-yellow);
        --icon-bg-2: var(--racine-orange);
    }
    
    /* ===== MAIN CONTENT GRID ===== */
    .dashboard-main-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    /* ===== ORDERS SECTION ===== */
    .orders-section {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #F8F6F3;
    }
    
    .section-title {
        font-family: var(--font-heading);
        font-size: 1.5rem;
        font-weight: 400;
        color: var(--racine-black);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }
    
    .section-title i {
        color: var(--racine-orange);
        font-size: 1.35rem;
    }
    
    .section-link {
        color: #D4A574;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition-fast);
    }
    
    .section-link:hover {
        color: var(--racine-orange);
        gap: 0.75rem;
        text-decoration: none;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table thead {
        background: #F8F6F3;
    }
    
    .orders-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
        color: #8B7355;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .orders-table td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid #F8F6F3;
        color: var(--racine-black);
    }
    
    .orders-table tbody tr:hover {
        background: rgba(212, 165, 116, 0.05);
    }
    
    .order-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .order-status.pending {
        background: rgba(255, 184, 0, 0.1);
        color: var(--racine-yellow);
    }
    
    .order-status.paid {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }
    
    .order-status.shipped {
        background: rgba(59, 130, 246, 0.1);
        color: #3B82F6;
    }
    
    .order-status.completed {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }
    
    .order-status.cancelled {
        background: rgba(255, 107, 107, 0.1);
        color: #ff6b6b;
    }
    
    /* ===== QUICK ACTIONS ===== */
    .quick-actions {
        background: white;
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .quick-action-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-radius: var(--radius-lg);
        text-decoration: none;
        color: var(--racine-black);
        transition: var(--transition-fast);
        border: 1px solid transparent;
        margin-bottom: 0.75rem;
    }
    
    .quick-action-item:hover {
        background: rgba(212, 165, 116, 0.05);
        border-color: rgba(212, 165, 116, 0.2);
        transform: translateX(4px);
        text-decoration: none;
        color: var(--racine-black);
    }
    
    .quick-action-item:last-child {
        margin-bottom: 0;
    }
    
    .quick-action-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--action-bg-1), var(--action-bg-2));
        flex-shrink: 0;
    }
    
    .quick-action-icon i {
        font-size: 1.25rem;
        color: white;
    }
    
    .quick-action-content {
        flex: 1;
    }
    
    .quick-action-title {
        font-weight: 600;
        color: var(--racine-black);
        margin-bottom: 0.25rem;
    }
    
    .quick-action-desc {
        font-size: 0.875rem;
        color: #8B7355;
    }
    
    .quick-action-arrow {
        color: #8B7355;
        font-size: 0.875rem;
    }
    
    .quick-action-item.primary {
        --action-bg-1: #D4A574;
        --action-bg-2: #8B5A2B;
    }
    
    .quick-action-item.secondary {
        --action-bg-1: #3B82F6;
        --action-bg-2: #2563EB;
    }
    
    .quick-action-item.success {
        --action-bg-1: #22C55E;
        --action-bg-2: #16A34A;
    }
    
    .quick-action-item.warning {
        --action-bg-1: var(--racine-yellow);
        --action-bg-2: var(--racine-orange);
    }
    
    /* ===== PRODUCTS GRID ===== */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .product-card {
        background: #F8F6F3;
        border-radius: var(--radius-lg);
        padding: 1rem;
        transition: var(--transition-fast);
        border: 1px solid rgba(0, 0, 0, 0.05);
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: rgba(212, 165, 116, 0.3);
        text-decoration: none;
        color: inherit;
    }
    
    .product-image-wrapper {
        width: 100%;
        height: 150px;
        background: white;
        border-radius: var(--radius-md);
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .product-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .product-title {
        font-weight: 600;
        color: var(--racine-black);
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }
    
    .product-price {
        font-size: 0.875rem;
        color: #8B7355;
        margin-bottom: 0.5rem;
    }
    
    .product-badges {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .product-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .product-badge.active {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }
    
    .product-badge.inactive {
        background: rgba(255, 107, 107, 0.1);
        color: #ff6b6b;
    }
    
    .product-badge.stock {
        color: #8B7355;
        background: rgba(0, 0, 0, 0.05);
    }
    
    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #8B7355;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 1rem;
    }
    
    .empty-state-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--racine-black);
        margin-bottom: 0.5rem;
    }
    
    .empty-state-text {
        font-size: 0.95rem;
        color: #8B7355;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .dashboard-main-grid {
            grid-template-columns: 1fr !important;
        }
    }
    
    @media (max-width: 768px) {
        .creator-hero {
            padding: 2rem 0;
            margin: -1rem -1rem 1rem -1rem;
        }
        
        .creator-hero-content {
            flex-direction: column;
            text-align: center;
        }
        
        .creator-hero-left {
            flex-direction: column;
            text-align: center;
        }
        
        .creator-hero-right {
            width: 100%;
            justify-content: center;
        }
        
        .hero-action-btn {
            width: 100%;
            justify-content: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .orders-table {
            font-size: 0.875rem;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 0.75rem 0.5rem;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
</style>
@endpush

@section('content')
<div class="creator-dashboard">
    {{-- HERO SECTION --}}
    <div class="creator-hero">
        <div class="creator-hero-content">
            <div class="creator-hero-left">
                <div class="creator-avatar">
                    <span>{{ strtoupper(substr($creatorProfile->brand_name ?? $user->name ?? 'C', 0, 1)) }}</span>
                </div>
                <div class="creator-info">
                    <h2>Tableau de Bord</h2>
                    <p class="page-subtitle">Vue d'ensemble de votre activité</p>
                    <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                        <div>
                            <span style="color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Bonjour,</span>
                            <strong style="color: white; font-size: 1rem; margin-left: 0.5rem;">{{ $creatorProfile->brand_name ?? $user->name ?? 'Créateur' }}</strong>
                        </div>
                        <span class="status-badge {{ $creatorProfile->status ?? 'active' }}">
                            <i class="fas fa-{{ $creatorProfile->status === 'active' ? 'check-circle' : ($creatorProfile->status === 'pending' ? 'clock' : 'ban') }}"></i>
                            {{ $creatorProfile->status === 'active' ? 'Compte Actif' : ($creatorProfile->status === 'pending' ? 'En Attente' : 'Suspendu') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="creator-hero-right">
                <a href="{{ route('creator.products.create') }}" class="hero-action-btn">
                    <i class="fas fa-plus"></i>
                    Nouveau Produit
                </a>
            </div>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="stats-grid">
        <div class="stat-card products">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Produits Publiés</div>
                    <div class="stat-card-value">{{ $stats['products_count'] ?? 0 }}</div>
                    <div class="stat-card-subtitle">{{ $stats['active_products_count'] ?? 0 }} actifs</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>

        <div class="stat-card sales">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Ventes Total</div>
                    <div class="stat-card-value" style="font-size: 1.75rem;">
                        {{ number_format($stats['total_sales'] ?? 0, 0, ',', ' ') }}<small style="font-size: 0.6em;"> FCFA</small>
                    </div>
                    <div class="stat-card-subtitle">Montant total des ventes</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>

        <div class="stat-card revenue">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Revenus ce Mois</div>
                    <div class="stat-card-value" style="font-size: 1.75rem;">
                        {{ number_format($stats['monthly_sales'] ?? 0, 0, ',', ' ') }}<small style="font-size: 0.6em;"> FCFA</small>
                    </div>
                    <div class="stat-card-subtitle">Ventes du mois en cours</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>

        <div class="stat-card pending">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Commandes en Attente</div>
                    <div class="stat-card-value">{{ $stats['pending_orders'] ?? 0 }}</div>
                    <div class="stat-card-subtitle">À traiter</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT GRID --}}
    <div class="dashboard-main-grid">
        {{-- ORDERS SECTION --}}
        <div class="orders-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-shopping-bag"></i>
                    Commandes Récentes
                </h3>
                <a href="{{ route('creator.orders.index') }}" class="section-link">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            @if(isset($recentOrders) && $recentOrders->count() > 0)
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('creator.orders.index') }}?order={{ $order->id }}" style="color: #D4A574; text-decoration: none; font-weight: 600;">
                                #{{ $order->id }}
                            </a>
                        </td>
                        <td>{{ $order->customer_name ?? ($order->user->name ?? 'N/A') }}</td>
                        <td><strong>{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</strong></td>
                        <td>
                            <span class="order-status {{ strtolower($order->status ?? 'pending') }}">
                                {{ ucfirst($order->status ?? 'En attente') }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="empty-state-title">Aucune commande</div>
                <div class="empty-state-text">Vos commandes apparaîtront ici une fois que vous aurez des ventes.</div>
            </div>
            @endif
        </div>

        {{-- VALIDATION STATUS --}}
        @php
            $checklist = $creatorProfile->validationChecklist ?? collect();
            $completionPercentage = $checklist->count() > 0 
                ? \App\Models\CreatorValidationChecklist::getCompletionPercentage($creatorProfile->id) 
                : 0;
            $requiredCompletionPercentage = $checklist->count() > 0 
                ? \App\Models\CreatorValidationChecklist::getRequiredCompletionPercentage($creatorProfile->id) 
                : 0;
            $pendingItems = $checklist->where('is_completed', false)->where('is_required', true)->count();
        @endphp
        @if($creatorProfile->status === 'pending' || $pendingItems > 0)
        <div class="validation-status-card" style="background: linear-gradient(135deg, #FFF8F0 0%, #FFFBF5 100%); border: 2px solid var(--racine-orange); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--racine-black);">
                        <i class="fas fa-clipboard-check text-racine-orange me-2"></i>
                        Statut de Validation
                    </h4>
                    <p class="text-muted small mb-0">Suivez la progression de votre dossier</p>
                </div>
                @if($creatorProfile->status === 'pending')
                    <span class="badge bg-warning rounded-pill">
                        <i class="fas fa-clock me-1"></i>En attente
                    </span>
                @elseif($creatorProfile->is_verified)
                    <span class="badge bg-success rounded-pill">
                        <i class="fas fa-check-circle me-1"></i>Vérifié
                    </span>
                @endif
            </div>
            
            @if($checklist->count() > 0)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-racine-black">Progression globale</span>
                        <span class="small fw-bold text-racine-orange">{{ $completionPercentage }}%</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 8px; background: #F8F6F3;">
                        <div class="progress-bar bg-{{ $completionPercentage >= 100 ? 'success' : ($completionPercentage >= 75 ? 'warning' : 'danger') }}" 
                             role="progressbar" 
                             style="width: {{ $completionPercentage }}%; border-radius: 8px;"
                             aria-valuenow="{{ $completionPercentage }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                        <span>Éléments requis : {{ $requiredCompletionPercentage }}%</span>
                        <span>{{ $checklist->where('is_completed', true)->count() }} / {{ $checklist->count() }} complétés</span>
                    </div>
                </div>
                
                @if($pendingItems > 0)
                    <div class="alert alert-warning mb-0" style="background: rgba(237, 95, 30, 0.1); border: 1px solid rgba(237, 95, 30, 0.3); border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Action requise :</strong> Il vous reste {{ $pendingItems }} élément(s) requis à compléter pour finaliser votre dossier.
                    </div>
                @elseif($completionPercentage >= 100)
                    <div class="alert alert-success mb-0" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px;">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Félicitations !</strong> Votre dossier est complet. Il sera examiné par notre équipe sous peu.
                    </div>
                @endif
            @else
                <div class="alert alert-info mb-0" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px;">
                    <i class="fas fa-info-circle me-2"></i>
                    Votre checklist de validation sera initialisée prochainement.
                </div>
            @endif
        </div>
        @endif

        {{-- QUICK ACTIONS --}}
        <div class="quick-actions">
            <h3 class="section-title" style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #F8F6F3;">
                <i class="fas fa-bolt"></i>
                Actions Rapides
            </h3>
            
            <a href="{{ route('creator.products.index') }}" class="quick-action-item primary">
                <div class="quick-action-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="quick-action-content">
                    <div class="quick-action-title">Gérer mes Produits</div>
                    <div class="quick-action-desc">Voir et modifier vos créations</div>
                </div>
                <i class="fas fa-chevron-right quick-action-arrow"></i>
            </a>

            <a href="{{ route('creator.orders.index') }}" class="quick-action-item secondary">
                <div class="quick-action-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="quick-action-content">
                    <div class="quick-action-title">Mes Commandes</div>
                    <div class="quick-action-desc">Suivre et gérer vos ventes</div>
                </div>
                <i class="fas fa-chevron-right quick-action-arrow"></i>
            </a>

            <a href="{{ '#' }}" class="quick-action-item success">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="quick-action-content">
                    <div class="quick-action-title">Statistiques</div>
                    <div class="quick-action-desc">Analyser vos performances</div>
                </div>
                <i class="fas fa-chevron-right quick-action-arrow"></i>
            </a>

            <a href="{{ route('profile.edit') }}" class="quick-action-item warning">
                <div class="quick-action-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="quick-action-content">
                    <div class="quick-action-title">Mon Profil</div>
                    <div class="quick-action-desc">Modifier vos informations</div>
                </div>
                <i class="fas fa-chevron-right quick-action-arrow"></i>
            </a>
        </div>
    </div>

    {{-- RECENT PRODUCTS --}}
    @if(isset($recentProducts) && $recentProducts->count() > 0)
    <div class="orders-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-box"></i>
                Produits Récents
            </h3>
            <a href="{{ route('creator.products.index') }}" class="section-link">
                Voir tout <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="products-grid">
            @foreach($recentProducts as $product)
            <a href="{{ route('creator.products.index') }}?product={{ $product->id }}" class="product-card">
                <div class="product-image-wrapper">
                    @if($product->main_image)
                        <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->title }}">
                    @else
                        <i class="fas fa-image" style="font-size: 2rem; color: #ddd;"></i>
                    @endif
                </div>
                <h4 class="product-title">{{ Str::limit($product->title ?? 'Produit', 30) }}</h4>
                <p class="product-price">{{ number_format($product->price ?? 0, 0, ',', ' ') }} FCFA</p>
                <div class="product-badges">
                    <span class="product-badge {{ ($product->is_active ?? false) ? 'active' : 'inactive' }}">
                        <i class="fas fa-{{ ($product->is_active ?? false) ? 'check' : 'times' }}"></i>
                        {{ ($product->is_active ?? false) ? 'Actif' : 'Inactif' }}
                    </span>
                    <span class="product-badge stock">
                        <i class="fas fa-box"></i>
                        Stock: {{ $product->stock ?? 0 }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection