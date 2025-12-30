@extends('layouts.admin-master')

@section('title', 'Transactions - Payments Hub - RACINE BY GANDA')
@section('page-title', 'Transactions')
@section('page-subtitle', 'Liste et gestion des transactions de paiement')

@section('content')

{{-- En-tête avec stats --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total</h6>
                <h3 class="mb-0">{{ number_format($stats['total'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Réussies</h6>
                <h3 class="mb-0 text-success">{{ number_format($stats['succeeded'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">Échouées</h6>
                <h3 class="mb-0 text-danger">{{ number_format($stats['failed'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body">
                <h6 class="text-muted mb-1">En attente</h6>
                <h3 class="mb-0 text-warning">{{ number_format($stats['pending'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Filtres --}}
<div class="card card-racine mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.payments.transactions.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Payment ref, transaction ID...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Provider</label>
                <select name="provider" class="form-control">
                    <option value="">Tous</option>
                    <option value="stripe" {{ request('provider') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="monetbil" {{ request('provider') === 'monetbil' ? 'selected' : '' }}>Monetbil</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>En traitement</option>
                    <option value="succeeded" {{ request('status') === 'succeeded' ? 'selected' : '' }}>Réussi</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                    <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Annulé</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date début</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('admin.payments.transactions.index') }}" class="btn btn-secondary w-100" title="Réinitialiser">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table transactions --}}
<div class="card card-racine">
    <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-list text-racine-orange me-2"></i>
            Transactions ({{ $transactions->total() }})
        </h5>
        <a href="{{ route('admin.payments.transactions.export.csv', request()->all()) }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-download me-1"></i> Export CSV
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Provider</th>
                        <th>Payment Ref</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Order</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <code>{{ $transaction->id }}</code>
                            </td>
                            <td>
                                <span class="badge badge-{{ $transaction->provider === 'stripe' ? 'primary' : 'info' }}">
                                    {{ ucfirst($transaction->provider) }}
                                </span>
                            </td>
                            <td>
                                <small class="font-monospace">{{ $transaction->payment_ref ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <strong>{{ number_format($transaction->amount ?? 0, 0, ',', ' ') }} {{ $transaction->currency ?? 'XAF' }}</strong>
                            </td>
                            <td>
                                @php
                                    $statusBadges = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'succeeded' => 'success',
                                        'failed' => 'danger',
                                        'canceled' => 'secondary',
                                        'refunded' => 'info',
                                    ];
                                    $badge = $statusBadges[$transaction->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $badge }}">{{ ucfirst($transaction->status) }}</span>
                            </td>
                            <td>
                                @if($transaction->order_id)
                                    <a href="{{ route('admin.orders.show', $transaction->order_id) }}" class="text-decoration-none">
                                        #{{ $transaction->order_id }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $transaction->created_at->format('d/m/Y H:i') }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.payments.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Aucune transaction trouvée
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer bg-transparent">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

@endsection




