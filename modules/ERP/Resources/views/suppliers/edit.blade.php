@extends('layouts.admin-master')

@section('title', 'ERP - Modifier Fournisseur')
@section('page-title', 'Modifier Fournisseur')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
                {{-- Header --}}
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('erp.suppliers.index') }}">Fournisseurs</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-4">🏭 Modifier : {{ $fournisseur->name }}</h1>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('erp.suppliers.update', $fournisseur) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="name">Nom du fournisseur <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $fournisseur->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $fournisseur->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Téléphone</label>
                                        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $fournisseur->phone) }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Adresse</label>
                                <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $fournisseur->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="tax_id">Numéro fiscal / RCCM</label>
                                <input type="text" name="tax_id" id="tax_id" class="form-control @error('tax_id') is-invalid @enderror" value="{{ old('tax_id', $fournisseur->tax_id) }}">
                                @error('tax_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $fournisseur->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="is_active" id="is_active" class="custom-control-input" value="1" {{ old('is_active', $fournisseur->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="custom-control-label">Fournisseur actif</label>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                            </div>
                        </form>
                    </div>
                </div>
    </div>
</div>
@endsection

