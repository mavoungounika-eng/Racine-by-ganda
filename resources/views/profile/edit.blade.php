@php
    $user = Auth::user();
    $roleSlug = $user->getRoleSlug();
    
    // Déterminer le layout selon le rôle
    $layout = match($roleSlug) {
        'super_admin', 'admin', 'staff' => 'layouts.admin',
        'createur' => 'layouts.creator',
        default => 'layouts.frontend',
    };
@endphp

@extends($layout)

@section('title', 'Modifier mon profil - RACINE BY GANDA')

@if($roleSlug === 'createur')
    @section('page-title', 'Modifier mon profil')
@elseif(in_array($roleSlug, ['super_admin', 'admin', 'staff']))
    @section('page-title', 'Modifier mon profil')
@else
    @section('page-title', 'Modifier mon profil')
@endif

@push('styles')
<style>
    .profile-edit-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .profile-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
        border: 1px solid #E5DDD3;
    }
    
    .profile-avatar-section {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #F8F6F3 0%, #E5DDD3 100%);
        border-radius: 16px;
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        box-shadow: 0 8px 24px rgba(237, 95, 30, 0.3);
        border: 4px solid white;
        font-size: 3rem;
        font-weight: 700;
        color: white;
        font-family: 'Playfair Display', serif;
    }
    
    .section-header {
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
    
    .section-header i {
        color: #ED5F1E;
        font-size: 1.35rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border: 1px solid #D4A574;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #ED5F1E;
        box-shadow: 0 0 0 3px rgba(237, 95, 30, 0.1);
        outline: none;
    }
    
    .btn-racine-primary {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }
    
    .btn-racine-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .badge-role {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 999px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .badge-super-admin {
        background: rgba(220, 38, 38, 0.1);
        color: #DC2626;
        border: 2px solid rgba(220, 38, 38, 0.2);
    }
    
    .badge-admin {
        background: rgba(255, 184, 0, 0.1);
        color: #B8860B;
        border: 2px solid rgba(255, 184, 0, 0.2);
    }
    
    .badge-staff {
        background: rgba(14, 165, 233, 0.1);
        color: #0EA5E9;
        border: 2px solid rgba(14, 165, 233, 0.2);
    }
    
    .badge-creator {
        background: rgba(34, 197, 94, 0.1);
        color: #22C55E;
        border: 2px solid rgba(34, 197, 94, 0.2);
    }
    
    .badge-client {
        background: rgba(139, 92, 246, 0.1);
        color: #8B5CF6;
        border: 2px solid rgba(139, 92, 246, 0.2);
    }
</style>
@endpush

@section('content')
<div class="profile-edit-container">
    {{-- AVATAR SECTION --}}
    <div class="profile-avatar-section">
        <div class="profile-avatar">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <h2 style="font-family: 'Libre Baskerville', serif; color: #2C1810; margin-bottom: 0.5rem;">
            {{ $user->name }}
        </h2>
        <span class="badge-role badge-{{ str_replace('_', '-', $roleSlug) }}">
            @switch($roleSlug)
                @case('super_admin')
                    👑 Super Admin
                    @break
                @case('admin')
                    ⚙️ Administrateur
                    @break
                @case('staff')
                    🛠️ Staff
                    @break
                @case('createur')
                    🎨 Créateur
                    @break
                @default
                    👤 Client
            @endswitch
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- INFORMATIONS DE BASE --}}
        <div class="profile-card">
            <h3 class="section-header">
                <i class="fas fa-user"></i>
                Informations personnelles
            </h3>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="name" 
                           id="name"
                           class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $user->name) }}" 
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           class="form-control @error('email') is-invalid @enderror" 
                           value="{{ old('email', $user->email) }}" 
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="text" 
                           name="phone" 
                           id="phone"
                           class="form-control @error('phone') is-invalid @enderror" 
                           value="{{ old('phone', $user->phone) }}" 
                           placeholder="+242 06 XXX XX XX">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if($roleSlug === 'staff')
                <div class="col-md-6 mb-3">
                    <label for="staff_role" class="form-label">Rôle staff</label>
                    <input type="text" 
                           name="staff_role" 
                           id="staff_role"
                           class="form-control @error('staff_role') is-invalid @enderror" 
                           value="{{ old('staff_role', $user->staff_role) }}" 
                           placeholder="Ex: vendeur, caissier, gestionnaire_stock">
                    @error('staff_role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif

                @if(in_array($roleSlug, ['super_admin', 'admin', 'staff']))
                <div class="col-md-6 mb-3">
                    <label for="locale" class="form-label">Langue préférée</label>
                    <select name="locale" 
                            id="locale"
                            class="form-select @error('locale') is-invalid @enderror">
                        <option value="fr" {{ old('locale', $user->locale) === 'fr' ? 'selected' : '' }}>Français</option>
                        <option value="en" {{ old('locale', $user->locale) === 'en' ? 'selected' : '' }}>English</option>
                    </select>
                    @error('locale')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif
            </div>
        </div>

        {{-- EMAIL PROFESSIONNEL ET NOTIFICATIONS --}}
        <div class="profile-card">
            <h3 class="section-header">
                <i class="fas fa-envelope"></i>
                Email professionnel et notifications
            </h3>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="professional_email" class="form-label">
                        Email professionnel
                        @if($user->professional_email && $user->professional_email_verified)
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check-circle"></i> Vérifié
                            </span>
                        @elseif($user->professional_email)
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-exclamation-circle"></i> Non vérifié
                            </span>
                        @endif
                    </label>
                    <div class="input-group">
                        <input type="email" 
                               name="professional_email" 
                               id="professional_email"
                               class="form-control @error('professional_email') is-invalid @enderror" 
                               value="{{ old('professional_email', $user->professional_email) }}" 
                               placeholder="contact@votre-entreprise.com">
                        @if($user->professional_email && !$user->professional_email_verified)
                            <a href="{{ route('profile.verify-email') }}" 
                               class="btn btn-outline-warning" 
                               onclick="event.preventDefault(); document.getElementById('verify-email-form').submit();">
                                <i class="fas fa-check"></i> Vérifier
                            </a>
                        @endif
                    </div>
                    <form id="verify-email-form" action="{{ route('profile.verify-email') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <div class="form-text">
                        <i class="fas fa-info-circle"></i>
                        Utilisez votre email professionnel pour recevoir les notifications et envoyer des emails depuis la messagerie.
                    </div>
                    @error('professional_email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               name="email_notifications_enabled" 
                               id="email_notifications_enabled"
                               class="form-check-input" 
                               value="1"
                               {{ old('email_notifications_enabled', $user->email_notifications_enabled) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_notifications_enabled">
                            <strong>Recevoir les notifications par email</strong>
                            <br>
                            <small class="text-muted">Vous recevrez un email à chaque nouveau message dans vos conversations.</small>
                        </label>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               name="email_messaging_enabled" 
                               id="email_messaging_enabled"
                               class="form-check-input" 
                               value="1"
                               {{ old('email_messaging_enabled', $user->email_messaging_enabled) ? 'checked' : '' }}
                               {{ !$user->hasVerifiedProfessionalEmail() ? 'disabled' : '' }}>
                        <label class="form-check-label" for="email_messaging_enabled">
                            <strong>Activer l'envoi d'emails depuis la messagerie</strong>
                            <br>
                            <small class="text-muted">
                                Permet d'envoyer des emails directement depuis la messagerie interne.
                                @if(!$user->hasVerifiedProfessionalEmail())
                                    <span class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Un email professionnel vérifié est requis.
                                    </span>
                                @endif
                            </small>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- PROFIL CRÉATEUR (si applicable) --}}
        @if($user->isCreator() && $creatorProfile)
        <div class="profile-card">
            <h3 class="section-header">
                <i class="fas fa-palette"></i>
                Informations créateur
            </h3>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="brand_name" class="form-label">Nom de la marque <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="brand_name" 
                           id="brand_name"
                           class="form-control @error('brand_name') is-invalid @enderror" 
                           value="{{ old('brand_name', $creatorProfile->brand_name) }}" 
                           required>
                    @error('brand_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="type" class="form-label">Type d'activité</label>
                    <input type="text" 
                           name="type" 
                           id="type"
                           class="form-control @error('type') is-invalid @enderror" 
                           value="{{ old('type', $creatorProfile->type) }}" 
                           placeholder="Ex: Mode, Bijouterie, Accessoires">
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="bio" class="form-label">Biographie</label>
                <textarea name="bio" 
                          id="bio"
                          class="form-control @error('bio') is-invalid @enderror" 
                          rows="4"
                          placeholder="Décrivez votre marque, votre univers créatif...">{{ old('bio', $creatorProfile->bio) }}</textarea>
                @error('bio')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="location" class="form-label">Localisation</label>
                    <input type="text" 
                           name="location" 
                           id="location"
                           class="form-control @error('location') is-invalid @enderror" 
                           value="{{ old('location', $creatorProfile->location) }}" 
                           placeholder="Ex: Brazzaville, Congo">
                    @error('location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="legal_status" class="form-label">Statut légal</label>
                    <input type="text" 
                           name="legal_status" 
                           id="legal_status"
                           class="form-control @error('legal_status') is-invalid @enderror" 
                           value="{{ old('legal_status', $creatorProfile->legal_status) }}" 
                           placeholder="Ex: auto-entrepreneur, SARL">
                    @error('legal_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="registration_number" class="form-label">Numéro d'enregistrement</label>
                <input type="text" 
                       name="registration_number" 
                       id="registration_number"
                       class="form-control @error('registration_number') is-invalid @enderror" 
                       value="{{ old('registration_number', $creatorProfile->registration_number) }}">
                @error('registration_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <hr class="my-4">

            <h4 class="h5 mb-3" style="color: #2C1810;">
                <i class="fas fa-globe me-2"></i>Réseaux sociaux & Site web
            </h4>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="website" class="form-label">Site web</label>
                    <input type="url" 
                           name="website" 
                           id="website"
                           class="form-control @error('website') is-invalid @enderror" 
                           value="{{ old('website', $creatorProfile->website) }}" 
                           placeholder="https://votresite.com">
                    @error('website')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="instagram_url" class="form-label">Instagram</label>
                    <input type="url" 
                           name="instagram_url" 
                           id="instagram_url"
                           class="form-control @error('instagram_url') is-invalid @enderror" 
                           value="{{ old('instagram_url', $creatorProfile->instagram_url) }}" 
                           placeholder="https://instagram.com/votrepseudo">
                    @error('instagram_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tiktok_url" class="form-label">TikTok</label>
                    <input type="url" 
                           name="tiktok_url" 
                           id="tiktok_url"
                           class="form-control @error('tiktok_url') is-invalid @enderror" 
                           value="{{ old('tiktok_url', $creatorProfile->tiktok_url) }}" 
                           placeholder="https://tiktok.com/@votrepseudo">
                    @error('tiktok_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="facebook_url" class="form-label">Facebook</label>
                    <input type="url" 
                           name="facebook_url" 
                           id="facebook_url"
                           class="form-control @error('facebook_url') is-invalid @enderror" 
                           value="{{ old('facebook_url', $creatorProfile->facebook_url ?? '') }}" 
                           placeholder="https://facebook.com/votrepseudo">
                    @error('facebook_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- BOUTONS D'ACTION --}}
        <div class="profile-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <button type="submit" class="btn btn-racine-primary">
                        <i class="fas fa-save me-2"></i>
                        Enregistrer les modifications
                    </button>
                </div>
                <div>
                    @if($roleSlug === 'createur')
                        <a href="{{ route('creator.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                    @elseif(in_array($roleSlug, ['super_admin', 'admin', 'staff']))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                    @else
                        <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- MODIFICATION MOT DE PASSE --}}
    <div class="profile-card">
        <h3 class="section-header">
            <i class="fas fa-lock"></i>
            Sécurité
        </h3>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                <input type="password" 
                       name="current_password" 
                       id="current_password"
                       class="form-control @error('current_password') is-invalid @enderror" 
                       required>
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="form-control @error('password') is-invalid @enderror" 
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation"
                           class="form-control" 
                           required>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Conseils :</strong> Utilisez au moins 8 caractères avec des lettres, chiffres et symboles.
            </div>

            <button type="submit" class="btn btn-outline-primary">
                <i class="fas fa-key me-2"></i>
                Modifier le mot de passe
            </button>
        </form>
    </div>
</div>
@endsection

