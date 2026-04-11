@props([
    'context' => null,
    'type' => 'login', // 'login' ou 'register'
])

@php
    $titles = [
        'login' => [
            'boutique' => 'Connexion Client',
            'equipe' => 'Connexion Équipe',
            'neutral' => 'Connexion',
        ],
        'register' => [
            'boutique' => 'Créer un compte',
            'equipe' => 'Inscription Équipe',
            'neutral' => 'Inscription',
        ],
    ];
    
    $subtitles = [
        'login' => [
            'boutique' => 'Accédez à votre espace client',
            'equipe' => 'Accédez à votre espace de travail',
            'neutral' => 'Bienvenue sur RACINE',
        ],
        'register' => [
            'boutique' => 'Rejoignez la communauté RACINE',
            'equipe' => 'Créez votre compte équipe',
            'neutral' => 'Créez votre compte',
        ],
    ];
    
    $contextKey = $context ?? 'neutral';
    $title = $titles[$type][$contextKey] ?? $titles[$type]['neutral'];
    $subtitle = $subtitles[$type][$contextKey] ?? $subtitles[$type]['neutral'];
@endphp

<div class="auth-header text-center mb-4">
    <h2 class="auth-title mb-2">{{ $title }}</h2>
    <p class="auth-subtitle text-muted">{{ $subtitle }}</p>
    
    @if($context)
        <span class="badge badge-{{ $context === 'boutique' ? 'primary' : 'warning' }} mb-3">
            {{ $context === 'boutique' ? '🛍️ Espace Boutique' : '👥 Espace Équipe' }}
        </span>
    @endif
</div>

<style>
.auth-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.auth-subtitle {
    font-size: 0.95rem;
    color: #6b7280;
}
</style>
