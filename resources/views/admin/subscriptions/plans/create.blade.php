@extends('layouts.admin')

@section('title', 'Créer un Plan - Admin')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.subscriptions.plans.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i>Retour
        </a>
    </div>

    <h1 class="h3 mb-4">Créer un Nouveau Plan</h1>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.subscriptions.plans.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nom du Plan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                   id="code" name="code" value="{{ old('code') }}" required>
                            <small class="form-text text-muted">Ex: basic, advanced, premium</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="price">Prix Mensuel (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('price') is-invalid @enderror" 
                                   id="price" name="price" value="{{ old('price', 0) }}" min="0" step="1" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Fonctionnalités</label>
                            <div id="features-container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="features[]" placeholder="Ex: 10 produits maximum">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-success" onclick="addFeature()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">Ajoutez les fonctionnalités incluses dans ce plan</small>
                        </div>

                        <hr>

                        <h5 class="mb-3">Configuration Stripe</h5>

                        <div class="form-group">
                            <label for="stripe_product_id">Stripe Product ID</label>
                            <input type="text" class="form-control @error('stripe_product_id') is-invalid @enderror" 
                                   id="stripe_product_id" name="stripe_product_id" value="{{ old('stripe_product_id') }}">
                            <small class="form-text text-muted">ID du produit dans Stripe Dashboard</small>
                            @error('stripe_product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="stripe_price_id">Stripe Price ID</label>
                            <input type="text" class="form-control @error('stripe_price_id') is-invalid @enderror" 
                                   id="stripe_price_id" name="stripe_price_id" value="{{ old('stripe_price_id') }}">
                            <small class="form-text text-muted">ID du prix dans Stripe Dashboard</small>
                            @error('stripe_price_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">Plan actif</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.subscriptions.plans.index') }}" class="btn btn-outline-secondary">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Créer le Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="features[]" placeholder="Nouvelle fonctionnalité">
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
}
</script>
@endpush
@endsection
