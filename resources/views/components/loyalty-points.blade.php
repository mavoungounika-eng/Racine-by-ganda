@props(['user'])

@php
    $loyaltyPoint = $user->loyaltyPoints ?? null;
    $points = $loyaltyPoint ? $loyaltyPoint->points : 0;
    $tier = $loyaltyPoint ? $loyaltyPoint->tier : 'bronze';
    $tierNames = [
        'bronze' => 'Bronze',
        'silver' => 'Silver',
        'gold' => 'Gold',
    ];
    $tierColors = [
        'bronze' => '#ED5F1E',
        'silver' => 'rgba(22,13,12,0.3)',
        'gold' => '#FFB800',
    ];
@endphp

@if($loyaltyPoint)
<div class="loyalty-card card mb-4">
    <div class="card-body">
        <h5 class="card-title">
            <i class="icon-star me-2"></i>
            Programme de Fidélité
        </h5>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">{{ number_format($points) }} points</h3>
                <small class="text-muted">Points disponibles</small>
            </div>
            <div class="text-end">
                <span class="badge" style="background: {{ $tierColors[$tier] }}; color: white; padding: 0.5rem 1rem;">
                    {{ $tierNames[$tier] }}
                </span>
            </div>
        </div>

        <div class="progress mb-3" style="height: 10px;">
            @php
                $nextTier = match($tier) {
                    'bronze' => 5000,
                    'silver' => 10000,
                    'gold' => PHP_INT_MAX,
                };
                $progress = min(100, ($loyaltyPoint->total_earned / $nextTier) * 100);
            @endphp
            <div class="progress-bar" role="progressbar" 
                 style="width: {{ $progress }}%; background: {{ $tierColors[$tier] }};"
                 aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>

        <small class="text-muted">
            @if($tier !== 'gold')
            {{ number_format($nextTier - $loyaltyPoint->total_earned) }} points pour atteindre le niveau {{ $tierNames[match($tier) { 'bronze' => 'silver', 'silver' => 'gold', default => 'gold' }] }}
            @else
            Niveau maximum atteint ! 🎉
            @endif
        </small>

        <div class="mt-3">
            <a href="{{ route('profile.loyalty') }}" class="btn btn-sm btn-outline-primary">
                Voir l'historique
            </a>
        </div>
    </div>
</div>
@endif

