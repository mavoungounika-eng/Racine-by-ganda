@extends('layouts.admin-master')

@section('title', 'Détail Transaction - Payments Hub - RACINE BY GANDA')
@section('page-title', 'Détail Transaction')
@section('page-subtitle', 'Informations complètes et timeline des événements')

@section('content')

<div class="row g-4">
    {{-- Informations transaction --}}
    <div class="col-lg-8">
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-info-circle text-racine-orange me-2"></i>
                    Informations Transaction
                </h5>
                <a href="{{ route('admin.payments.transactions.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>ID Transaction:</strong><br>
                        <code>{{ $transaction->id }}</code>
                    </div>
                    <div class="col-md-6">
                        <strong>Provider:</strong><br>
                        <span class="badge badge-{{ $transaction->provider === 'stripe' ? 'primary' : 'info' }}">
                            {{ ucfirst($transaction->provider) }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Payment Ref:</strong><br>
                        <code>{{ $transaction->payment_ref ?? 'N/A' }}</code>
                    </div>
                    <div class="col-md-6">
                        <strong>Transaction ID:</strong><br>
                        <code>{{ $transaction->transaction_id ?? 'N/A' }}</code>
                    </div>
                    <div class="col-md-6">
                        <strong>Montant:</strong><br>
                        <h4 class="mb-0 text-success">{{ number_format($transaction->amount ?? 0, 0, ',', ' ') }} {{ $transaction->currency ?? 'XAF' }}</h4>
                    </div>
                    <div class="col-md-6">
                        <strong>Statut:</strong><br>
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
                        <span class="badge badge-{{ $badge }} badge-lg">{{ ucfirst($transaction->status) }}</span>
                    </div>
                    @if($transaction->order_id)
                        <div class="col-md-6">
                            <strong>Commande:</strong><br>
                            <a href="{{ route('admin.orders.show', $transaction->order_id) }}" class="text-decoration-none">
                                Commande #{{ $transaction->order_id }}
                            </a>
                        </div>
                    @endif
                    @if($transaction->operator)
                        <div class="col-md-6">
                            <strong>Opérateur:</strong><br>
                            {{ $transaction->operator }}
                        </div>
                    @endif
                    @if($transaction->phone)
                        <div class="col-md-6">
                            <strong>Téléphone:</strong><br>
                            {{ $transaction->phone }}
                        </div>
                    @endif
                    @if($transaction->fee)
                        <div class="col-md-6">
                            <strong>Frais:</strong><br>
                            {{ number_format($transaction->fee, 0, ',', ' ') }} {{ $transaction->currency ?? 'XAF' }}
                        </div>
                    @endif
                    <div class="col-md-6">
                        <strong>Créée le:</strong><br>
                        {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                    </div>
                    @if($transaction->notified_at)
                        <div class="col-md-6">
                            <strong>Notifiée le:</strong><br>
                            {{ $transaction->notified_at->format('d/m/Y H:i:s') }}
                        </div>
                    @endif
                </div>

                @if($transaction->raw_payload)
                    <hr>
                    <h6 class="fw-bold mb-3">Payload (redacted):</h6>
                    <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>@json(app(\App\Services\Payments\PayloadRedactionService::class)->redact($transaction->raw_payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</code></pre>
                @endif
            </div>
        </div>
    </div>

    {{-- Timeline événements --}}
    <div class="col-lg-4">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-history text-racine-orange me-2"></i>
                    Timeline Événements
                </h5>
            </div>
            <div class="card-body">
                @forelse($timelineEvents as $timelineItem)
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge badge-{{ $timelineItem['type'] === 'stripe' ? 'primary' : 'info' }}">
                                {{ ucfirst($timelineItem['type']) }}
                            </span>
                            <small class="text-muted">{{ $timelineItem['created_at']->diffForHumans() }}</small>
                        </div>
                        <div class="small">
                            <strong>Type:</strong> {{ $timelineItem['event']->event_type ?? 'N/A' }}<br>
                            <strong>Statut:</strong> 
                            @php
                                $eventStatus = $timelineItem['event']->status ?? 'unknown';
                                $statusBadges = [
                                    'processed' => 'success',
                                    'failed' => 'danger',
                                    'received' => 'warning',
                                    'ignored' => 'secondary',
                                ];
                                $badge = $statusBadges[$eventStatus] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $badge }}">{{ ucfirst($eventStatus) }}</span>
                        </div>
                        @if($timelineItem['type'] === 'stripe')
                            <div class="mt-2">
                                <small class="text-muted">Event ID: <code>{{ $timelineItem['event']->event_id ?? 'N/A' }}</code></small>
                            </div>
                        @else
                            <div class="mt-2">
                                <small class="text-muted">Event Key: <code>{{ $timelineItem['event']->event_key ?? 'N/A' }}</code></small>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center mb-0">Aucun événement associé</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection




