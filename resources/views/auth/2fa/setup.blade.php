@extends('layouts.frontend')

@section('title', 'Activer la Double Authentification - RACINE BY GANDA')

@push('styles')
<style>
    .setup-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #1a0f09 0%, #2C1810 50%, #1a0f09 100%);
        padding: 4rem 0;
        margin-top: -70px;
        padding-top: calc(4rem + 70px);
    }
    
    .setup-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        max-width: 600px;
        margin: 0 auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    }
    
    .setup-header {
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }
    
    .setup-header h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    
    .setup-header p {
        opacity: 0.9;
        font-size: 0.95rem;
    }
    
    .setup-body {
        padding: 2.5rem;
    }
    
    .step {
        margin-bottom: 2rem;
    }
    
    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.9rem;
        margin-right: 0.75rem;
    }
    
    .step-title {
        font-weight: 600;
        color: #2C1810;
        font-size: 1.1rem;
    }
    
    .step-content {
        margin-left: 2.5rem;
        margin-top: 0.75rem;
        color: #5C4A3D;
    }
    
    .qr-container {
        display: flex;
        justify-content: center;
        padding: 1.5rem;
        background: #F8F6F3;
        border-radius: 16px;
        margin: 1rem 0;
    }
    
    .qr-container svg {
        width: 200px;
        height: 200px;
    }
    
    .secret-key {
        background: #2C1810;
        color: #D4A574;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        font-family: 'Courier New', monospace;
        font-size: 1rem;
        letter-spacing: 2px;
        text-align: center;
        word-break: break-all;
    }
    
    .copy-btn {
        background: #D4A574;
        color: #2C1810;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 0.75rem;
        transition: all 0.3s;
    }
    
    .copy-btn:hover {
        background: #ED5F1E;
        color: white;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .code-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        font-size: 1.5rem;
        text-align: center;
        letter-spacing: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .code-input:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }
    
    .btn-primary {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(237, 95, 30, 0.3);
    }
    
    .app-badges {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .app-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #F8F6F3;
        border-radius: 8px;
        color: #5C4A3D;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.3s;
    }
    
    .app-badge:hover {
        background: #E5DDD3;
        color: #2C1810;
    }
    
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    
    .alert-error {
        background: #FEF2F2;
        color: #DC2626;
        border: 1px solid #FECACA;
    }
    
    .security-note {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }
    
    .security-note i {
        color: #22C55E;
        font-size: 1.25rem;
        margin-top: 2px;
    }
    
    .security-note p {
        color: #166534;
        font-size: 0.9rem;
        margin: 0;
    }
</style>
@endpush

@section('content')
<div class="setup-page">
    <div class="container">
        <div class="setup-card">
            <div class="setup-header">
                <h1>🔐 Double Authentification</h1>
                <p>Protégez votre compte avec Google Authenticator</p>
            </div>
            
            <div class="setup-body">
                @if(session('error'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
                @endif
                
                <div class="step">
                    <span class="step-number">1</span>
                    <span class="step-title">Téléchargez l'application</span>
                    <div class="step-content">
                        <p>Installez Google Authenticator ou une application compatible sur votre téléphone.</p>
                        <div class="app-badges">
                            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="app-badge">
                                <i class="fab fa-google-play"></i> Android
                            </a>
                            <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" class="app-badge">
                                <i class="fab fa-app-store"></i> iPhone
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-title">Scannez le QR Code</span>
                    <div class="step-content">
                        <p>Ouvrez l'application et scannez ce QR code :</p>
                        <div class="qr-container">
                            {!! $qrCodeSvg !!}
                        </div>
                        <p style="text-align: center; font-size: 0.85rem; color: #8B7355;">
                            Ou entrez manuellement cette clé :
                        </p>
                        <div class="secret-key" id="secret-key">{{ $secret }}</div>
                        <div class="text-center">
                            <button type="button" class="copy-btn" onclick="copySecret()">
                                <i class="fas fa-copy"></i> Copier la clé
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-title">Entrez le code de vérification</span>
                    <div class="step-content">
                        <form action="{{ route('2fa.confirm') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="code">Code à 6 chiffres</label>
                                <input type="text" 
                                       name="code" 
                                       id="code" 
                                       class="code-input" 
                                       maxlength="6" 
                                       pattern="[0-9]{6}"
                                       inputmode="numeric"
                                       autocomplete="one-time-code"
                                       placeholder="000000"
                                       required>
                            </div>
                            
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-shield-alt"></i> Activer la protection
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    <p>
                        <strong>Sécurité maximale</strong><br>
                        La double authentification protège votre compte même si votre mot de passe est compromis.
                        Elle est <strong>obligatoire</strong> pour les comptes administrateurs.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copySecret() {
    const secret = document.getElementById('secret-key').textContent;
    navigator.clipboard.writeText(secret).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy"></i> Copier la clé';
        }, 2000);
    });
}

// Auto-focus sur l'input
document.getElementById('code').focus();

// Format automatique du code
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 6);
});
</script>
@endpush

