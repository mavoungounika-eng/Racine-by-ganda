@extends('layouts.admin-master')

@section('title', 'Détail Événement Stripe - Payments Hub')
@section('page-title', 'Détail Événement Stripe')
@section('page-subtitle', 'Informations complètes de l\'événement webhook')

@section('content')

<div class="card card-racine">
    <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">
            <i class="fab fa-stripe text-primary me-2"></i>
            Événement Stripe
        </h5>
        <a href="{{ route('admin.payments.webhooks.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <strong>Event ID:</strong><br>
                <code>{{ $event->event_id }}</code>
            </div>
            <div class="col-md-6">
                <strong>Type:</strong><br>
                {{ $event->event_type ?? 'N/A' }}
            </div>
            <div class="col-md-6">
                <strong>Statut:</strong><br>
                @php
                    $statusBadges = [
                        'processed' => 'success',
                        'failed' => 'danger',
                        'received' => 'warning',
                        'ignored' => 'secondary',
                    ];
                    $badge = $statusBadges[$event->status] ?? 'secondary';
                @endphp
                <span class="badge badge-{{ $badge }} badge-lg">{{ ucfirst($event->status) }}</span>
            </div>
            <div class="col-md-6">
                <strong>Créé le:</strong><br>
                {{ $event->created_at->format('d/m/Y H:i:s') }}
            </div>
            @if($event->processed_at)
                <div class="col-md-6">
                    <strong>Traité le:</strong><br>
                    {{ $event->processed_at->format('d/m/Y H:i:s') }}
                </div>
            @endif
            @if($event->payment_id)
                <div class="col-md-6">
                    <strong>Payment ID:</strong><br>
                    {{ $event->payment_id }}
                </div>
            @endif
        </div>

        @if($event->payload_hash)
            <hr>
            <div>
                <strong>Payload Hash:</strong><br>
                <code>{{ $event->payload_hash }}</code>
            </div>
            <small class="text-muted">Le payload complet n'est pas stocké pour des raisons de sécurité.</small>
        @endif
    </div>
</div>

@endsection




