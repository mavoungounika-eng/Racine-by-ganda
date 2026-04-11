@extends('layouts.creator')

@section('title', 'Mon Profil Créateur - RACINE BY GANDA')
@section('page-title', 'Mon Profil')

@push('styles')
<style>
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
        margin-bottom: 2rem;
    }
    
    .profile-avatar-section {
        text-align: center;
        padding: 3rem 2rem;
        background: linear-gradient(135deg, #F8F6F3 0%, #E5DDD3 100%);
        border-radius: 24px;
        margin-bottom: 2rem;
        border: 2px solid rgba(212, 165, 116, 0.2);
    }
    
    .profile-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        box-shadow: 0 12px 32px rgba(237, 95, 30, 0.4);
        border: 5px solid white;
        font-size: 3.5rem;
        font-weight: 700;
        color: white;
        font-family: 'Playfair Display', serif;
    }
    
    .profile-name {
        font-family: 'Libre Baskerville', serif;
        font-size: 2rem;
        font-weight: 400;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .profile-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 999px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .section-title {
        font-family: 'Libre Baskerville', serif;
        font-size: 1.5rem;
        font-weight: 400;
        color: #2C1810;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #E5DDD3;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .section-title i {
        color: #ED5F1E;
        font-size: 1.35rem;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .info-item {
        padding: 1.5rem;
        background: linear-gradient(135deg, #F8F6F3 0%, white 100%);
        border-radius: 16px;
        border: 1px solid #E5DDD3;
        transition: all 0.3s;
    }
    
    .info-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border-color: rgba(212, 165, 116, 0.3);
    }
    
    .info-label {
        font-size: 0.875rem;
        color: #8B7355;
        margin-bottom: 0.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        text-decoration: none;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .premium-btn-secondary {
        background: white;
        color: #2C1810;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    
    .premium-btn-secondary:hover {
        background: #F8F6F3;
        border-color: #D4A574;
        color: #2C1810;
    }
    
    .link-premium {
        color: #D4A574;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .link-premium:hover {
        color: #ED5F1E;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- AVATAR SECTION --}}
    <div class="profile-avatar-section">
        <div class="profile-avatar">
            {{ strtoupper(substr($creatorProfile->brand_name ?? Auth::user()->name ?? 'C', 0, 1)) }}
        </div>
        <h2 class="profile-name">{{ $creatorProfile->brand_name ?? Auth::user()->name ?? 'Créateur' }}</h2>
        <span class="profile-badge {{ $creatorProfile->status ?? 'active' }}" 
              style="background: {{ $creatorProfile->status === 'active' ? 'rgba(34, 197, 94, 0.1)' : ($creatorProfile->status === 'pending' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(239, 68, 68, 0.1)') }}; 
                     color: {{ $creatorProfile->status === 'active' ? '#22C55E' : ($creatorProfile->status === 'pending' ? '#F59E0B' : '#EF4444') }}; 
                     border: 2px solid {{ $creatorProfile->status === 'active' ? 'rgba(34, 197, 94, 0.2)' : ($creatorProfile->status === 'pending' ? 'rgba(234, 179, 8, 0.2)' : 'rgba(239, 68, 68, 0.2)') }};">
            <i class="fas fa-{{ $creatorProfile->status === 'active' ? 'check-circle' : ($creatorProfile->status === 'pending' ? 'clock' : 'ban') }}"></i>
            {{ $creatorProfile->status === 'active' ? 'Compte Actif' : ($creatorProfile->status === 'pending' ? 'En Attente' : 'Suspendu') }}
        </span>
    </div>

    {{-- INFORMATIONS GÉNÉRALES --}}
    <div class="premium-card">
        <h3 class="section-title">
            <i class="fas fa-info-circle"></i>
            Informations Générales
        </h3>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nom de la marque</div>
                <div class="info-value">{{ $creatorProfile->brand_name ?? 'Non défini' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ Auth::user()->email ?? 'N/A' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Type d'activité</div>
                <div class="info-value">{{ ucfirst($creatorProfile->type ?? 'Non défini') }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Statut légal</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $creatorProfile->legal_status ?? 'Non défini')) }}</div>
            </div>
            
            @if($creatorProfile->location)
            <div class="info-item">
                <div class="info-label">Localisation</div>
                <div class="info-value">{{ $creatorProfile->location }}</div>
            </div>
            @endif
            
            @if($creatorProfile->registration_number)
            <div class="info-item">
                <div class="info-label">Numéro d'enregistrement</div>
                <div class="info-value">{{ $creatorProfile->registration_number }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- DESCRIPTION --}}
    @if($creatorProfile->bio)
    <div class="premium-card">
        <h3 class="section-title">
            <i class="fas fa-align-left"></i>
            À propos
        </h3>
        <p style="color: #2C1810; line-height: 1.8; font-size: 1.05rem; padding: 1.5rem; background: linear-gradient(135deg, #F8F6F3 0%, white 100%); border-radius: 16px; border: 1px solid #E5DDD3;">{{ $creatorProfile->bio }}</p>
    </div>
    @endif

    {{-- RÉSEAUX SOCIAUX --}}
    @if($creatorProfile->instagram || $creatorProfile->facebook || $creatorProfile->website)
    <div class="premium-card">
        <h3 class="section-title">
            <i class="fas fa-share-alt"></i>
            Réseaux Sociaux & Site Web
        </h3>
        
        <div class="info-grid">
            @if($creatorProfile->website)
            <div class="info-item">
                <div class="info-label">Site Web</div>
                <div class="info-value">
                    <a href="{{ $creatorProfile->website }}" target="_blank" class="link-premium">
                        {{ $creatorProfile->website }}
                        <i class="fas fa-external-link-alt ml-2" style="font-size: 0.75rem;"></i>
                    </a>
                </div>
            </div>
            @endif
            
            @if($creatorProfile->instagram)
            <div class="info-item">
                <div class="info-label">Instagram</div>
                <div class="info-value">
                    <a href="https://instagram.com/{{ $creatorProfile->instagram }}" target="_blank" class="link-premium">
                        @{{ $creatorProfile->instagram }}
                        <i class="fas fa-external-link-alt ml-2" style="font-size: 0.75rem;"></i>
                    </a>
                </div>
            </div>
            @endif
            
            @if($creatorProfile->facebook)
            <div class="info-item">
                <div class="info-label">Facebook</div>
                <div class="info-value">
                    <a href="https://facebook.com/{{ $creatorProfile->facebook }}" target="_blank" class="link-premium">
                        {{ $creatorProfile->facebook }}
                        <i class="fas fa-external-link-alt ml-2" style="font-size: 0.75rem;"></i>
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- INFORMATIONS DE PAIEMENT --}}
    <div class="premium-card">
        <h3 class="section-title">
            <i class="fas fa-wallet"></i>
            Informations de Paiement
        </h3>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Méthode de paiement</div>
                <div class="info-value">
                    @if($creatorProfile->payout_method === 'bank')
                        Virement bancaire
                    @elseif($creatorProfile->payout_method === 'mobile_money')
                        Mobile Money
                    @else
                        Autre
                    @endif
                </div>
            </div>
        </div>
        
        @if($creatorProfile->payout_details)
        <div style="margin-top: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #F8F6F3 0%, white 100%); border-radius: 16px; border: 1px solid #E5DDD3;">
            <div class="info-label mb-3">Détails de paiement</div>
            <pre style="margin: 0; color: #2C1810; font-size: 0.9rem; white-space: pre-wrap; font-family: 'Inter', sans-serif;">{{ json_encode($creatorProfile->payout_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif
    </div>

    {{-- ACTIONS --}}
    <div class="premium-card">
        <h3 class="section-title">
            <i class="fas fa-cog"></i>
            Actions
        </h3>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <button type="button" class="premium-btn" onclick="alert('Fonctionnalité à venir : Modification du profil')">
                <i class="fas fa-edit"></i>
                Modifier mon profil
            </button>
            
            <a href="{{ route('creator.dashboard') }}" class="premium-btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</div>
@endsection
