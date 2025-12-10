@extends('layouts.frontend')

@section('title', 'Mon Profil - RACINE BY GANDA')
@section('page-title', 'Mon Profil')
@section('page-subtitle', 'Gérez vos informations personnelles')

@section('content')
<div class="row">
    {{-- Carte Profil --}}
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center py-5">
                {{-- Avatar --}}
                <div class="mb-4 position-relative d-inline-block">
                    <div class="user-avatar-lg mx-auto" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--racine-violet) 0%, var(--racine-violet-dark) 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(75, 29, 242, 0.3);">
                        <span style="color: white; font-size: 3rem; font-weight: 600; font-family: 'Playfair Display', serif;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    </div>
                    <button class="btn btn-sm btn-gold position-absolute" style="bottom: 0; right: calc(50% - 70px); border-radius: 50%; width: 36px; height: 36px; padding: 0;">
                        📷
                    </button>
                </div>

                {{-- Nom & Email --}}
                <h3 style="font-family: 'Playfair Display', serif; color: var(--racine-black); margin-bottom: 0.25rem;">
                    {{ $user->name }}
                </h3>
                <p style="color: var(--racine-gray-dark); margin-bottom: 1rem;">{{ $user->email }}</p>
                
                {{-- Badge de rôle --}}
                @switch($user->role)
                    @case('super_admin')
                        <span class="badge" style="background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); color: white; padding: 0.5rem 1rem; font-size: 0.8rem;">
                            👑 Super Admin
                        </span>
                        @break
                    @case('admin')
                        <span class="badge" style="background: linear-gradient(135deg, var(--racine-gold) 0%, #B8860B 100%); color: var(--racine-black); padding: 0.5rem 1rem; font-size: 0.8rem;">
                            ⚙️ Administrateur
                        </span>
                        @break
                    @case('staff')
                        <span class="badge" style="background: linear-gradient(135deg, #0EA5E9 0%, #0369A1 100%); color: white; padding: 0.5rem 1rem; font-size: 0.8rem;">
                            🛠️ Staff
                        </span>
                        @break
                    @case('createur')
                        <span class="badge" style="background: linear-gradient(135deg, #22C55E 0%, #15803D 100%); color: white; padding: 0.5rem 1rem; font-size: 0.8rem;">
                            🎨 Créateur
                        </span>
                        @break
                    @default
                        <span class="badge" style="background: linear-gradient(135deg, var(--racine-violet) 0%, var(--racine-violet-dark) 100%); color: white; padding: 0.5rem 1rem; font-size: 0.8rem;">
                            👤 Client
                        </span>
                @endswitch
            </div>

            <div style="border-top: 1px solid var(--racine-gray);">
                <div class="px-4 py-3">
                    <div class="d-flex align-items-center mb-3">
                        <span style="width: 40px; height: 40px; background: var(--racine-gray); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-right: 12px;">📱</span>
                        <div>
                            <small style="color: var(--racine-gray-dark);">Téléphone</small>
                            <p class="mb-0" style="font-weight: 500;">{{ $user->phone ?? 'Non renseigné' }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <span style="width: 40px; height: 40px; background: var(--racine-gray); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-right: 12px;">📅</span>
                        <div>
                            <small style="color: var(--racine-gray-dark);">Membre depuis</small>
                            <p class="mb-0" style="font-weight: 500;">{{ $user->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="width: 40px; height: 40px; background: var(--racine-gray); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-right: 12px;">🕐</span>
                        <div>
                            <small style="color: var(--racine-gray-dark);">Dernière activité</small>
                            <p class="mb-0" style="font-weight: 500;">{{ $user->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulaires --}}
    <div class="col-lg-8">
        {{-- Informations personnelles --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
                <span style="font-size: 1.25rem; margin-right: 10px;">✏️</span>
                <h5 class="mb-0">Informations personnelles</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Adresse email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="+242 06 XXX XX XX">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <span class="icon-check mr-2"></span> Enregistrer les modifications
                        </button>
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-2"></i> Modifier toutes les informations
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sécurité --}}
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span style="font-size: 1.25rem; margin-right: 10px;">🔒</span>
                <h5 class="mb-0">Sécurité</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>

                    <div class="p-3 mb-4" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-radius: var(--radius-md);">
                        <small style="color: #92400E;">
                            <strong>💡 Conseils :</strong> Utilisez au moins 8 caractères avec des lettres, chiffres et symboles.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-gold">
                        <span class="icon-lock mr-2"></span> Modifier le mot de passe
                    </button>
                </form>
            </div>
        </div>
        
        {{-- Zone de danger --}}
        <div class="col-12 mt-4">
            <div class="card border-danger" style="border-width: 2px;">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Zone de danger
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 1rem;">
                        <div>
                            <h6 class="mb-1" style="color: #DC2626; font-weight: 600;">Supprimer mon compte</h6>
                            <p class="mb-0" style="color: #6c757d; font-size: 0.9rem;">
                                Supprimez définitivement votre compte et toutes vos données personnelles (conformité RGPD)
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('profile.data.export', ['format' => 'json']) }}" class="btn btn-sm" style="background: rgba(14, 165, 233, 0.1); color: #0EA5E9; border: 1px solid #0EA5E9;">
                                <i class="fas fa-download me-1"></i>Exporter données
                            </a>
                            <a href="{{ route('profile.delete-account') }}" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash-alt me-1"></i>Supprimer le compte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mon Profil', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
@endsection
