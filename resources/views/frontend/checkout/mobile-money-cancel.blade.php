@extends('layouts.frontend')

@section('title', 'Paiement annulé - RACINE BY GANDA')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            {{-- Card principale --}}
            <div class="card shadow-lg">
                {{-- Header --}}
                <div class="card-header bg-dark text-white text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                    </div>
                    <h1 class="h3 mb-2">Paiement annulé</h1>
                    <p class="mb-0">Le paiement Mobile Money a été annulé</p>
                </div>

                {{-- Contenu --}}
                <div class="card-body p-4 text-center">
                    {{-- Message --}}
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                            <div>
                                <strong>Paiement annulé</strong>
                                <p class="mb-0">Le paiement Mobile Money a été annulé. Votre commande reste en attente de paiement.</p>
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
                                    <p class="text-muted mb-1">Montant</p>
                                    <h5 class="font-weight-bold mb-0">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="border-top pt-4">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <a href="{{ route('checkout.mobile-money.form', $order) }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-redo mr-2"></i>
                                    Réessayer le paiement
                                </a>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="{{ route('frontend.home') }}" class="btn btn-outline-secondary btn-block">
                                    <i class="fas fa-home mr-2"></i>
                                    Retour à l'accueil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
