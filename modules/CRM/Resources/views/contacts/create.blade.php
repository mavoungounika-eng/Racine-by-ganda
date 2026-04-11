@extends('layouts.admin-master')

@section('title', 'CRM - Nouveau Contact')
@section('page-title', 'Nouveau Contact')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
                {{-- Header --}}
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('crm.contacts.index') }}">Contacts</a></li>
                        <li class="breadcrumb-item active">Nouveau</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-4">👤 Nouveau Contact</h1>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('crm.contacts.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type">Type <span class="text-danger">*</span></label>
                                        <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                            <option value="lead" {{ old('type') === 'lead' ? 'selected' : '' }}>Lead (Prospect)</option>
                                            <option value="client" {{ old('type') === 'client' ? 'selected' : '' }}>Client</option>
                                            <option value="partner" {{ old('type') === 'partner' ? 'selected' : '' }}>Partenaire</option>
                                            <option value="supplier" {{ old('type') === 'supplier' ? 'selected' : '' }}>Fournisseur</option>
                                        </select>
                                        @error('type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Statut <span class="text-danger">*</span></label>
                                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                            <option value="prospect" {{ old('status', 'prospect') === 'prospect' ? 'selected' : '' }}>Prospect</option>
                                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Actif</option>
                                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">Prénom <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Nom</label>
                                        <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Téléphone</label>
                                        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company">Entreprise</label>
                                        <input type="text" name="company" id="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company') }}">
                                        @error('company')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="position">Poste</label>
                                        <input type="text" name="position" id="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position') }}">
                                        @error('position')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Adresse</label>
                                <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="source">Source</label>
                                        <input type="text" name="source" id="source" class="form-control @error('source') is-invalid @enderror" value="{{ old('source') }}" placeholder="ex: Site web, Salon, Recommandation">
                                        @error('source')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tags">Tags (séparés par des virgules)</label>
                                        <input type="text" name="tags" id="tags" class="form-control @error('tags') is-invalid @enderror" value="{{ old('tags') }}" placeholder="ex: VIP, Mode, Brazzaville">
                                        @error('tags')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('crm.contacts.index') }}" class="btn btn-outline-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
    </div>
</div>
@endsection

