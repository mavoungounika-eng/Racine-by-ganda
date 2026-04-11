@extends('layouts.frontend')

@section('title', 'Dashboard Admin - RACINE BY GANDA')

@section('content')
<div class="py-5 bg-light">
    <div class="container">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h1 class="h2 mb-1">??? Dashboard Admin</h1>
                        <p class="text-muted mb-0">{{ now()->format('l d F Y') }} • {{ Auth::user()->name }}</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-dark">
                            <span class="icon-cog mr-2"></span> Back-Office Complet
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Row 1 - Commandes --}}
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="text-muted text-uppercase mb-3">?? Commandes</h6>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="small mb-1 opacity-75">Aujourd'hui</p>
                                <h2 class="mb-0">{{ $stats['orders_today'] }}</h2>
                            </div>
                            <span class="h1 opacity-50">??</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">En attente</p>
                                <h2 class="mb-0 text-warning">{{ $stats['orders_pending'] }}</h2>
                            </div>
                            <span class="h1 text-warning opacity-50">?</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">En cours</p>
                                <h2 class="mb-0 text-info">{{ $stats['orders_processing'] }}</h2>
                            </div>
                            <span class="h1 text-info opacity-50">??</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100 bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="small mb-1 opacity-75">CA Aujourd'hui</p>
                                <h4 class="mb-0">{{ number_format($stats['revenue_today'], 0, ',', ' ') }}</h4>
                                <small class="opacity-75">FCFA</small>
                            </div>
                            <span class="h1 opacity-50">??</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Row 2 - Global --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">CA du mois</p>
                                <h3 class="mb-0 text-success">{{ number_format($stats['revenue_month'], 0, ',', ' ') }}</h3>
                                <small class="text-muted">FCFA</small>
                            </div>
                            <span class="h1 text-success opacity-25">??</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Produits</p>
                                <h3 class="mb-0">{{ $stats['products_total'] }}</h3>
                                <small class="text-danger">{{ $stats['products_low_stock'] }} stock faible</small>
                            </div>
                            <span class="h1 opacity-25">??</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Utilisateurs</p>
                                <h3 class="mb-0">{{ $stats['users_total'] }}</h3>
                                <small class="text-success">+{{ $stats['new_clients_today'] }} aujourd'hui</small>
                            </div>
                            <span class="h1 opacity-25">??</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Commandes en attente --}}
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">? Commandes en attente</h5>
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body p-0">
                        @if($pending_orders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0">#</th>
                                            <th class="border-0">Client</th>
                                            <th class="border-0">Montant</th>
                                            <th class="border-0">Paiement</th>
                                            <th class="border-0">Date</th>
                                            <th class="border-0"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pending_orders as $order)
                                        <tr>
                                            <td class="align-middle"><strong>{{ $order->id }}</strong></td>
                                            <td class="align-middle">{{ $order->user->name ?? $order->customer_name ?? 'N/A' }}</td>
                                            <td class="align-middle">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</td>
                                            <td class="align-middle">
                                                @if($order->payment_status === 'paid')
                                                    <span class="badge bg-success">Payé</span>
                                                @else
                                                    <span class="badge bg-warning">En attente</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-muted small">{{ $order->created_at->diffForHumans() }}</td>
                                            <td class="align-middle">
                                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">
                                                    Voir
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <span class="display-1">?</span>
                                <h5 class="text-muted mt-3">Aucune commande en attente</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions Rapides --}}
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">? Actions Rapides</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-primary btn-block mb-2">
                            ?? Gérer les commandes
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary btn-block mb-2">
                            ?? Gérer les produits
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-block mb-2">
                            ?? Gérer les utilisateurs
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-block">
                            ?? Gérer les catégories
                        </a>
                    </div>
                </div>

                {{-- Alertes --}}
                @if($stats['products_low_stock'] > 0)
                <div class="card border-0 shadow-sm bg-warning">
                    <div class="card-body">
                        <h5 class="mb-2">?? Alertes Stock</h5>
                        <p class="mb-0 small">
                            <strong>{{ $stats['products_low_stock'] }}</strong> produit(s) avec stock faible.
                            <a href="{{ route('admin.products.index') }}" class="text-dark font-weight-bold">Voir ?</a>
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
