@extends('layouts.frontend')

@section('title', 'Espace Staff - RACINE BY GANDA')

@section('content')
<div class="py-5 bg-light">
    <div class="container">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h1 class="h2 mb-1">🛠️ Espace Staff</h1>
                        <p class="text-muted mb-0">Bonjour {{ Auth::user()->name }} • {{ now()->format('l d F Y') }}</p>
                    </div>
                    <div>
                        <span class="badge bg-info py-2 px-3">
                            {{ Auth::user()->staff_role ?? 'Staff' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100 border-left-warning" style="border-left: 4px solid #ffc107 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">En attente</p>
                                <h3 class="mb-0">{{ $stats['orders_pending'] }}</h3>
                            </div>
                            <div class="text-warning">
                                <span class="h1">⏳</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #17a2b8 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">En cours</p>
                                <h3 class="mb-0">{{ $stats['orders_processing'] }}</h3>
                            </div>
                            <div class="text-info">
                                <span class="h1">🔄</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #28a745 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Aujourd'hui</p>
                                <h3 class="mb-0">{{ $stats['orders_today'] }}</h3>
                            </div>
                            <div class="text-success">
                                <span class="h1">📦</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Stock faible</p>
                                <h3 class="mb-0">{{ $stats['products_low_stock'] }}</h3>
                            </div>
                            <div class="text-danger">
                                <span class="h1">⚠️</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Commandes à traiter --}}
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">📋 Commandes à traiter</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($orders_to_process->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0">Commande</th>
                                            <th class="border-0">Client</th>
                                            <th class="border-0">Montant</th>
                                            <th class="border-0">Statut</th>
                                            <th class="border-0">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders_to_process as $order)
                                        <tr>
                                            <td class="align-middle">
                                                <strong>#{{ $order->id }}</strong>
                                            </td>
                                            <td class="align-middle">
                                                {{ $order->user->name ?? $order->customer_name ?? 'N/A' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ number_format($order->total_amount, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="align-middle">
                                                @if($order->status === 'pending')
                                                    <span class="badge bg-warning">En attente</span>
                                                @else
                                                    <span class="badge bg-info">En cours</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-muted small">
                                                {{ $order->created_at->diffForHumans() }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <span class="display-1">✅</span>
                                </div>
                                <h5 class="text-muted">Aucune commande en attente</h5>
                                <p class="text-muted">Toutes les commandes ont été traitées !</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions Rapides --}}
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">⚡ Actions Rapides</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary btn-block mb-2">
                            📦 Voir toutes les commandes
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-block mb-2">
                            👗 Gérer les produits
                        </a>
                        <a href="{{ route('admin.orders.scan') }}" class="btn btn-outline-info btn-block">
                            📱 Scanner QR Code
                        </a>
                    </div>
                </div>

                {{-- Info Session --}}
                <div class="card border-0 shadow-sm bg-dark text-white">
                    <div class="card-body">
                        <h5 class="mb-3">📊 Ma Session</h5>
                        <p class="small mb-2">
                            <strong>Connecté depuis :</strong><br>
                            {{ now()->format('H:i') }}
                        </p>
                        <p class="small mb-0">
                            <strong>Rôle :</strong><br>
                            {{ Auth::user()->staff_role ?? 'Staff général' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
