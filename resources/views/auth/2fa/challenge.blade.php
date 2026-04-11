@extends('layouts.frontend')

@section('title', 'Vérification 2FA - RACINE BY GANDA')

@push('styles')
<style>
    .challenge-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #1a0f09 0%, #2C1810 50%, #1a0f09 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        margin-top: -70px;
    }
    
    .challenge-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    }
    
    .challenge-header {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 2.5rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .challenge-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(237, 95, 30, 0.1) 0%, transparent 70%);
    }
    
    .lock-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 2rem;
        color: white;
        position: relative;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(237, 95, 30, 0.4); }
        50% { box-shadow: 0 0 0 15px rgba(237, 95, 30, 0); }
    }
    
    .challenge-header h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 0.5rem;
        position: relative;
    }
    
    .challenge-header p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        position: relative;
    }
    
    .challenge-body {
        padding: 2rem;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-error {
        background: #FEF2F2;
        color: #DC2626;
        border: 1px solid #FECACA;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }
    
    .code-input {
        width: 100%;
        padding: 1.25rem;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        font-size: 1.75rem;
        text-align: center;
        letter-spacing: 10px;
        font-weight: 700;
        color: #2C1810;
        transition: all 0.3s;
    }
    
    .code-input:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
    
    .code-input::placeholder {
        letter-spacing: 5px;
        color: #ccc;
    }
    
    .trust-device {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #F8F6F3;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        cursor: pointer;
    }
    
    .trust-device input {
        width: 20px;
        height: 20px;
        accent-color: #ED5F1E;
    }
    
    .trust-device span {
        color: #5C4A3D;
        font-size: 0.9rem;
    }
    
    .btn-primary {
        width: 100%;
        padding: 1.1rem;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(237, 95, 30, 0.3);
    }
    
    .divider {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        color: #8B7355;
        font-size: 0.85rem;
    }
    
    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #E5DDD3;
    }
    
    .divider span {
        padding: 0 1rem;
    }
    
    .recovery-link {
        display: block;
        text-align: center;
        color: #ED5F1E;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .recovery-link:hover {
        color: #c44b12;
        text-decoration: underline;
    }
    
    .help-text {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .help-text p {
        color: #8B7355;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
    
    .help-text a {
        color: #ED5F1E;
        text-decoration: none;
    }
</style>
@endpush

@section('content')
<div class="challenge-page">
    <div class="challenge-card">
        <div class="challenge-header">
            <div class="lock-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Vérification en deux étapes</h1>
            <p>Entrez le code de votre application d'authentification</p>
        </div>
        
        <div class="challenge-body">
            @if(session('error'))
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif
            
            <form action="{{ route('2fa.verify') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="code">
                        <i class="fas fa-key"></i> Code de vérification
                    </label>
                    <input type="text" 
                           name="code" 
                           id="code" 
                           class="code-input" 
                           maxlength="9"
                           autocomplete="one-time-code"
                           placeholder="000000"
                           autofocus
                           required>
                </div>
                
                <label class="trust-device">
                    <input type="checkbox" name="trust_device" value="1">
                    <span>
                        <strong>Faire confiance à cet appareil</strong><br>
                        <small style="color: #8B7355;">Vous ne serez plus demandé pendant 30 jours</small>
                    </span>
                </label>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i>
                    Vérifier et continuer
                </button>
            </form>
            
            <div class="divider">
                <span>ou</span>
            </div>
            
            <a href="#" class="recovery-link" onclick="showRecoveryInput()">
                <i class="fas fa-key"></i> Utiliser un code de récupération
            </a>
            
            <div class="help-text">
                <p>Problème d'accès ?</p>
                <a href="{{ route('frontend.contact') }}">Contactez le support</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-format du code
document.getElementById('code').addEventListener('input', function(e) {
    let value = this.value.replace(/[^0-9A-Za-z-]/g, '');
    
    // Si c'est un code numérique (6 chiffres), formater
    if (/^\d+$/.test(value)) {
        value = value.substring(0, 6);
    }
    
    this.value = value;
});

function showRecoveryInput() {
    const input = document.getElementById('code');
    input.placeholder = 'XXXX-XXXX';
    input.maxLength = 9;
    alert('Entrez un de vos codes de récupération (format: XXXX-XXXX)');
    input.focus();
}
</script>
@endpush

