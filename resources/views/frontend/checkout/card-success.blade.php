@extends('layouts.frontend')

@section('title', 'Paiement Réussi - RACINE BY GANDA')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            {{-- Card principale --}}
            <div class="card shadow-lg">
                {{-- Header --}}
                <div class="card-header bg-dark text-white text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h1 class="h3 mb-2">Paiement réussi !</h1>
                    <p class="mb-0">Votre paiement par carte bancaire a été accepté.</p>
                </div>

                {{-- Contenu --}}
                <div class="card-body p-4">
                    {{-- Message de confirmation --}}
                    <div class="alert alert-success">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x mr-3"></i>
                            <div>
                                <strong>Paiement confirmé</strong>
                                <p class="mb-0">Votre commande a été enregistrée et sera traitée dans les plus brefs délais.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Informations de la commande --}}
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <p class="text-muted mb-1">Numéro de commande</p>
                                    <h4 class="font-weight-bold mb-0">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</h4>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Montant payé</p>
                                    <h5 class="font-weight-bold text-success mb-0">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</h5>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <p class="text-muted mb-1">Date</p>
                                    <p class="mb-0">{{ now()->format('d/m/Y à H:i') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Moyen de paiement</p>
                                    <p class="mb-0">Carte bancaire</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Message email --}}
                    <div class="alert alert-info">
                        <i class="fas fa-envelope mr-2"></i>
                        <strong>Email de confirmation :</strong> Un email de confirmation vous a été envoyé.
                    </div>

                    {{-- Actions --}}
                    <div class="border-top pt-4">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <a href="{{ route('frontend.shop') }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-shopping-bag mr-2"></i>
                                    Continuer mes achats
                                </a>
                            </div>
                            @auth
                            <div class="col-md-6 mb-2">
                                <a href="{{ route('profile.orders') }}" class="btn btn-outline-dark btn-block">
                                    <i class="fas fa-list mr-2"></i>
                                    Voir mes commandes
                                </a>
                            </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
