@extends('layouts.admin-master')

@section('title', 'CRM - Contact : ' . $contact->first_name)
@section('page-title', 'Fiche Contact')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-2">
                <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                <li class="breadcrumb-item"><a href="{{ route('crm.contacts.index') }}">Contacts</a></li>
                <li class="breadcrumb-item active">{{ $contact->first_name }}</li>
            </ol>
        </nav>

        <div class="row">
            {{-- Profil Contact --}}
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="h2 mb-0">{{ strtoupper(substr($contact->first_name, 0, 1)) }}{{ strtoupper(substr($contact->last_name ?? '', 0, 1)) }}</span>
                        </div>
                        <h4 class="mb-1">{{ $contact->first_name }} {{ $contact->last_name }}</h4>
                        @if($contact->position)
                            <p class="text-muted mb-2">{{ $contact->position }}</p>
                        @endif
                        @if($contact->company)
                            <p class="mb-3"><strong>{{ $contact->company }}</strong></p>
                        @endif
                        
                        <div class="mb-3">
                            @switch($contact->type)
                                @case('lead') <span class="badge bg-warning py-2 px-3">Lead</span> @break
                                @case('client') <span class="badge bg-success py-2 px-3">Client</span> @break
                                @case('partner') <span class="badge bg-info py-2 px-3">Partenaire</span> @break
                                @default <span class="badge bg-secondary py-2 px-3">{{ $contact->type }}</span>
                            @endswitch
                        </div>

                        <hr>

                        @if($contact->email)
                        <p class="mb-2">
                            <span class="icon-envelope mr-2"></span>
                            <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                        </p>
                        @endif
                        @if($contact->phone)
                        <p class="mb-2">
                            <span class="icon-phone mr-2"></span>
                            <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>
                        </p>
                        @endif
                        @if($contact->address)
                        <p class="mb-0 text-muted small">
                            <span class="icon-map-marker mr-2"></span>
                            {{ $contact->address }}
                        </p>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-0">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('crm.contacts.edit', $contact) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                            <form action="{{ route('crm.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Supprimer ce contact ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tags --}}
                @if($contact->tags && count($contact->tags) > 0)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="mb-3">🏷️ Tags</h6>
                        @foreach($contact->tags as $tag)
                            <span class="badge bg-light mr-1 mb-1">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Opportunités & Interactions --}}
            <div class="col-lg-8">
                
                {{-- Interactions --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">💬 Interactions</h5>
                        <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#collapseInteraction" aria-expanded="false" aria-controls="collapseInteraction">
                            + Nouvelle
                        </button>
                    </div>
                    <div class="collapse" id="collapseInteraction">
                        <div class="card-body bg-light border-bottom">
                            <form action="{{ route('crm.interactions.store', $contact) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select name="type" class="form-control" required>
                                                <option value="call">Appel</option>
                                                <option value="email">Email</option>
                                                <option value="meeting">Réunion</option>
                                                <option value="note">Note</option>
                                                <option value="other">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Date</label>
                                            <input type="datetime-local" name="date" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Résumé</label>
                                    <input type="text" name="summary" class="form-control" placeholder="Sujet de l'interaction" required>
                                </div>
                                <div class="form-group">
                                    <label>Détails</label>
                                    <textarea name="details" class="form-control" rows="3" placeholder="Détails..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($contact->interactions && $contact->interactions->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($contact->interactions->sortByDesc('date') as $interaction)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                @switch($interaction->type)
                                                    @case('call') 📞 @break
                                                    @case('email') 📧 @break
                                                    @case('meeting') 🤝 @break
                                                    @case('note') 📝 @break
                                                    @default 📌
                                                @endswitch
                                                {{ $interaction->summary }}
                                            </h6>
                                            <small class="text-muted">{{ $interaction->date->format('d/m/Y H:i') }}</small>
                                        </div>
                                        <p class="mb-1 text-muted small">{{ $interaction->details }}</p>
                                        <div class="text-right">
                                            <small>Par {{ $interaction->user->name ?? 'Inconnu' }}</small>
                                            <form action="{{ route('crm.interactions.destroy', $interaction) }}" method="POST" class="d-inline ml-2" onsubmit="return confirm('Supprimer ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger p-0" style="font-size: 0.8rem;">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucune interaction enregistrée</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Opportunités --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">🎯 Opportunités</h5>
                        <a href="{{ route('crm.opportunities.create') }}?contact_id={{ $contact->id }}" class="btn btn-sm btn-primary">+ Nouvelle</a>
                    </div>
                    <div class="card-body p-0">
                        @if($contact->opportunities->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0">Titre</th>
                                            <th class="border-0">Valeur</th>
                                            <th class="border-0">Étape</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contact->opportunities as $opp)
                                        <tr>
                                            <td>{{ $opp->title }}</td>
                                            <td>{{ number_format($opp->value, 0, ',', ' ') }} FCFA</td>
                                            <td>
                                                @switch($opp->stage)
                                                    @case('won') <span class="badge bg-success">Gagnée</span> @break
                                                    @case('lost') <span class="badge bg-danger">Perdue</span> @break
                                                    @default <span class="badge bg-info">{{ $opp->stage }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucune opportunité</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Infos supplémentaires --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">📋 Informations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Source :</strong> {{ $contact->source ?? 'Non renseignée' }}</p>
                                <p class="mb-2"><strong>Statut :</strong> 
                                    @if($contact->status === 'active')
                                        <span class="badge bg-success">Actif</span>
                                    @elseif($contact->status === 'prospect')
                                        <span class="badge bg-warning">Prospect</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Créé le :</strong> {{ $contact->created_at->format('d/m/Y H:i') }}</p>
                                <p class="mb-0"><strong>Modifié le :</strong> {{ $contact->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection
