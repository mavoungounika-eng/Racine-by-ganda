@props([
    'value' => '0',
    'label' => 'Label',
    'icon' => '📊',
    'color' => 'primary', // primary, success, warning, danger, gold
    'trend' => null, // +10%, -5%, etc.
    'trendUp' => true
])

@php
$gradients = [
    'primary' => 'linear-gradient(135deg, #4B1DF2 0%, #3A16BD 100%)',
    'success' => 'linear-gradient(135deg, #22C55E 0%, #15803D 100%)',
    'warning' => 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)',
    'danger' => 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)',
    'gold' => 'linear-gradient(135deg, #D4AF37 0%, #B8860B 100%)',
    'info' => 'linear-gradient(135deg, #0EA5E9 0%, #0369A1 100%)',
    'dark' => 'linear-gradient(135deg, #1A1A2E 0%, #11001F 100%)',
];
$gradient = $gradients[$color] ?? $gradients['primary'];
$textColor = in_array($color, ['gold']) ? '#11001F' : '#FFFFFF';
@endphp

<div class="card kpi-card h-100" style="background: {{ $gradient }}; color: {{ $textColor }}; overflow: hidden;">
    <div class="card-body position-relative">
        <div class="kpi-icon">{{ $icon }}</div>
        <p class="kpi-label mb-1" style="opacity: 0.85; font-size: 0.85rem;">{{ $label }}</p>
        <h2 class="kpi-value mb-0">{{ $value }}</h2>
        @if($trend)
            <div class="mt-2" style="font-size: 0.8rem;">
                <span style="opacity: 0.9;">
                    @if($trendUp)
                        ↑
                    @else
                        ↓
                    @endif
                    {{ $trend }}
                </span>
            </div>
        @endif
    </div>
</div>

