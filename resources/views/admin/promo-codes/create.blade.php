@extends('layouts.admin-master')

@section('title', 'Créer un code promo')
@section('page-title', 'Créer un code promo')
@section('page-subtitle', 'Nouveau code de réduction pour le checkout')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-4">
                <h3 class="mb-0 fw-bold">
                    <i class="fas fa-plus-circle text-racine-orange me-2"></i>
                    Nouveau code promo
                </h3>
                <p class="text-muted mb-0 mt-2">
                    Remplissez le formulaire ci-dessous pour créer un nouveau code de réduction.
                </p>
            </div>

            <div class="card-body">
                @include('admin.promo-codes._form', [
                    'action'      => route('admin.promo-codes.store'),
                    'method'      => 'POST',
                    'promoCode'   => null,
                    'submitLabel' => 'Créer le code promo',
                ])
            </div>
        </div>
    </div>
</div>

@endsection
