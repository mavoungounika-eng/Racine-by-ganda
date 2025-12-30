@props([
    'context' => null,
])

@if($context !== 'equipe')
<div class="auth-oauth mt-4">
    <div class="oauth-divider mb-3">
        <span>Ou continuer avec</span>
    </div>
    
    <div class="oauth-buttons">
        <a href="{{ route('auth.social.redirect', ['provider' => 'google', 'role' => 'client']) }}" 
           class="btn btn-outline-secondary btn-block mb-2">
            <i class="fab fa-google mr-2"></i> Google
        </a>
        
        <a href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'role' => 'client']) }}" 
           class="btn btn-outline-secondary btn-block mb-2">
            <i class="fab fa-facebook mr-2"></i> Facebook
        </a>
        
        <a href="{{ route('auth.social.redirect', ['provider' => 'apple', 'role' => 'client']) }}" 
           class="btn btn-outline-secondary btn-block">
            <i class="fab fa-apple mr-2"></i> Apple
        </a>
    </div>
</div>
@endif

<style>
.oauth-divider {
    display: flex;
    align-items: center;
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
}

.oauth-divider::before,
.oauth-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e5e7eb;
}

.oauth-divider span {
    padding: 0 1rem;
}

.oauth-buttons .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.oauth-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
