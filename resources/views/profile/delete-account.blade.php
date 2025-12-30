@extends('layouts.frontend')

@section('title', 'Supprimer mon compte - RACINE BY GANDA')

@push('styles')
<style>
    .delete-account-hero {
        background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .delete-account-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .warning-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 2px solid #DC2626;
        margin-bottom: 2rem;
    }
    
    .warning-icon {
        font-size: 4rem;
        color: #DC2626;
        margin-bottom: 1.5rem;
    }
    
    .warning-title {
        font-size: 2rem;
        font-weight: 700;
        color: #DC2626;
        margin-bottom: 1rem;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .warning-text {
        color: #6c757d;
        line-height: 1.8;
        margin-bottom: 1.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }
    
    .stat-item {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    .delete-form-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #DC2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        outline: none;
    }
    
    .form-check {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .form-check-input {
        margin-top: 0.25rem;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    .form-check-label {
        color: #6c757d;
        line-height: 1.6;
    }
    
    .btn-delete-account {
        width: 100%;
        background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    
    .btn-delete-account:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 38, 38, 0.4);
    }
    
    .btn-cancel {
        width: 100%;
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        border: 1px solid rgba(108, 117, 125, 0.3);
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        text-decoration: none;
        display: block;
        text-align: center;
        margin-top: 1rem;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: rgba(108, 117, 125, 0.2);
        color: #2C1810;
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="delete-account-hero">
    <div class="container">
        <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
            <i class="fas fa-exclamation-triangle me-3"></i>Supprimer mon compte
        </h1>
        <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.9); margin: 0;">
            Action irréversible - Conformité RGPD
        </p>
    </div>
</section>

<!-- DELETE ACCOUNT CONTENT -->
<section class="delete-account-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- WARNING CARD -->
                <div class="warning-card">
                    <div class="text-center">
                        <div class="warning-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2 class="warning-title">Attention !</h2>
                        <p class="warning-text">
                            La suppression de votre compte est <strong>irréversible</strong>. Toutes vos données personnelles seront :
                        </p>
                        <ul style="text-align: left; color: #6c757d; line-height: 2;">
                            <li>Anonymisées conformément au RGPD</li>
                            <li>Vos commandes seront conservées pour historique mais anonymisées</li>
                            <li>Vos favoris, avis et adresses seront supprimés définitivement</li>
                            <li>Vous ne pourrez plus accéder à votre compte</li>
                        </ul>
                    </div>
                </div>
                
                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">{{ $stats['orders_count'] }}</div>
                        <div class="stat-label">Commandes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $stats['addresses_count'] }}</div>
                        <div class="stat-label">Adresses</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $stats['reviews_count'] }}</div>
                        <div class="stat-label">Avis</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $stats['wishlist_count'] }}</div>
                        <div class="stat-label">Favoris</div>
                    </div>
                </div>
                
                <!-- DELETE FORM -->
                <div class="delete-form-card">
                    <h3 style="font-size: 1.5rem; font-weight: 600; color: #2C1810; margin-bottom: 1.5rem;">
                        <i class="fas fa-key me-2" style="color: #DC2626;"></i>Confirmer la suppression
                    </h3>
                    
                    <form action="{{ route('profile.delete-account.destroy') }}" method="POST" id="deleteAccountForm">
                        @csrf
                        @method('DELETE')
                        
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Mot de passe *
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   required
                                   placeholder="Entrez votre mot de passe pour confirmer">
                            @error('password')
                            <small style="color: #DC2626; margin-top: 0.5rem; display: block;">{{ $message }}</small>
                            @enderror
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" 
                                   name="confirm" 
                                   id="confirm" 
                                   class="form-check-input" 
                                   value="1"
                                   required>
                            <label for="confirm" class="form-check-label">
                                Je comprends que cette action est <strong>irréversible</strong> et que toutes mes données personnelles seront supprimées ou anonymisées conformément au RGPD.
                            </label>
                        </div>
                        @error('confirm')
                        <small style="color: #DC2626; margin-top: 0.5rem; display: block;">{{ $message }}</small>
                        @enderror
                        
                        <button type="submit" class="btn-delete-account" onclick="return confirm('Êtes-vous ABSOLUMENT SÛR de vouloir supprimer votre compte ? Cette action est irréversible !')">
                            <i class="fas fa-trash-alt me-2"></i>Supprimer définitivement mon compte
                        </button>
                    </form>
                    
                    <a href="{{ route('profile.index') }}" class="btn-cancel">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </div>
                
                <!-- EXPORT DATA -->
                <div class="delete-form-card" style="margin-top: 2rem; border: 2px solid #0EA5E9;">
                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #2C1810; margin-bottom: 1rem;">
                        <i class="fas fa-download me-2" style="color: #0EA5E9;"></i>Exporter mes données avant suppression
                    </h3>
                    <p style="color: #6c757d; margin-bottom: 1.5rem;">
                        Avant de supprimer votre compte, vous pouvez télécharger toutes vos données personnelles au format JSON ou CSV.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('profile.data.export', ['format' => 'json']) }}" class="btn" style="background: rgba(14, 165, 233, 0.1); color: #0EA5E9; border: 1px solid #0EA5E9; border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 600; text-decoration: none;">
                            <i class="fas fa-file-code me-2"></i>Export JSON
                        </a>
                        <a href="{{ route('profile.data.export', ['format' => 'csv']) }}" class="btn" style="background: rgba(34, 197, 94, 0.1); color: #22C55E; border: 1px solid #22C55E; border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 600; text-decoration: none;">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mon Profil', 'url' => route('profile.index')],
        ['label' => 'Supprimer mon compte', 'url' => null],
    ],
    'backUrl' => route('profile.index'),
    'backText' => 'Retour au profil',
    'position' => 'bottom',
])
@endsection

