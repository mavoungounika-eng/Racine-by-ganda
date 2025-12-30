@extends('layouts.admin-master')

@section('title', 'Détails Créateur - ' . $creator->brand_name)
@section('page-title', 'Détails Créateur')
@section('page-subtitle', $creator->brand_name)

@section('content')

{{-- En-tête avec actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            <i class="fas fa-user text-racine-orange me-2"></i>
            {{ $creator->brand_name }}
        </h2>
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Profil et documents du créateur
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.creators.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Retour à la liste
        </a>
        <form action="{{ route('admin.creators.verify', $creator->id) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" 
                    class="btn {{ $creator->is_verified ? 'btn-warning' : 'btn-success' }}"
                    onclick="return confirm('Êtes-vous sûr de vouloir {{ $creator->is_verified ? 'retirer la vérification' : 'vérifier' }} ce créateur ?')">
                <i class="fas fa-{{ $creator->is_verified ? 'times' : 'check' }} me-2"></i>
                {{ $creator->is_verified ? 'Retirer la vérification' : 'Vérifier le créateur' }}
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    {{-- Informations générales --}}
    <div class="col-lg-8">
        {{-- Profil créateur --}}
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-info-circle text-racine-orange me-2"></i>
                    Informations du créateur
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Nom de la marque</label>
                            <div class="fw-semibold text-racine-black">{{ $creator->brand_name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Nom complet</label>
                            <div class="fw-semibold text-racine-black">{{ $creator->user->name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Email</label>
                            <div class="text-racine-black">
                                <i class="fas fa-envelope me-1 text-muted"></i>
                                {{ $creator->user->email }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Téléphone</label>
                            <div class="text-racine-black">
                                <i class="fas fa-phone me-1 text-muted"></i>
                                {{ $creator->user->phone ?? 'Non renseigné' }}
                            </div>
                        </div>
                    </div>
                    @if($creator->location)
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Localisation</label>
                            <div class="text-racine-black">
                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                {{ $creator->location }}
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($creator->legal_status)
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Statut légal</label>
                            <div class="text-racine-black">{{ $creator->legal_status }}</div>
                        </div>
                    </div>
                    @endif
                    @if($creator->registration_number)
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Numéro d'enregistrement</label>
                            <div class="text-racine-black">
                                <code>{{ $creator->registration_number }}</code>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($creator->type)
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Type d'activité</label>
                            <div class="text-racine-black">{{ $creator->type }}</div>
                        </div>
                    </div>
                    @endif
                    @if($creator->bio)
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Biographie</label>
                            <div class="text-racine-black">{{ $creator->bio }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Statut</label>
                            <div>
                                @if($creator->is_verified)
                                    <span class="badge bg-success rounded-pill me-2">
                                        <i class="fas fa-check-circle me-1"></i>Vérifié
                                    </span>
                                @else
                                    <span class="badge bg-warning rounded-pill me-2">
                                        <i class="fas fa-clock me-1"></i>En attente de vérification
                                    </span>
                                @endif
                                @if($creator->status === 'active' && $creator->is_active)
                                    <span class="badge bg-primary rounded-pill me-2">
                                        <i class="fas fa-check me-1"></i>Actif
                                    </span>
                                @elseif($creator->status === 'suspended')
                                    <span class="badge bg-danger rounded-pill me-2">
                                        <i class="fas fa-ban me-1"></i>Suspendu
                                    </span>
                                @else
                                    <span class="badge bg-secondary rounded-pill me-2">
                                        <i class="fas fa-pause me-1"></i>Inactif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-file-alt text-racine-orange me-2"></i>
                    Documents fournis
                    <span class="badge bg-info ms-2">{{ $creator->documents()->count() }}</span>
                </h5>
            </div>
            <div class="card-body">
                @php
                    $documents = $creator->documents()->orderBy('created_at', 'desc')->get();
                @endphp
                @if($documents->count() > 0)
                    <div class="row g-3">
                        @foreach($documents as $document)
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-semibold">
                                                <i class="fas fa-file-{{ $document->isImage() ? 'image' : 'alt' }} text-racine-orange me-2"></i>
                                                {{ $document->document_type_label }}
                                            </h6>
                                            <p class="small text-muted mb-0">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $document->created_at->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                        @if($document->is_verified)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Vérifié
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>En attente
                                            </span>
                                        @endif
                                    </div>
                                    @if($document->description)
                                        <p class="small text-muted mb-2">{{ $document->description }}</p>
                                    @endif
                                    <div class="d-flex gap-2">
                                        <a href="{{ $document->url }}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>
                                            Voir
                                        </a>
                                        <a href="{{ $document->url }}" 
                                           download 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-download me-1"></i>
                                            Télécharger
                                        </a>
                                        @if(!$document->is_verified)
                                        <form action="{{ route('admin.creators.documents.verify', $document->id) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Vérifier ce document ?')">
                                                <i class="fas fa-check me-1"></i>
                                                Vérifier
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                    @if($document->is_verified && $document->verifier)
                                        <div class="mt-2 small text-muted">
                                            <i class="fas fa-user-check me-1"></i>
                                            Vérifié par {{ $document->verifier->name }} le {{ $document->verified_at->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3 opacity-50"></i>
                        <p class="text-muted mb-0">Aucun document fourni par ce créateur</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Logo et bannière --}}
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-image text-racine-orange me-2"></i>
                    Visuels
                </h5>
            </div>
            <div class="card-body text-center">
                @if($creator->logo_path)
                    <img src="{{ asset('storage/' . $creator->logo_path) }}" 
                         alt="Logo {{ $creator->brand_name }}" 
                         class="img-fluid rounded mb-3" 
                         style="max-height: 150px;">
                @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" 
                         style="height: 150px;">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                @endif
                @if($creator->banner_path)
                    <img src="{{ asset('storage/' . $creator->banner_path) }}" 
                         alt="Bannière {{ $creator->brand_name }}" 
                         class="img-fluid rounded" 
                         style="max-height: 100px;">
                @endif
            </div>
        </div>

        {{-- Statistiques --}}
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-bar text-racine-orange me-2"></i>
                    Statistiques
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Produits</span>
                        <span class="fw-semibold">{{ $creator->products()->count() }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Documents</span>
                        <span class="fw-semibold">{{ $creator->documents()->count() }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Documents vérifiés</span>
                        <span class="fw-semibold text-success">{{ $creator->documents()->where('is_verified', true)->count() }}</span>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Inscription</span>
                        <span class="fw-semibold">{{ $creator->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Liens --}}
        @if($creator->website || $creator->instagram_url || $creator->tiktok_url)
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-link text-racine-orange me-2"></i>
                    Liens
                </h5>
            </div>
            <div class="card-body">
                @if($creator->website)
                    <a href="{{ $creator->website }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-globe me-2"></i>
                        Site web
                    </a>
                @endif
                @if($creator->instagram_url)
                    <a href="{{ $creator->instagram_url }}" target="_blank" class="btn btn-outline-danger btn-sm w-100 mb-2">
                        <i class="fab fa-instagram me-2"></i>
                        Instagram
                    </a>
                @endif
                @if($creator->tiktok_url)
                    <a href="{{ $creator->tiktok_url }}" target="_blank" class="btn btn-outline-dark btn-sm w-100">
                        <i class="fab fa-tiktok me-2"></i>
                        TikTok
                    </a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

