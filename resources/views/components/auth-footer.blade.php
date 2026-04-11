@props([
    'type' => 'login', // 'login' ou 'register'
])

<div class="auth-footer mt-4 text-center">
    @if($type === 'login')
        <p class="mb-2">
            <a href="{{ route('password.request') }}" class="text-primary">
                Mot de passe oublié ?
            </a>
        </p>
        <p class="text-muted">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="text-primary font-weight-bold">
                S'inscrire
            </a>
        </p>
    @else
        <p class="text-muted">
            Déjà un compte ?
            <a href="{{ route('login') }}" class="text-primary font-weight-bold">
                Se connecter
            </a>
        </p>
    @endif
</div>

<style>
.auth-footer {
    font-size: 0.9rem;
}

.auth-footer a {
    text-decoration: none;
    transition: all 0.2s ease;
}

.auth-footer a:hover {
    text-decoration: underline;
}
</style>
