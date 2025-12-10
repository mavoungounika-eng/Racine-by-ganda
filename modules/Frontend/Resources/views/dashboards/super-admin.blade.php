@extends('layouts.frontend')

@section('title', 'Dashboard CEO - RACINE BY GANDA')

@section('content')
<div class="py-5" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh;">
    <div class="container">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h1 class="h2 mb-1 text-white">👑 Dashboard CEO</h1>
                        <p class="text-white-50 mb-0">Vue d'ensemble • {{ now()->format('l d F Y') }}</p>
                    </div>
                    <div>
                        <span class="badge py-2 px-3" style="background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);">
                            Super Admin
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIs Principaux --}}
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <p class="small mb-1 opacity-75">💰 Revenus Totaux</p>
                        <h2 class="mb-0">{{ number_format($stats['orders_revenue'], 0, ',', ' ') }}</h2>
                        <small class="opacity-75">FCFA</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="card-body text-white">
                        <p class="small mb-1 opacity-75">📦 Commandes</p>
                        <h2 class="mb-0">{{ $stats['orders_total'] }}</h2>
                        <small class="opacity-75">{{ $stats['orders_completed'] }} livrées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);">
                    <div class="card-body text-white">
                        <p class="small mb-1 opacity-75">👥 Utilisateurs</p>
                        <h2 class="mb-0">{{ $stats['users_total'] }}</h2>
                        <small class="opacity-75">{{ $stats['users_clients'] }} clients</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);">
                    <div class="card-body text-white">
                        <p class="small mb-1 opacity-75">👗 Produits</p>
                        <h2 class="mb-0">{{ $stats['products_total'] }}</h2>
                        <small class="opacity-75">{{ $stats['products_active'] }} actifs</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Détaillées --}}
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="text-white-50 text-uppercase mb-3">📊 Répartition</h6>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-dark text-white h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="text-white-50 mb-0">👥 Utilisateurs par rôle</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Clients</span>
                            <span class="badge bg-primary">{{ $stats['users_clients'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Créateurs</span>
                            <span class="badge bg-success">{{ $stats['users_createurs'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Staff</span>
                            <span class="badge bg-info">{{ $stats['users_staff'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Admins</span>
                            <span class="badge bg-warning">{{ $stats['users_admins'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-dark text-white h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="text-white-50 mb-0">📦 Statut Commandes</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>En attente</span>
                            <span class="badge bg-warning">{{ $stats['orders_pending'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Complétées</span>
                            <span class="badge bg-success">{{ $stats['orders_completed'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total</span>
                            <span class="badge bg-light text-dark">{{ $stats['orders_total'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-dark text-white h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="text-white-50 mb-0">👗 Statut Produits</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Actifs</span>
                            <span class="badge bg-success">{{ $stats['products_active'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Stock faible</span>
                            <span class="badge bg-warning">{{ $stats['products_low_stock'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Rupture</span>
                            <span class="badge bg-danger">{{ $stats['products_out_of_stock'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Dernières Commandes --}}
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm bg-dark text-white">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0">📦 Dernières Commandes</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($recent_orders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="border-0">#</th>
                                            <th class="border-0">Client</th>
                                            <th class="border-0">Montant</th>
                                            <th class="border-0">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recent_orders as $order)
                                        <tr>
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->user->name ?? 'N/A' }}</td>
                                            <td>{{ number_format($order->total_amount, 0, ',', ' ') }}</td>
                                            <td>
                                                @switch($order->status)
                                                    @case('pending')
                                                        <span class="badge bg-warning">En attente</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge bg-success">Livrée</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $order->status }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucune commande récente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Derniers Utilisateurs --}}
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm bg-dark text-white">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0">👥 Derniers Inscrits</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($recent_users->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="border-0">Nom</th>
                                            <th class="border-0">Email</th>
                                            <th class="border-0">Rôle</th>
                                            <th class="border-0">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recent_users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td class="small">{{ Str::limit($user->email, 20) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $user->role === 'client' ? 'primary' : ($user->role === 'createur' ? 'success' : 'info') }}">
                                                    {{ $user->role }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ $user->created_at->diffForHumans() }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucun utilisateur récent</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Accès Rapides CEO --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-dark">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <a href="{{ route('admin.dashboard') }}" class="text-white text-decoration-none">
                                    <div class="p-3">
                                        <span class="display-4">🎛️</span>
                                        <p class="mt-2 mb-0">Back-Office</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <a href="{{ route('admin.users.index') }}" class="text-white text-decoration-none">
                                    <div class="p-3">
                                        <span class="display-4">👥</span>
                                        <p class="mt-2 mb-0">Utilisateurs</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="{{ route('admin.orders.index') }}" class="text-white text-decoration-none">
                                    <div class="p-3">
                                        <span class="display-4">📦</span>
                                        <p class="mt-2 mb-0">Commandes</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="{{ route('admin.products.index') }}" class="text-white text-decoration-none">
                                    <div class="p-3">
                                        <span class="display-4">👗</span>
                                        <p class="mt-2 mb-0">Produits</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
