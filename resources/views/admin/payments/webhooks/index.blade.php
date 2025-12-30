@extends('layouts.admin-master')

@section('title', 'Webhooks/Callbacks - Payments Hub - RACINE BY GANDA')
@section('page-title', 'Monitoring Webhooks/Callbacks')
@section('page-subtitle', 'Suivi des événements Stripe et Monetbil')

@section('content')

{{-- Lien vers Stuck Webhooks --}}
@can('payments.view')
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('admin.payments.webhooks.stuck.index') }}" class="btn btn-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Stuck Webhooks
        </a>
    </div>
</div>
@endcan

{{-- Stats --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fab fa-stripe text-primary me-2"></i>
                    Stripe
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="small text-muted">Total</div>
                        <div class="h4 mb-0">{{ $stats['stripe']['total'] }}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Traité</div>
                        <div class="h4 mb-0 text-success">{{ $stats['stripe']['processed'] }}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Échoué</div>
                        <div class="h4 mb-0 text-danger">{{ $stats['stripe']['failed'] }}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Reçu</div>
                        <div class="h4 mb-0 text-warning">{{ $stats['stripe']['received'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-mobile-alt text-info me-2"></i>
                    Monetbil
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="small text-muted">Total</div>
                        <div class="h4 mb-0">{{ $stats['monetbil']['total'] }}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Traité</div>
                        <div class="h4 mb-0 text-success">{{ $stats['monetbil']['processed'] }}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Échoué</div>
                        <div class="h4 mb-0 text-danger">{{ $stats['monetbil']['failed'] }}</div>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted">Reçu</div>
                        <div class="h4 mb-0 text-warning">{{ $stats['monetbil']['received'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filtres --}}
<div class="card card-racine mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.payments.webhooks.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Provider</label>
                <select name="provider" class="form-control">
                    <option value="all" {{ $provider === 'all' ? 'selected' : '' }}>Tous</option>
                    <option value="stripe" {{ $provider === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="monetbil" {{ $provider === 'monetbil' ? 'selected' : '' }}>Monetbil</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control">
                    <option value="">Tous</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Reçu</option>
                    <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Traité</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                    <option value="ignored" {{ request('status') === 'ignored' ? 'selected' : '' }}>Ignoré</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type événement</label>
                <input type="text" name="event_type" class="form-control" value="{{ request('event_type') }}" placeholder="payment_intent...">
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
                    <a href="{{ route('admin.payments.webhooks.index') }}" class="btn btn-secondary w-100" title="Réinitialiser">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tabs Bootstrap 4 --}}
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ $provider === 'all' || $provider === 'stripe' ? 'active' : '' }}" 
           href="{{ route('admin.payments.webhooks.index', array_merge(request()->all(), ['provider' => 'stripe'])) }}">
            <i class="fab fa-stripe me-1"></i> Stripe
            @if($stats['stripe']['total'] > 0)
                <span class="badge badge-primary">{{ $stats['stripe']['total'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $provider === 'monetbil' ? 'active' : '' }}" 
           href="{{ route('admin.payments.webhooks.index', array_merge(request()->all(), ['provider' => 'monetbil'])) }}">
            <i class="fas fa-mobile-alt me-1"></i> Monetbil
            @if($stats['monetbil']['total'] > 0)
                <span class="badge badge-info">{{ $stats['monetbil']['total'] }}</span>
            @endif
        </a>
    </li>
</ul>

{{-- Contenu Stripe --}}
@if(($provider === 'all' || $provider === 'stripe') && $stripeEvents)
    <div class="card card-racine mb-4">
        <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fab fa-stripe text-primary me-2"></i>
                Événements Stripe ({{ $stripeEvents->total() }})
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stripeEvents as $event)
                            <tr>
                                <td><code>{{ $event->event_id }}</code></td>
                                <td>{{ $event->event_type ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $statusBadges = [
                                            'processed' => 'success',
                                            'failed' => 'danger',
                                            'received' => 'warning',
                                            'ignored' => 'secondary',
                                        ];
                                        $badge = $statusBadges[$event->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ ucfirst($event->status) }}</span>
                                </td>
                                <td><small>{{ $event->created_at->format('d/m/Y H:i') }}</small></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payments.webhooks.show.stripe', $event) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aucun événement Stripe</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($stripeEvents->hasPages())
            <div class="card-footer bg-transparent">
                {{ $stripeEvents->links() }}
            </div>
        @endif
    </div>
@endif

{{-- Contenu Monetbil --}}
@if(($provider === 'all' || $provider === 'monetbil') && $monetbilEvents)
    <div class="card card-racine mb-4">
        <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-mobile-alt text-info me-2"></i>
                Événements Monetbil ({{ $monetbilEvents->total() }})
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Event Key</th>
                            <th>Type</th>
                            <th>Payment Ref</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monetbilEvents as $event)
                            <tr>
                                <td><code>{{ $event->event_key }}</code></td>
                                <td>{{ $event->event_type ?? 'N/A' }}</td>
                                <td><code>{{ $event->payment_ref ?? 'N/A' }}</code></td>
                                <td>
                                    @php
                                        $statusBadges = [
                                            'processed' => 'success',
                                            'failed' => 'danger',
                                            'received' => 'warning',
                                            'ignored' => 'secondary',
                                        ];
                                        $badge = $statusBadges[$event->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ ucfirst($event->status) }}</span>
                                </td>
                                <td><small>{{ $event->created_at->format('d/m/Y H:i') }}</small></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payments.webhooks.show.monetbil', $event) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucun événement Monetbil</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($monetbilEvents->hasPages())
            <div class="card-footer bg-transparent">
                {{ $monetbilEvents->links() }}
            </div>
        @endif
    </div>
@endif

@endsection




