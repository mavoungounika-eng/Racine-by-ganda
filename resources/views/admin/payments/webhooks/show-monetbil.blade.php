@extends('layouts.admin-master')

@section('title', 'Détail Événement Monetbil - Payments Hub')
@section('page-title', 'Détail Événement Monetbil')
@section('page-subtitle', 'Informations complètes du callback')

@section('content')

<div class="card card-racine">
    <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-mobile-alt text-info me-2"></i>
            Événement Monetbil
        </h5>
        <a href="{{ route('admin.payments.webhooks.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <strong>Event Key:</strong><br>
                <code>{{ $event->event_key }}</code>
            </div>
            <div class="col-md-6">
                <strong>Type:</strong><br>
                {{ $event->event_type ?? 'N/A' }}
            </div>
            <div class="col-md-6">
                <strong>Payment Ref:</strong><br>
                <code>{{ $event->payment_ref ?? 'N/A' }}</code>
            </div>
            <div class="col-md-6">
                <strong>Transaction ID:</strong><br>
                <code>{{ $event->transaction_id ?? 'N/A' }}</code>
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
                <strong>Reçu le:</strong><br>
                {{ $event->received_at?->format('d/m/Y H:i:s') ?? $event->created_at->format('d/m/Y H:i:s') }}
            </div>
            @if($event->processed_at)
                <div class="col-md-6">
                    <strong>Traité le:</strong><br>
                    {{ $event->processed_at->format('d/m/Y H:i:s') }}
                </div>
            @endif
            @if($event->error)
                <div class="col-12">
                    <strong>Erreur:</strong><br>
                    <div class="alert alert-danger mb-0">{{ $event->error }}</div>
                </div>
            @endif
        </div>

        @if($event->payload)
            <hr>
            <h6 class="fw-bold mb-3">Payload (redacted):</h6>
            <pre class="bg-light p-3 rounded" style="max-height: 500px; overflow-y: auto;"><code>@json(app(\App\Services\Payments\PayloadRedactionService::class)->redact($event->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</code></pre>
        @endif
    </div>
</div>

@endsection




