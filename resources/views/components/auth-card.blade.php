@props([
    'context' => null, // 'boutique', 'equipe' ou null
])

<div class="auth-card-container">
    <div class="auth-card {{ $context ? 'auth-card--' . $context : 'auth-card--neutral' }}">
        {{ $slot }}
    </div>
</div>

<style>
.auth-card-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

.auth-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    padding: 3rem;
    max-width: 450px;
    width: 100%;
}

.auth-card--boutique {
    border-top: 4px solid #667eea;
}

.auth-card--equipe {
    border-top: 4px solid #f59e0b;
}

.auth-card--neutral {
    border-top: 4px solid #6b7280;
}
</style>
