@extends('layouts.admin-master')

@section('title', 'CRM - Opportunités')
@section('page-title', 'Opportunités')

@section('content')
<div class="mb-4">
        {{-- Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Opportunités</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">🎯 Opportunités</h1>
                    <a href="{{ route('crm.opportunities.create') }}" class="btn btn-primary">
                        + Nouvelle Opportunité
                    </a>
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
                <div class="card border-0 shadow-sm h-100 bg-success text-white">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0">{{ number_format($stats['pipeline_value'], 0, ',', ' ') }}</h4>
                        <small>Pipeline (FCFA)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <a href="{{ route('crm.opportunities.index') }}" class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0">{{ $stats['total'] }}</h4>
                        <small class="text-muted">Total</small>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 text-primary">{{ $stats['open'] }}</h4>
                        <small class="text-muted">En cours</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3 text-center">
                        <h4 class="mb-0 text-success">{{ $stats['won'] }}</h4>
                        <small class="text-muted">Gagnées</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtre --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('crm.opportunities.index') }}" class="row align-items-center">
                    <div class="col-md-5 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <select name="stage" class="form-control">
                            <option value="">Toutes les étapes</option>
                            <option value="prospection" {{ request('stage') === 'prospection' ? 'selected' : '' }}>Prospection</option>
                            <option value="qualification" {{ request('stage') === 'qualification' ? 'selected' : '' }}>Qualification</option>
                            <option value="proposition" {{ request('stage') === 'proposition' ? 'selected' : '' }}>Proposition</option>
                            <option value="negotiation" {{ request('stage') === 'negotiation' ? 'selected' : '' }}>Négociation</option>
                            <option value="won" {{ request('stage') === 'won' ? 'selected' : '' }}>Gagnée</option>
                            <option value="lost" {{ request('stage') === 'lost' ? 'selected' : '' }}>Perdue</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Liste --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($opportunities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Opportunité</th>
                                    <th class="border-0">Contact</th>
                                    <th class="border-0">Valeur</th>
                                    <th class="border-0">Étape</th>
                                    <th class="border-0">Date prévue</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($opportunities as $opp)
                                <tr>
                                    <td class="align-middle">
                                        <strong>{{ Str::limit($opp->title, 30) }}</strong>
                                    </td>
                                    <td class="align-middle">
                                        {{ $opp->contact->first_name ?? '-' }} {{ $opp->contact->last_name ?? '' }}
                                    </td>
                                    <td class="align-middle font-weight-bold">
                                        {{ number_format($opp->value, 0, ',', ' ') }} {{ $opp->currency }}
                                    </td>
                                    <td class="align-middle">
                                        @switch($opp->stage)
                                            @case('prospection')
                                                <span class="badge bg-secondary">Prospection</span>
                                                @break
                                            @case('qualification')
                                                <span class="badge bg-info">Qualification</span>
                                                @break
                                            @case('proposition')
                                                <span class="badge bg-primary">Proposition</span>
                                                @break
                                            @case('negotiation')
                                                <span class="badge bg-warning">Négociation</span>
                                                @break
                                            @case('won')
                                                <span class="badge bg-success">Gagnée ✓</span>
                                                @break
                                            @case('lost')
                                                <span class="badge bg-danger">Perdue</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="align-middle text-muted small">
                                        {{ $opp->expected_close_date ? $opp->expected_close_date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('crm.opportunities.edit', $opp) }}" class="btn btn-sm btn-outline-primary mr-1">Modifier</a>
                                        <form action="{{ route('crm.opportunities.destroy', $opp) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
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
                        {{ $opportunities->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="display-1">🎯</span>
                        <h5 class="text-muted mt-3">Aucune opportunité</h5>
                        <a href="{{ route('crm.opportunities.create') }}" class="btn btn-primary mt-3">
                            + Créer une opportunité
                        </a>
                    </div>
                @endif
            </div>
        </div>
</div>
@endsection

