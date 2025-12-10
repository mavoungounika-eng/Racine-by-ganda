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

        {{-- Checklist de validation --}}
        @php
            $checklist = $creator->validationChecklist()->orderBy('order')->get();
            $completionPercentage = \App\Models\CreatorValidationChecklist::getCompletionPercentage($creator->id);
            $requiredCompletionPercentage = \App\Models\CreatorValidationChecklist::getRequiredCompletionPercentage($creator->id);
        @endphp
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-clipboard-check text-racine-orange me-2"></i>
                        Checklist de validation
                    </h5>
                    <div>
                        <span class="badge bg-{{ $completionPercentage >= 100 ? 'success' : ($completionPercentage >= 75 ? 'warning' : 'danger') }} rounded-pill">
                            {{ $completionPercentage }}% complété
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($checklist->count() > 0)
                    <div class="mb-3">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $completionPercentage >= 100 ? 'success' : ($completionPercentage >= 75 ? 'warning' : 'danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $completionPercentage }}%"
                                 aria-valuenow="{{ $completionPercentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span>Éléments requis : {{ $requiredCompletionPercentage }}%</span>
                            <span>{{ $checklist->where('is_completed', true)->count() }} / {{ $checklist->count() }} complétés</span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($checklist as $item)
                        <div class="list-group-item border-0 px-0 py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    @if($item->is_completed)
                                        <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                                    @else
                                        <i class="far fa-circle text-muted me-3 fa-lg"></i>
                                    @endif
                                    <div>
                                        <div class="fw-semibold {{ $item->is_completed ? 'text-success' : 'text-racine-black' }}">
                                            {{ $item->item_label }}
                                            @if($item->is_required)
                                                <span class="badge bg-danger ms-2">Requis</span>
                                            @endif
                                        </div>
                                        @if($item->is_completed && $item->completedByUser)
                                            <div class="small text-muted">
                                                <i class="fas fa-user-check me-1"></i>
                                                Complété par {{ $item->completedByUser->name }} le {{ $item->completed_at->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                        @if($item->notes)
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-sticky-note me-1"></i>
                                                {{ $item->notes }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if(!$item->is_completed)
                                    <form action="{{ route('admin.creators.checklist.complete', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('Marquer cet élément comme complété ?')">
                                            <i class="fas fa-check me-1"></i>
                                            Marquer complété
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.creators.checklist.uncomplete', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-warning"
                                                onclick="return confirm('Marquer cet élément comme non complété ?')">
                                            <i class="fas fa-undo me-1"></i>
                                            Annuler
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3 opacity-50"></i>
                        <p class="text-muted mb-0">Aucune checklist disponible</p>
                        <form action="{{ route('admin.creators.checklist.initialize', $creator->id) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-racine-orange">
                                <i class="fas fa-plus me-2"></i>
                                Initialiser la checklist
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Notes internes --}}
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-sticky-note text-racine-orange me-2"></i>
                        Notes internes
                        <span class="badge bg-info ms-2">{{ $creator->adminNotes()->count() }}</span>
                    </h5>
                    <button type="button" class="btn btn-sm btn-racine-orange" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="fas fa-plus me-1"></i>
                        Ajouter une note
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($creator->adminNotes->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($creator->adminNotes as $note)
                        <div class="list-group-item border-0 px-0 py-3 {{ $note->is_pinned ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        @if($note->is_pinned)
                                            <i class="fas fa-thumbtack text-warning"></i>
                                        @endif
                                        @if($note->is_important)
                                            <i class="fas fa-exclamation-circle text-danger"></i>
                                        @endif
                                        <strong class="text-racine-black">{{ $note->creator->name }}</strong>
                                        <span class="text-muted small">{{ $note->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="mb-2 text-racine-black">{{ $note->note }}</p>
                                    @if($note->tags && count($note->tags) > 0)
                                        <div class="d-flex gap-1 flex-wrap">
                                            @foreach($note->tags as $tag)
                                                <span class="badge bg-secondary small">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editNote({{ $note->id }}, '{{ addslashes($note->note) }}', {{ json_encode($note->tags) }}, {{ $note->is_important ? 'true' : 'false' }}, {{ $note->is_pinned ? 'true' : 'false' }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.creators.notes.destroy', $note->id) }}" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Supprimer cette note ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-sticky-note fa-3x text-muted mb-3 opacity-50"></i>
                        <p class="text-muted mb-0">Aucune note interne</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Historique des actions --}}
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-history text-racine-orange me-2"></i>
                    Historique des actions
                </h5>
            </div>
            <div class="card-body">
                @php
                    $activityLogs = $creator->activityLogs()->with('user')->latest()->take(20)->get();
                @endphp
                @if($activityLogs->count() > 0)
                    <div class="timeline">
                        @foreach($activityLogs as $log)
                        <div class="timeline-item mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-racine-orange bg-opacity-10 d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-{{ $log->action === 'verified' ? 'check-circle' : ($log->action === 'status_changed' ? 'exchange-alt' : 'info-circle') }} text-racine-orange"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <strong class="text-racine-black">{{ $log->action_label }}</strong>
                                            <span class="text-muted small ms-2">par {{ $log->user->name }}</span>
                                        </div>
                                        <span class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    @if($log->description)
                                        <p class="mb-0 small text-muted">{{ $log->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3 opacity-50"></i>
                        <p class="text-muted mb-0">Aucune action enregistrée</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Documents --}}
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-file-alt text-racine-orange me-2"></i>
                    Documents fournis
                    <span class="badge bg-info ms-2">{{ $creator->documents_count ?? 0 }}</span>
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

        {{-- Scores --}}
        @if($creator->overall_score !== null)
        <div class="card card-racine mb-4">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-star text-racine-orange me-2"></i>
                    Scores
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Score global</span>
                        <span class="fw-bold h5 mb-0 text-racine-orange">{{ number_format($creator->overall_score, 1) }}/100</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-racine-orange" 
                             role="progressbar" 
                             style="width: {{ $creator->overall_score }}%"
                             aria-valuenow="{{ $creator->overall_score }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Qualité</span>
                        <span class="fw-semibold">{{ number_format($creator->quality_score ?? 0, 1) }}/100</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Complétude</span>
                        <span class="fw-semibold">{{ number_format($creator->completeness_score ?? 0, 1) }}/100</span>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Performance</span>
                        <span class="fw-semibold">{{ number_format($creator->performance_score ?? 0, 1) }}/100</span>
                    </div>
                </div>
                @if($creator->last_score_calculated_at)
                    <div class="mt-3 small text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Dernière mise à jour : {{ $creator->last_score_calculated_at->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </div>
        @endif

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
                        <span class="fw-semibold">{{ $creator->documents_count ?? 0 }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Documents vérifiés</span>
                        <span class="fw-semibold text-success">{{ $creator->verified_documents_count ?? 0 }}</span>
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

<!-- Modal Ajouter/Modifier Note -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="addNoteModalLabel">
                    <i class="fas fa-sticky-note text-racine-orange me-2"></i>
                    <span id="noteModalTitle">Ajouter une note</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="noteForm" method="POST" action="">
                @csrf
                <div id="noteFormMethod"></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="note" class="form-label fw-semibold">Note</label>
                        <textarea name="note" id="note" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="tags" class="form-label fw-semibold">Tags (séparés par des virgules)</label>
                        <input type="text" name="tags" id="tags" class="form-control" 
                               placeholder="urgent, follow_up, issue">
                        <div class="form-text small">Tags disponibles : urgent, follow_up, issue, positive, warning, info, contact, payment, document</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_important" id="is_important" value="1">
                                <label class="form-check-label" for="is_important">
                                    <i class="fas fa-exclamation-circle text-danger me-1"></i>
                                    Important
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned" value="1">
                                <label class="form-check-label" for="is_pinned">
                                    <i class="fas fa-thumbtack text-warning me-1"></i>
                                    Épingler
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-racine-orange">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editNote(id, note, tags, isImportant, isPinned) {
    document.getElementById('noteModalTitle').textContent = 'Modifier la note';
    document.getElementById('noteForm').action = '{{ route('admin.creators.notes.update', ':id') }}'.replace(':id', id);
    document.getElementById('noteFormMethod').innerHTML = '@method("PUT")';
    document.getElementById('note').value = note;
    document.getElementById('tags').value = tags ? tags.join(', ') : '';
    document.getElementById('is_important').checked = isImportant;
    document.getElementById('is_pinned').checked = isPinned;
    
    const modal = new bootstrap.Modal(document.getElementById('addNoteModal'));
    modal.show();
}

document.getElementById('addNoteModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('noteModalTitle').textContent = 'Ajouter une note';
    document.getElementById('noteForm').action = '{{ route('admin.creators.notes.store', $creator->id) }}';
    document.getElementById('noteFormMethod').innerHTML = '';
    document.getElementById('note').value = '';
    document.getElementById('tags').value = '';
    document.getElementById('is_important').checked = false;
    document.getElementById('is_pinned').checked = false;
});
</script>
@endpush

@endsection

