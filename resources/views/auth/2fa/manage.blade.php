@extends('layouts.frontend')

@section('title', 'Gérer la Double Authentification - RACINE BY GANDA')

@push('styles')
<style>
    .manage-page {
        min-height: 100vh;
        background: #F8F6F3;
        padding: 4rem 0;
        margin-top: -70px;
        padding-top: calc(4rem + 70px);
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.25rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: #8B7355;
    }
    
    .manage-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        max-width: 700px;
        margin: 0 auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    }
    
    .status-banner {
        padding: 1.5rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .status-banner.enabled {
        background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
        color: white;
    }
    
    .status-banner.disabled {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: white;
    }
    
    .status-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .status-icon {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .status-text h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .status-text p {
        font-size: 0.85rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .manage-body {
        padding: 2rem;
    }
    
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-success {
        background: #ECFDF5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }
    
    .alert-error {
        background: #FEF2F2;
        color: #DC2626;
        border: 1px solid #FECACA;
    }
    
    .alert-info {
        background: #EFF6FF;
        color: #1E40AF;
        border: 1px solid #BFDBFE;
    }
    
    .section {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .section:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .recovery-status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        background: #F8F6F3;
        border-radius: 12px;
    }
    
    .recovery-status .info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .recovery-status .count {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ED5F1E;
    }
    
    .recovery-status .label {
        color: #5C4A3D;
        font-size: 0.9rem;
    }
    
    .btn {
        padding: 0.75rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .btn-outline {
        background: white;
        border: 2px solid #E5DDD3;
        color: #5C4A3D;
    }
    
    .btn-outline:hover {
        border-color: #ED5F1E;
        color: #ED5F1E;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        border: none;
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(237, 95, 30, 0.3);
        color: white;
    }
    
    .btn-danger {
        background: #FEF2F2;
        border: 2px solid #FECACA;
        color: #DC2626;
    }
    
    .btn-danger:hover {
        background: #DC2626;
        border-color: #DC2626;
        color: white;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 500;
        color: #2C1810;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #E5DDD3;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #ED5F1E;
    }
    
    .required-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #FEF3C7;
        color: #D97706;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="manage-page">
    <div class="container">
        <div class="page-header">
            <h1>🔐 Sécurité du compte</h1>
            <p>Gérez votre authentification à deux facteurs</p>
        </div>
        
        <div class="manage-card">
            <div class="status-banner {{ $isEnabled ? 'enabled' : 'disabled' }}">
                <div class="status-info">
                    <div class="status-icon">
                        <i class="fas {{ $isEnabled ? 'fa-shield-alt' : 'fa-exclamation-triangle' }}"></i>
                    </div>
                    <div class="status-text">
                        <h3>{{ $isEnabled ? 'Protection activée' : 'Protection désactivée' }}</h3>
                        <p>
                            @if($isEnabled)
                            Votre compte est protégé par la double authentification
                            @else
                            Activez la double authentification pour sécuriser votre compte
                            @endif
                        </p>
                    </div>
                </div>
                @if($isRequired)
                <span class="required-badge">
                    <i class="fas fa-lock"></i> Obligatoire
                </span>
                @endif
            </div>
            
            <div class="manage-body">
                @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif
                
                @if(session('error'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif
                
                @if(session('info'))
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ session('info') }}</span>
                </div>
                @endif
                
                @if(!$isEnabled)
                {{-- Section activation --}}
                <div class="section">
                    <h4 class="section-title">
                        <i class="fas fa-plus-circle"></i> Activer la protection
                    </h4>
                    <p style="color: #5C4A3D; margin-bottom: 1rem;">
                        Utilisez Google Authenticator ou une application compatible pour générer des codes de vérification.
                    </p>
                    <a href="{{ route('2fa.setup') }}" class="btn btn-primary">
                        <i class="fas fa-shield-alt"></i> Configurer maintenant
                    </a>
                </div>
                @else
                {{-- Section codes de récupération --}}
                <div class="section">
                    <h4 class="section-title">
                        <i class="fas fa-key"></i> Codes de récupération
                    </h4>
                    <div class="recovery-status">
                        <div class="info">
                            <span class="count">{{ $recoveryCodesCount }}</span>
                            <span class="label">codes disponibles</span>
                        </div>
                        <form action="{{ route('2fa.recovery-codes.regenerate') }}" method="POST" style="display: inline;">
                            @csrf
                            <input type="password" name="password" placeholder="Mot de passe" class="form-input" style="width: auto; display: inline-block; margin-right: 0.5rem;" required>
                            <button type="submit" class="btn btn-outline">
                                <i class="fas fa-sync-alt"></i> Régénérer
                            </button>
                        </form>
                    </div>
                </div>
                
                {{-- Section désactivation --}}
                @if(!$isRequired)
                <div class="section">
                    <h4 class="section-title" style="color: #DC2626;">
                        <i class="fas fa-exclamation-triangle"></i> Zone de danger
                    </h4>
                    <p style="color: #5C4A3D; margin-bottom: 1rem;">
                        Désactiver la double authentification rendra votre compte moins sécurisé.
                    </p>
                    <form action="{{ route('2fa.disable') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Mot de passe actuel</label>
                            <input type="password" name="password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Code 2FA ou code de récupération</label>
                            <input type="text" name="code" class="form-input" placeholder="000000 ou XXXX-XXXX" required>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir désactiver la double authentification ?')">
                            <i class="fas fa-shield-alt"></i> Désactiver la protection
                        </button>
                    </form>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i>
                    <span>La double authentification est <strong>obligatoire</strong> pour les comptes administrateurs et ne peut pas être désactivée.</span>
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

