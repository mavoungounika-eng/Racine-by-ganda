@extends('layouts.frontend')

@section('title', 'Mon Compte - RACINE BY GANDA')

@push('styles')
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4A574' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .dashboard-hero-content {
        position: relative;
        z-index: 1;
    }
    
    .user-avatar-lg {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 24px rgba(237, 95, 30, 0.4);
        transition: transform 0.3s;
        border: 3px solid rgba(212, 165, 116, 0.3);
    }
    
    .user-avatar-lg:hover {
        transform: scale(1.05);
    }
    
    .user-avatar-lg span {
        color: white;
        font-size: 2.75rem;
        font-weight: 700;
        font-family: 'Playfair Display', serif;
    }
    
    .dashboard-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    /* STATS CARDS - Grid moderne */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
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
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }
    
    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        background: linear-gradient(135deg, var(--stat-color-1), var(--stat-color-2));
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .stat-card.orders { --stat-color-1: #ED5F1E; --stat-color-2: #c44b12; }
    .stat-card.pending { --stat-color-1: #FFB800; --stat-color-2: #d99a00; }
    .stat-card.completed { --stat-color-1: #22C55E; --stat-color-2: #15803D; }
    .stat-card.spent { --stat-color-1: #8B5A2B; --stat-color-2: #6B4423; }
    
    .stat-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8B7355;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2C1810;
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    
    .stat-subtitle {
        font-size: 0.9rem;
        color: #8B7355;
        margin-top: 0.5rem;
    }
    
    /* MAIN CONTENT GRID */
    .dashboard-main-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
    }
    
    /* ORDERS SECTION */
    .orders-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .section-header {
        padding: 1.75rem 2rem;
        border-bottom: 2px solid #f0f0f0;
        background: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .section-title i {
        color: #ED5F1E;
        font-size: 1.35rem;
    }
    
    .section-link {
        color: #ED5F1E;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .section-link:hover {
        color: #c44b12;
        gap: 0.75rem;
    }
    
    .orders-table {
        width: 100%;
    }
    
    .orders-table thead {
        background: #f8f9fa;
    }
    
    .orders-table th {
        padding: 1.25rem 2rem;
        font-weight: 600;
        color: #2C1810;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .orders-table td {
        padding: 1.25rem 2rem;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    
    .orders-table tbody tr {
        transition: background 0.2s;
    }
    
    .orders-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .order-id {
        font-weight: 600;
        color: #2C1810;
        font-size: 1rem;
    }
    
    .order-date {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    .order-items-count {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    .order-amount {
        font-weight: 600;
        color: #ED5F1E;
        font-size: 1.1rem;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.85rem;
        display: inline-block;
        border: 1px solid;
    }
    
    .order-action-btn {
        background: rgba(237, 95, 30, 0.1);
        color: #ED5F1E;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .order-action-btn:hover {
        background: #ED5F1E;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }
    
    /* SIDEBAR */
    .dashboard-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    /* LOYALTY CARD */
    .loyalty-card {
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 24px rgba(212, 165, 116, 0.3);
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .loyalty-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    }
    
    .loyalty-content {
        position: relative;
        z-index: 1;
        text-align: center;
    }
    
    .loyalty-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2.5rem;
        backdrop-filter: blur(10px);
    }
    
    .loyalty-points {
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.5rem;
    }
    
    .loyalty-label {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 1rem;
    }
    
    .loyalty-tier {
        display: inline-block;
        background: rgba(255,255,255,0.25);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        backdrop-filter: blur(10px);
    }
    
    .loyalty-btn {
        width: 100%;
        background: white;
        color: #8B5A2B;
        border: none;
        border-radius: 12px;
        padding: 1rem;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .loyalty-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        color: #6B4423;
    }
    
    /* QUICK ACTIONS */
    .quick-actions-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .quick-action-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .quick-action-item:last-child {
        border-bottom: none;
    }
    
    .quick-action-item:hover {
        background: #f8f9fa;
        transform: translateX(4px);
    }
    
    .quick-action-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .quick-action-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
    }
    
    .quick-action-icon.shop { background: linear-gradient(135deg, #ED5F1E, #c44b12); }
    .quick-action-icon.cart { background: linear-gradient(135deg, #FFB800, #d99a00); }
    .quick-action-icon.profile { background: linear-gradient(135deg, #8B5A2B, #6B4423); }
    .quick-action-icon.addresses { background: linear-gradient(135deg, #22C55E, #15803D); }
    .quick-action-icon.orders { background: linear-gradient(135deg, #8B5A2B, #6B4423); }
    .quick-action-icon.loyalty { background: linear-gradient(135deg, #D4A574, #8B5A2B); }
    
    .quick-action-text {
        font-weight: 500;
        color: #2C1810;
        font-size: 0.95rem;
    }
    
    .quick-action-arrow {
        color: #8B7355;
        font-size: 0.9rem;
        transition: transform 0.3s;
    }
    
    .quick-action-item:hover .quick-action-arrow {
        transform: translateX(4px);
        color: #ED5F1E;
    }
    
    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }
    
    .empty-state-icon {
        font-size: 5rem;
        color: #ddd;
        margin-bottom: 1.5rem;
    }
    
    .empty-state-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .empty-state-text {
        color: #8B7355;
        margin-bottom: 2rem;
    }
    
    .empty-state-btn {
        background: #ED5F1E;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .empty-state-btn:hover {
        background: #c44b12;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        color: white;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .dashboard-main-grid {
            grid-template-columns: 1fr;
        }
        
        .dashboard-sidebar {
            order: -1;
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
        }
        
        .orders-table {
            font-size: 0.85rem;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 1rem;
        }
        
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .orders-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="dashboard-hero">
    <div class="container">
        <div class="dashboard-hero-content">
            <div class="d-flex align-items-center flex-wrap" style="gap: 2rem;">
                <div class="user-avatar-lg">
                    <span>{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div>
                    <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
                        Bienvenue, {{ $user->name ?? 'Client' }}
                    </h1>
                    <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.8); margin: 0;">
                        Gérez vos commandes, suivez vos achats et profitez de vos avantages
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DASHBOARD CONTENT -->
<section class="dashboard-content">
    <div class="container">
        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card orders">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-label">Total Commandes</div>
                        <div class="stat-value">{{ $stats['my_orders_total'] ?? 0 }}</div>
                        <div class="stat-subtitle">Toutes vos commandes</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-label">En Attente</div>
                        <div class="stat-value">{{ $stats['my_orders_pending'] ?? 0 }}</div>
                        <div class="stat-subtitle">En cours de traitement</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card completed">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-label">Complétées</div>
                        <div class="stat-value">{{ $stats['my_orders_completed'] ?? 0 }}</div>
                        <div class="stat-subtitle">Commandes livrées</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card spent">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-label">Total Dépensé</div>
                        <div class="stat-value" style="font-size: 1.75rem;">
                            {{ number_format($stats['total_spent'] ?? 0, 0, ',', ' ') }}<small style="font-size: 0.6em;"> FCFA</small>
                        </div>
                        <div class="stat-subtitle">Montant total payé</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT GRID -->
        <div class="dashboard-main-grid">
            <!-- COLONNE GAUCHE : Commandes Récentes -->
            <div class="orders-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-shopping-bag"></i>
                        Mes Commandes Récentes
                    </h2>
                    <a href="{{ route('profile.orders') }}" class="section-link">
                        Voir tout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="orders-content">
                    @if($my_orders && $my_orders->count() > 0)
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Date</th>
                                    <th>Articles</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($my_orders as $order)
                                <tr>
                                    <td>
                                        <span class="order-id">#{{ $order->id }}</span>
                                    </td>
                                    <td>
                                        <span class="order-date">{{ $order->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="order-items-count">{{ $order->items->count() }} article(s)</span>
                                    </td>
                                    <td>
                                        <span class="order-amount">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusConfig = [
                                                'pending' => ['label' => 'En attente', 'color' => '#FFB800', 'bg' => 'rgba(255, 184, 0, 0.1)'],
                                                'processing' => ['label' => 'En traitement', 'color' => '#FFB800', 'bg' => 'rgba(255, 184, 0, 0.1)'],
                                                'paid' => ['label' => 'Payée', 'color' => '#0EA5E9', 'bg' => 'rgba(14, 165, 233, 0.1)'],
                                                'shipped' => ['label' => 'Expédiée', 'color' => '#0EA5E9', 'bg' => 'rgba(14, 165, 233, 0.1)'],
                                                'completed' => ['label' => 'Complétée', 'color' => '#22C55E', 'bg' => 'rgba(34, 197, 94, 0.1)'],
                                                'delivered' => ['label' => 'Livrée', 'color' => '#22C55E', 'bg' => 'rgba(34, 197, 94, 0.1)'],
                                                'cancelled' => ['label' => 'Annulée', 'color' => '#DC2626', 'bg' => 'rgba(220, 38, 38, 0.1)'],
                                                'failed' => ['label' => 'Échouée', 'color' => '#DC2626', 'bg' => 'rgba(220, 38, 38, 0.1)'],
                                            ];
                                            $status = $statusConfig[$order->status] ?? ['label' => ucfirst($order->status), 'color' => '#6c757d', 'bg' => 'rgba(108, 117, 125, 0.1)'];
                                        @endphp
                                        <span class="status-badge" style="background: {{ $status['bg'] }}; color: {{ $status['color'] }}; border-color: {{ $status['color'] }}40;">
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('profile.orders.show', $order) }}" class="order-action-btn">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <h3 class="empty-state-title">Aucune commande</h3>
                            <p class="empty-state-text">Vous n'avez pas encore passé de commande. Découvrez nos créations uniques !</p>
                            <a href="{{ route('frontend.shop') }}" class="empty-state-btn">
                                <i class="fas fa-store"></i> Découvrir la boutique
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- COLONNE DROITE : Fidélité + Actions Rapides -->
            <div class="dashboard-sidebar">
                <!-- CARTE FIDÉLITÉ -->
                @if($loyalty)
                <div class="loyalty-card">
                    <div class="loyalty-content">
                        <div class="loyalty-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="loyalty-points">
                            {{ number_format($loyalty->points ?? 0, 0, ',', ' ') }}
                        </div>
                        <div class="loyalty-label">Points de fidélité</div>
                        @php
                            $tierColors = [
                                'bronze' => '#cd7f32',
                                'silver' => '#c0c0c0',
                                'gold' => '#ffd700',
                            ];
                            $tierNames = [
                                'bronze' => 'Bronze',
                                'silver' => 'Silver',
                                'gold' => 'Gold',
                            ];
                        @endphp
                        <span class="loyalty-tier" style="background: {{ $tierColors[$loyalty->tier ?? 'bronze'] }};">
                            Niveau {{ $tierNames[$loyalty->tier ?? 'bronze'] }}
                        </span>
                        <a href="{{ route('profile.loyalty') }}" class="loyalty-btn">
                            <i class="fas fa-gift"></i> Voir mes avantages
                        </a>
                    </div>
                </div>
                @endif

                <!-- ACTIONS RAPIDES -->
                <div class="quick-actions-card">
                    <div class="section-header">
                        <h2 class="section-title" style="font-size: 1.25rem;">
                            <i class="fas fa-bolt"></i>
                            Actions Rapides
                        </h2>
                    </div>
                    <div class="quick-actions-list">
                        <a href="{{ route('frontend.shop') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon shop">
                                    <i class="fas fa-store"></i>
                                </div>
                                <span class="quick-action-text">Boutique</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('cart.index') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon cart">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <span class="quick-action-text">Mon Panier</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('profile.index') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon profile">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <span class="quick-action-text">Mon Profil</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('profile.addresses') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon addresses">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span class="quick-action-text">Mes Adresses</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('profile.orders') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon orders">
                                    <i class="fas fa-list-alt"></i>
                                </div>
                                <span class="quick-action-text">Toutes mes commandes</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('messages.index') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon" style="background: rgba(75, 29, 242, 0.1); color: #4B1DF2;">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <span class="quick-action-text">Messagerie</span>
                                @php
                                    $unreadCount = app(\App\Services\ConversationService::class)->getUnreadConversationsCount(auth()->id());
                                @endphp
                                @if($unreadCount > 0)
                                    <span class="badge badge-primary ml-2">{{ $unreadCount }}</span>
                                @endif
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        @if($loyalty)
                        <a href="{{ route('profile.loyalty') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon loyalty">
                                    <i class="fas fa-star"></i>
                                </div>
                                <span class="quick-action-text">Mes points de fidélité</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>
                        @endif

                        <a href="{{ route('profile.wishlist') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #DC2626, #991B1B);">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <span class="quick-action-text">Mes favoris</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('notifications.index') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #0EA5E9, #0369A1);">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <span class="quick-action-text">Mes notifications</span>
                                @if(isset($unreadCount) && $unreadCount > 0)
                                <span style="background: #ED5F1E; color: white; padding: 0.25rem 0.5rem; border-radius: 50%; font-size: 0.75rem; margin-left: 0.5rem;">
                                    {{ $unreadCount }}
                                </span>
                                @endif
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>

                        <a href="{{ route('profile.reviews') }}" class="quick-action-item">
                            <div class="quick-action-left">
                                <div class="quick-action-icon" style="background: linear-gradient(135deg, #FFB800, #d99a00);">
                                    <i class="fas fa-star"></i>
                                </div>
                                <span class="quick-action-text">Mes avis</span>
                            </div>
                            <i class="fas fa-chevron-right quick-action-arrow"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Accueil', 'url' => route('frontend.home')],
        ['label' => 'Mon Compte', 'url' => null],
    ],
    'backUrl' => route('frontend.home'),
    'backText' => 'Retour à l\'accueil',
    'position' => 'bottom',
])
@endsection
