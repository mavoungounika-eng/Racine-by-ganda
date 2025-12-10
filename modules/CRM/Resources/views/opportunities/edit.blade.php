@extends('layouts.admin-master')

@section('title', 'CRM - Modifier Opportunité')
@section('page-title', 'Modifier Opportunité')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
                {{-- Header --}}
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('crm.opportunities.index') }}">Opportunités</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-4">🎯 Modifier : {{ $opportunite->title }}</h1>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('crm.opportunities.update', $opportunite) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="title">Titre <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $opportunite->title) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="contact_id">Contact <span class="text-danger">*</span></label>
                                <select name="contact_id" id="contact_id" class="form-control @error('contact_id') is-invalid @enderror" required>
                                    <option value="">-- Sélectionner --</option>
                                    @foreach($contacts as $contact)
                                        <option value="{{ $contact->id }}" {{ old('contact_id', $opportunite->contact_id) == $contact->id ? 'selected' : '' }}>
                                            {{ $contact->first_name }} {{ $contact->last_name }} {{ $contact->company ? '(' . $contact->company . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('contact_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="value">Valeur estimée</label>
                                        <div class="input-group">
                                            <input type="number" name="value" id="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', $opportunite->value) }}" min="0" step="1">
                                            <div class="input-group-append">
                                                <span class="input-group-text">FCFA</span>
                                            </div>
                                        </div>
                                        @error('value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="stage">Étape <span class="text-danger">*</span></label>
                                        <select name="stage" id="stage" class="form-control @error('stage') is-invalid @enderror" required>
                                            <option value="prospection" {{ old('stage', $opportunite->stage) === 'prospection' ? 'selected' : '' }}>Prospection</option>
                                            <option value="qualification" {{ old('stage', $opportunite->stage) === 'qualification' ? 'selected' : '' }}>Qualification</option>
                                            <option value="proposition" {{ old('stage', $opportunite->stage) === 'proposition' ? 'selected' : '' }}>Proposition</option>
                                            <option value="negotiation" {{ old('stage', $opportunite->stage) === 'negotiation' ? 'selected' : '' }}>Négociation</option>
                                            <option value="won" {{ old('stage', $opportunite->stage) === 'won' ? 'selected' : '' }}>Gagnée ✓</option>
                                            <option value="lost" {{ old('stage', $opportunite->stage) === 'lost' ? 'selected' : '' }}>Perdue</option>
                                        </select>
                                        @error('stage')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="probability">Probabilité (%)</label>
                                        <input type="number" name="probability" id="probability" class="form-control @error('probability') is-invalid @enderror" value="{{ old('probability', $opportunite->probability) }}" min="0" max="100">
                                        @error('probability')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="expected_close_date">Date de clôture prévue</label>
                                        <input type="date" name="expected_close_date" id="expected_close_date" class="form-control @error('expected_close_date') is-invalid @enderror" value="{{ old('expected_close_date', $opportunite->expected_close_date ? $opportunite->expected_close_date->format('Y-m-d') : '') }}">
                                        @error('expected_close_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $opportunite->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('crm.opportunities.index') }}" class="btn btn-outline-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                            </div>
                        </form>
                    </div>
                </div>
    </div>
</div>
@endsection

