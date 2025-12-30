@extends('layouts.admin-master')

@section('title', 'CRM - Contacts')
@section('page-title', 'Contacts')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Contacts</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">👥 Contacts</h1>
                    <div>
                        <a href="{{ route('crm.contacts.export', request()->query()) }}" class="btn btn-success mr-2">
                            <i class="fas fa-file-excel"></i> Exporter
                        </a>
                        <a href="{{ route('crm.contacts.create') }}" class="btn btn-primary">
                            + Nouveau Contact
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        {{-- Stats --}}
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('crm.contacts.index') }}" class="card border-0 shadow-sm text-decoration-none {{ !request('type') ? 'border-primary' : '' }}" style="{{ !request('type') ? 'border: 2px solid #007bff !important;' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0">{{ $stats['total'] }}</h4>
                        <small class="text-muted">Tous</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('crm.contacts.index', ['type' => 'lead']) }}" class="card border-0 shadow-sm text-decoration-none {{ request('type') === 'lead' ? 'bg-warning' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 {{ request('type') === 'lead' ? '' : 'text-warning' }}">{{ $stats['leads'] }}</h4>
                        <small class="text-muted">Leads</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('crm.contacts.index', ['type' => 'client']) }}" class="card border-0 shadow-sm text-decoration-none {{ request('type') === 'client' ? 'bg-success text-white' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 {{ request('type') === 'client' ? '' : 'text-success' }}">{{ $stats['clients'] }}</h4>
                        <small class="{{ request('type') === 'client' ? 'text-white' : 'text-muted' }}">Clients</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('crm.contacts.index', ['type' => 'partner']) }}" class="card border-0 shadow-sm text-decoration-none {{ request('type') === 'partner' ? 'bg-info text-white' : '' }}">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 {{ request('type') === 'partner' ? '' : 'text-info' }}">{{ $stats['partners'] }}</h4>
                        <small class="{{ request('type') === 'partner' ? 'text-white' : 'text-muted' }}">Partenaires</small>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recherche --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('crm.contacts.index') }}" class="row align-items-center">
                    @if(request('type'))
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    @endif
                    <div class="col-md-9 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher par nom, email ou entreprise..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block">Rechercher</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Liste --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($contacts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Nom</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">Email</th>
                                    <th class="border-0">Entreprise</th>
                                    <th class="border-0">Statut</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contacts as $contact)
                                <tr>
                                    <td class="align-middle">
                                        <strong>{{ $contact->first_name }} {{ $contact->last_name }}</strong>
                                    </td>
                                    <td class="align-middle">
                                        @switch($contact->type)
                                            @case('lead')
                                                <span class="badge bg-warning">Lead</span>
                                                @break
                                            @case('client')
                                                <span class="badge bg-success">Client</span>
                                                @break
                                            @case('partner')
                                                <span class="badge bg-info">Partenaire</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $contact->type }}</span>
                                        @endswitch
                                    </td>
                                    <td class="align-middle">{{ $contact->email ?? '-' }}</td>
                                    <td class="align-middle">{{ $contact->company ?? '-' }}</td>
                                    <td class="align-middle">
                                        @if($contact->status === 'active')
                                            <span class="badge bg-success">Actif</span>
                                        @elseif($contact->status === 'prospect')
                                            <span class="badge bg-warning">Prospect</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('crm.contacts.show', $contact) }}" class="btn btn-sm btn-outline-info mr-1">Voir</a>
                                        <a href="{{ route('crm.contacts.edit', $contact) }}" class="btn btn-sm btn-outline-primary mr-1">Modifier</a>
                                        <form action="{{ route('crm.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce contact ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Suppr.</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-3">
                        {{ $contacts->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="display-1">👥</span>
                        <h5 class="text-muted mt-3">Aucun contact enregistré</h5>
                        <a href="{{ route('crm.contacts.create') }}" class="btn btn-primary mt-3">
                            + Ajouter un contact
                        </a>
                    </div>
                @endif
            </div>
        </div>
</div>
@endsection

