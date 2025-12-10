@extends('layouts.admin')

@section('title', 'Tableau de Bord Admin - RACINE BY GANDA')
@section('page_title', 'Tableau de bord')
@section('page_subtitle', "Vue d'ensemble de l'activité RACINE")

@section('content')

{{-- Statistiques principales --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Ventes totales',
            'value' => number_format($stats['monthly_sales'] ?? 0, 0, ',', ' ') . ' FCFA',
            'icon' => 'fas fa-wallet',
            'color' => 'success',
            'trend' => isset($stats['monthly_sales_evolution']) && $stats['monthly_sales_evolution'] != 0 ? ['value' => '+' . abs($stats['monthly_sales_evolution']) . '% ce mois', 'direction' => 'up', 'color' => '#22C55E'] : null
        ])
    </div>

    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Commandes',
            'value' => $stats['monthly_orders'] ?? 0,
            'icon' => 'fas fa-shopping-bag',
            'color' => 'info',
            'subtitle' => 'Dont ' . ($stats['pending_orders'] ?? 0) . ' en attente'
        ])
    </div>

    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Clients',
            'value' => $stats['total_clients'] ?? 0,
            'icon' => 'fas fa-user-friends',
            'color' => 'warning',
            'subtitle' => '+' . ($stats['new_clients_month'] ?? 0) . ' nouveaux ce mois'
        ])
    </div>

    <div class="col-lg-3 col-md-6">
        @include('partials.admin.stat-card', [
            'title' => 'Produits actifs',
            'value' => $stats['total_products'] ?? 0,
            'icon' => 'fas fa-box',
            'color' => 'primary',
            'subtitle' => 'Stock faible: ' . ($stats['low_stock_products'] ?? 0)
        ])
    </div>
</div>

{{-- Graphiques --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line text-racine-orange me-2"></i>
                    Ventes par mois
                </h5>
                <span class="badge badge-racine-orange">12 derniers mois</span>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-bar text-racine-orange me-2"></i>
                    Commandes par mois
                </h5>
                <span class="badge badge-racine-orange">12 derniers mois</span>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tableau des dernières commandes et actions rapides --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-shopping-cart text-racine-orange me-2"></i>
                    Commandes récentes
                </h5>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-racine-orange">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @if(!empty($recentActivity['recent_orders']) && count($recentActivity['recent_orders']))
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Commande</th>
                                <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Date</th>
                                <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Client</th>
                                <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Total</th>
                                <th class="text-uppercase small fw-bold text-muted" style="font-size: 0.75rem;">Statut</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recentActivity['recent_orders'] as $order)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-racine-black">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                                    </td>
                                    <td class="text-muted">{{ $order->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->user->name ?? 'Client' }}</div>
                                        @if($order->user->email ?? null)
                                            <div class="small text-muted">{{ $order->user->email }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-racine-orange">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill
                                            @if($order->status === 'pending') bg-warning text-dark
                                            @elseif($order->status === 'paid') bg-success
                                            @elseif($order->status === 'shipped') bg-info
                                            @elseif($order->status === 'completed') bg-success
                                            @else bg-secondary @endif">
                                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-receipt fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Aucune commande récente.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions rapides --}}
    <div class="col-lg-4">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-bolt text-racine-orange me-2"></i>
                    Actions rapides
                </h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.products.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <span>
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        <span class="fw-semibold">Ajouter un produit</span>
                    </span>
                    <i class="fas fa-chevron-right small text-muted"></i>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <span>
                        <i class="fas fa-tags text-warning me-2"></i>
                        <span class="fw-semibold">Gérer les catégories</span>
                    </span>
                    <i class="fas fa-chevron-right small text-muted"></i>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <span>
                        <i class="fas fa-receipt text-primary me-2"></i>
                        <span class="fw-semibold">Voir les commandes</span>
                    </span>
                    <i class="fas fa-chevron-right small text-muted"></i>
                </a>
                <a href="{{ route('messages.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <span>
                        <i class="fas fa-comments text-info me-2"></i>
                        <span class="fw-semibold">Messagerie</span>
                        @php
                            $unreadCount = app(\App\Services\ConversationService::class)->getUnreadConversationsCount(auth()->id());
                        @endphp
                        @if($unreadCount > 0)
                            <span class="badge bg-primary ms-2">{{ $unreadCount }}</span>
                        @endif
                    </span>
                    <i class="fas fa-chevron-right small text-muted"></i>
                </a>
                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <span>
                        <i class="fas fa-users text-info me-2"></i>
                        <span class="fw-semibold">Gérer les utilisateurs</span>
                    </span>
                    <i class="fas fa-chevron-right small text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Nouveaux clients et produits récents --}}
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-user-plus text-racine-orange me-2"></i>
                    Nouveaux clients
                </h5>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-racine-orange">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                @if(!empty($recentActivity['new_users']) && count($recentActivity['new_users']))
                    <div class="list-group list-group-flush">
                        @foreach($recentActivity['new_users'] as $user)
                            <div class="list-group-item d-flex align-items-center gap-3 border-0 py-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:48px;height:48px;background:linear-gradient(135deg,#ED5F1E,#FFB800);color:#160D0C;font-weight:bold;font-size:1.1rem;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-racine-black">{{ $user->name }}</div>
                                    <div class="small text-muted">{{ $user->email }}</div>
                                </div>
                                <div class="small text-muted text-end">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $user->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Aucun nouveau client.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-box text-racine-orange me-2"></i>
                    Produits récents
                </h5>
                <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-racine-orange">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                @if(!empty($recentActivity['recent_products']) && count($recentActivity['recent_products']))
                    <div class="list-group list-group-flush">
                        @foreach($recentActivity['recent_products'] as $product)
                            <div class="list-group-item d-flex align-items-center gap-3 border-0 py-3">
                                @if($product->main_image)
                                    <img src="{{ asset('storage/' . $product->main_image) }}"
                                         alt="{{ $product->title }}"
                                         class="rounded flex-shrink-0"
                                         style="width:56px;height:56px;object-fit:cover;border:2px solid var(--racine-beige);">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center bg-light flex-shrink-0"
                                         style="width:56px;height:56px;border:2px solid var(--racine-beige);">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                                <div class="flex-fill">
                                    <div class="fw-bold text-racine-black">{{ $product->title }}</div>
                                    <div class="small text-muted">
                                        <i class="fas fa-tag me-1"></i>
                                        {{ $product->category->name ?? 'Sans catégorie' }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-racine-orange">{{ number_format($product->price, 0, ',', ' ') }} FCFA</div>
                                    <div class="small text-muted">
                                        <i class="fas fa-warehouse me-1"></i>
                                        Stock: {{ $product->stock }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-box fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Aucun produit récent.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration globale Chart.js
    Chart.defaults.font.family = "'Aileron', system-ui, sans-serif";
    Chart.defaults.color = '#666666';

    const luxeColors = {
        primary: '#ED5F1E',
        secondary: '#FFB800',
        gray: '#8B7355'
    };

    // Graphique Ventes par Mois
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($chartData['salesByMonth']['labels'] ?? []),
                datasets: [{
                    label: 'Ventes (FCFA)',
                    data: @json($chartData['salesByMonth']['data'] ?? []),
                    borderColor: luxeColors.primary,
                    backgroundColor: 'rgba(237, 95, 30, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: luxeColors.primary,
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' F';
                            }
                        }
                    }
                }
            }
        });
    }

    // Graphique Commandes par Mois
    const ordersCtx = document.getElementById('ordersChart');
    if (ordersCtx) {
        new Chart(ordersCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($chartData['ordersByMonth']['labels'] ?? []),
                datasets: [{
                    label: 'Commandes',
                    data: @json($chartData['ordersByMonth']['data'] ?? []),
                    backgroundColor: luxeColors.secondary,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
