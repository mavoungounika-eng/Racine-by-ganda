{{-- Composant Carte Statistique --}}
@props(['title', 'value', 'icon', 'color' => 'primary', 'subtitle' => null, 'trend' => null])

@php
    $colors = [
        'primary' => ['bg' => 'bg-primary', 'text' => 'text-primary', 'gradient' => 'linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%)'],
        'success' => ['bg' => 'bg-success', 'text' => 'text-success', 'gradient' => 'linear-gradient(135deg, #22C55E 0%, #15803D 100%)'],
        'info' => ['bg' => 'bg-info', 'text' => 'text-info', 'gradient' => 'linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%)'],
        'warning' => ['bg' => 'bg-warning', 'text' => 'text-warning', 'gradient' => 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)'],
        'danger' => ['bg' => 'bg-danger', 'text' => 'text-danger', 'gradient' => 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)'],
    ];
    $colorScheme = $colors[$color] ?? $colors['primary'];
@endphp

<div class="card card-racine h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <div class="text-uppercase small text-muted mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">
                    {{ $title }}
                </div>
                <div class="h3 mb-0 text-racine-black" style="font-weight: 700; font-size: 1.75rem;">
                    {{ $value }}
                </div>
                @if($subtitle)
                    <div class="small text-muted mt-2">
                        {{ $subtitle }}
                    </div>
                @endif
                @if($trend)
                    <div class="small mt-2" style="color: {{ $trend['color'] ?? '#22C55E' }}; font-weight: 600;">
                        <i class="fas fa-arrow-{{ $trend['direction'] ?? 'up' }}"></i>
                        {{ $trend['value'] }}
                    </div>
                @endif
            </div>
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 56px; height: 56px; background: {{ $colorScheme['gradient'] }}; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <i class="{{ $icon }} fa-lg"></i>
            </div>
        </div>
    </div>
</div>

