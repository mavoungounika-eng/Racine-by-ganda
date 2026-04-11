@extends('layouts.frontend')

@section('title', 'Mon Espace - RACINE BY GANDA')

@push('styles')
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .dashboard-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        color: white;
    }
    
    .dashboard-hero p {
        color: rgba(255, 255, 255, 0.7);
    }
    
    .dashboard-content {
        padding: 2rem 0 4rem;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2rem;
    }
    
    /* SIDEBAR */
    .dashboard-sidebar {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        height: fit-content;
    }
    
    .user-profile {
        text-align: center;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #E5DDD3;
        margin-bottom: 1.5rem;
    }
    
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        margin: 0 auto 1rem;
    }
    
    .user-name {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.25rem;
    }
    
    .user-email {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-menu li {
        margin-bottom: 0.5rem;
    }
    
    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #5C4A3D;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.3s;
    }
    
    .sidebar-menu a:hover, .sidebar-menu a.active {
        background: rgba(212, 165, 116, 0.1);
        color: #8B5A2B;
    }
    
    .sidebar-menu a.active {
        font-weight: 600;
    }
    
    .sidebar-menu i {
        width: 20px;
        text-align: center;
    }
    
    .logout-btn {
        width: 100%;
        margin-top: 1rem;
        padding: 0.75rem;
        background: transparent;
        border: 1.5px solid #E5DDD3;
        border-radius: 10px;
        color: #8B7355;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .logout-btn:hover {
        background: #E53E3E;
        border-color: #E53E3E;
        color: white;
    }
    
    /* MAIN CONTENT */
    .dashboard-main {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    /* STATS CARDS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-icon.orders { background: rgba(99, 102, 241, 0.1); color: #6366F1; }
    .stat-icon.pending { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
    .stat-icon.completed { background: rgba(34, 197, 94, 0.1); color: #22C55E; }
    .stat-icon.spent { background: rgba(212, 165, 116, 0.1); color: #8B5A2B; }
    
    .stat-info h3 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2C1810;
        margin-bottom: 0.25rem;
    }
    
    .stat-info span {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    /* SECTIONS */
    .dashboard-section {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
    }
    
    .section-link {
        color: #8B5A2B;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .section-link:hover {
        text-decoration: underline;
    }
    
    /* ORDERS TABLE */
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th {
        text-align: left;
        padding: 1rem;
        color: #8B7355;
        font-weight: 500;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .orders-table td {
        padding: 1rem;
        color: #2C1810;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .orders-table tr:last-child td {
        border-bottom: none;
    }
    
    .order-number {
        font-weight: 600;
        color: #8B5A2B;
    }
    
    .order-status {
        display: inline-flex;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .status-pending { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
    .status-processing { background: rgba(99, 102, 241, 0.1); color: #6366F1; }
    .status-completed { background: rgba(34, 197, 94, 0.1); color: #22C55E; }
    .status-cancelled { background: rgba(239, 68, 68, 0.1); color: #EF4444; }
    
    .order-action {
        color: #8B5A2B;
        text-decoration: none;
        font-weight: 500;
    }
    
    .order-action:hover {
        text-decoration: underline;
    }
    
    /* QUICK ACTIONS */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    .quick-action-card {
        background: #F8F6F3;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .quick-action-card:hover {
        background: rgba(212, 165, 116, 0.1);
        transform: translateY(-4px);
    }
    
    .quick-action-card i {
        font-size: 2rem;
        color: #D4A574;
        margin-bottom: 0.75rem;
    }
    
    .quick-action-card h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.25rem;
    }
    
    .quick-action-card p {
        font-size: 0.85rem;
        color: #8B7355;
        margin: 0;
    }
    
    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 3rem;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #E5DDD3;
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #8B7355;
        margin-bottom: 1.5rem;
    }
    
    .btn-shop {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: #2C1810;
        color: white;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-shop:hover {
        background: #8B5A2B;
        color: white;
    }
    
    @media (max-width: 1024px) {
        .dashboard-grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .quick-actions { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="dashboard-hero">
    <div class="container">
        <h1>Bonjour, {{ auth()->user()->name ?? 'Client' }} 👋</h1>
        <p>Bienvenue dans votre espace personnel RACINE</p>
    </div>
</section>

<!-- DASHBOARD -->
<section class="dashboard-content">
    <div class="container">
        <div class="dashboard-grid">
            <!-- SIDEBAR -->
            <aside class="dashboard-sidebar">
                <div class="user-profile">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <h3 class="user-name">{{ auth()->user()->name ?? 'Utilisateur' }}</h3>
                    <p class="user-email">{{ auth()->user()->email ?? 'email@example.com' }}</p>
                </div>
                
                <ul class="sidebar-menu">
                    <li><a href="#" class="active"><i class="fas fa-home"></i> Tableau de bord</a></li>
                    <li><a href="#"><i class="fas fa-shopping-bag"></i> Mes commandes</a></li>
                    <li><a href="#"><i class="fas fa-heart"></i> Mes favoris</a></li>
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> Adresses</a></li>
                    <li><a href="{{ route('profile.index') }}"><i class="fas fa-user-cog"></i> Mon profil</a></li>
                    <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
                </ul>
                
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                    </button>
                </form>
            </aside>
            
            <!-- MAIN -->
            <div class="dashboard-main">
                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon orders"><i class="fas fa-shopping-bag"></i></div>
                        <div class="stat-info">
                            <h3>{{ $stats['my_orders_total'] ?? 0 }}</h3>
                            <span>Commandes</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3>{{ $stats['my_orders_pending'] ?? 0 }}</h3>
                            <span>En cours</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon completed"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3>{{ $stats['my_orders_completed'] ?? 0 }}</h3>
                            <span>Livrées</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon spent"><i class="fas fa-coins"></i></div>
                        <div class="stat-info">
                            <h3>{{ number_format($stats['total_spent'] ?? 0, 0) }}€</h3>
                            <span>Total dépensé</span>
                        </div>
                    </div>
                </div>
                
                <!-- RECENT ORDERS -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2 class="section-title">Commandes récentes</h2>
                        <a href="#" class="section-link">Voir tout →</a>
                    </div>
                    
                    @if(($my_orders ?? collect())->count() > 0)
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($my_orders as $order)
                            <tr>
                                <td><span class="order-number">#{{ $order->id }}</span></td>
                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                <td>{{ number_format($order->total_amount, 2) }} €</td>
                                <td>
                                    <span class="order-status status-{{ $order->status }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td><a href="#" class="order-action">Détails</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>Aucune commande</h3>
                        <p>Vous n'avez pas encore passé de commande.</p>
                        <a href="{{ route('frontend.shop') }}" class="btn-shop">
                            <i class="fas fa-store"></i> Découvrir la boutique
                        </a>
                    </div>
                    @endif
                </div>
                
                <!-- QUICK ACTIONS -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2 class="section-title">Actions rapides</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="{{ route('frontend.shop') }}" class="quick-action-card">
                            <i class="fas fa-store"></i>
                            <h4>Boutique</h4>
                            <p>Découvrir les collections</p>
                        </a>
                        <a href="{{ route('cart.index') }}" class="quick-action-card">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>Mon panier</h4>
                            <p>Voir mon panier</p>
                        </a>
                        <a href="{{ route('profile.index') }}" class="quick-action-card">
                            <i class="fas fa-user-edit"></i>
                            <h4>Mon profil</h4>
                            <p>Modifier mes informations</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
