@extends('layouts.admin-master')

@section('title', 'Interactions CRM')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📞 Interactions CRM</h1>
            <p class="text-muted mb-0">Historique de toutes les interactions avec les contacts</p>
        </div>
        <a href="{{ route('crm.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('crm.interactions.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="">Tous les types</option>
                        <option value="call" {{ request('type') == 'call' ? 'selected' : '' }}>Appel</option>
                        <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="meeting" {{ request('type') == 'meeting' ? 'selected' : '' }}>Réunion</option>
                        <option value="note" {{ request('type') == 'note' ? 'selected' : '' }}>Note</option>
                        <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="contact" class="form-label">Contact</label>
                    <input type="text" class="form-control" id="contact" name="contact" 
                           value="{{ request('contact') }}" placeholder="Nom du contact">
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Résumé ou détails">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Résumé</th>
                            <th>Utilisateur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($interactions as $interaction)
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        {{ $interaction->date ? \Carbon\Carbon::parse($interaction->date)->format('d/m/Y') : $interaction->created_at->format('d/m/Y') }}
                                        <br>
                                        {{ $interaction->created_at->format('H:i') }}
                                    </small>
                                </td>
                                <td>
                                    @switch($interaction->type)
                                        @case('call')
                                            <span class="badge bg-primary">
                                                <i class="fas fa-phone"></i> Appel
                                            </span>
                                            @break
                                        @case('email')
                                            <span class="badge bg-info">
                                                <i class="fas fa-envelope"></i> Email
                                            </span>
                                            @break
                                        @case('meeting')
                                            <span class="badge bg-success">
                                                <i class="fas fa-handshake"></i> Réunion
                                            </span>
                                            @break
                                        @case('note')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-sticky-note"></i> Note
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-circle"></i> Autre
                                            </span>
                                    @endswitch
                                </td>
                                <td>
                                    <a href="{{ route('crm.contacts.show', $interaction->contact) }}">
                                        {{ $interaction->contact->first_name }} {{ $interaction->contact->last_name }}
                                    </a>
                                    @if($interaction->contact->company)
                                        <br><small class="text-muted">{{ $interaction->contact->company }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ Str::limit($interaction->summary, 50) }}</strong>
                                    @if($interaction->details)
                                        <br><small class="text-muted">{{ Str::limit($interaction->details, 80) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $interaction->user->name ?? 'Système' }}</small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#interactionModal{{ $interaction->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <form action="{{ route('crm.interactions.destroy', $interaction) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Supprimer cette interaction ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal Détail Interaction -->
                            <div class="modal fade" id="interactionModal{{ $interaction->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                @switch($interaction->type)
                                                    @case('call') 📞 Appel @break
                                                    @case('email') 📧 Email @break
                                                    @case('meeting') 🤝 Réunion @break
                                                    @case('note') 📌 Note @break
                                                    @default 📋 Interaction
                                                @endswitch
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>Contact :</strong><br>
                                                <a href="{{ route('crm.contacts.show', $interaction->contact) }}">
                                                    {{ $interaction->contact->first_name }} {{ $interaction->contact->last_name }}
                                                </a>
                                                @if($interaction->contact->company)
                                                    <br><small class="text-muted">{{ $interaction->contact->company }}</small>
                                                @endif
                                            </div>
                                            <div class="mb-3">
                                                <strong>Date :</strong><br>
                                                {{ $interaction->date ? \Carbon\Carbon::parse($interaction->date)->format('d/m/Y à H:i') : $interaction->created_at->format('d/m/Y à H:i') }}
                                            </div>
                                            <div class="mb-3">
                                                <strong>Résumé :</strong><br>
                                                {{ $interaction->summary }}
                                            </div>
                                            @if($interaction->details)
                                            <div class="mb-3">
                                                <strong>Détails :</strong><br>
                                                {{ $interaction->details }}
                                            </div>
                                            @endif
                                            <div class="mb-0">
                                                <strong>Enregistré par :</strong><br>
                                                {{ $interaction->user->name ?? 'Système' }}
                                                <small class="text-muted">({{ $interaction->created_at->diffForHumans() }})</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-comments fa-3x mb-3 d-block"></i>
                                    Aucune interaction enregistrée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($interactions->hasPages())
                <div class="mt-3">
                    {{ $interactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

