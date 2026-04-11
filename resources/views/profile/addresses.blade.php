@extends('layouts.internal')

@section('title', 'Mes Adresses - RACINE BY GANDA')
@section('page-title', 'Mes Adresses')
@section('page-subtitle', 'Gérez vos adresses de livraison')

@section('content')
<div class="row">
    <div class="col-md-8">
        @if($addresses->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Adresses enregistrées</h5>
            </div>
            <div class="card-body">
                @foreach($addresses as $address)
                <div class="border rounded p-3 mb-3 {{ $address->is_default ? 'border-primary' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            @if($address->is_default)
                            <span class="badge badge-primary mb-2">Par défaut</span>
                            @endif
                            <h6>{{ $address->full_name }}</h6>
                            <p class="mb-1">{{ $address->full_address }}</p>
                            @if($address->phone)
                            <p class="mb-0 text-muted"><small>Tél: {{ $address->phone }}</small></p>
                            @endif
                        </div>
                        <form action="{{ route('profile.addresses.delete', $address) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr ?')">
                                <i class="icon-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ $addresses->count() > 0 ? 'Ajouter une nouvelle adresse' : 'Ajouter une adresse' }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.addresses.store') }}">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" placeholder="+242 06 XXX XX XX">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse ligne 1 *</label>
                        <input type="text" name="address_line_1" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse ligne 2</label>
                        <input type="text" name="address_line_2" class="form-control">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ville *</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code postal</label>
                            <input type="text" name="postal_code" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pays *</label>
                        <input type="text" name="country" class="form-control" value="Congo" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default">
                        <label class="form-check-label" for="is_default">
                            Définir comme adresse par défaut
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" class="btn-racine-primary">
                        <i class="icon-check mr-2"></i>
                        Enregistrer l'adresse
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Adresses', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
@endsection

