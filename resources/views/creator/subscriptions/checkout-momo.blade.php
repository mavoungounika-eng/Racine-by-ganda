@extends('layouts.creator')

@section('title', 'Paiement Mobile Money - RACINE BY GANDA')
@section('page-title', 'Paiement Mobile Money')

@push('styles')
<style>
    .payment-summary-card {
        background: #F8F6F3;
        border-radius: 20px;
        padding: 2rem;
        border: 1px solid #E5DDD3;
    }

    .momo-provider-label {
        display: block;
        cursor: pointer;
        height: 100%;
    }

    .momo-provider-card {
        padding: 1.5rem;
        border: 2px solid #E5DDD3;
        border-radius: 20px;
        background: white;
        transition: all 0.3s ease;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .momo-radio:checked + .momo-provider-card {
        border-color: var(--racine-orange);
        background: #FFF7ED;
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }

    .provider-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .info-box {
        background: #F0F7FF;
        border: 1px solid #BFDBFE;
        border-radius: 16px;
        padding: 1.5rem;
        color: #1E40AF;
    }

    .info-box h5 {
        color: #1E3A8A !important;
        font-weight: 800;
    }

    .info-box ol li {
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            
            <div class="creator-card shadow-lg border-0">
                <div class="text-center mb-5">
                    <div class="bg-orange-ultra-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                        <i class="fas fa-mobile-alt text-orange fa-3x"></i>
                    </div>
                    <h1 class="h2 font-weight-bold text-dark mb-2" style="font-family: 'Libre Baskerville', serif;">
                        Finaliser votre abonnement
                    </h1>
                    <p class="text-muted font-weight-bold">Mode de paiement : Mobile Money</p>
                </div>

                <div class="row">
                    <div class="col-lg-5 mb-4 mb-lg-0">
                        <div class="payment-summary-card h-100">
                            <h4 class="h5 font-weight-bold text-dark mb-4">Récapitulatif</h4>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Abonnement</span>
                                <span class="font-weight-bold text-dark">{{ $plan->name }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Période</span>
                                <span class="font-weight-bold text-dark">Mensuel</span>
                            </div>
                            <hr class="my-4" style="border-top: 2px dashed #E5DDD3;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 font-weight-bold text-dark mb-0">Total à payer</span>
                                <span class="h3 font-weight-bold text-orange mb-0">{{ number_format($plan->price, 0, ',', ' ') }} <small>FCFA</small></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <form method="POST" action="{{ route('creator.subscription.checkout.momo.process', $plan) }}">
                            @csrf

                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-dark mb-3">Choisissez votre opérateur</label>
                                <div class="row no-gutters">
                                    <div class="col-6 p-2">
                                        <label class="momo-provider-label">
                                            <input type="radio" name="provider" value="orange" class="d-none momo-radio" required>
                                            <div class="momo-provider-card">
                                                <div class="provider-icon bg-orange-ultra-light text-orange">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <span class="font-weight-bold text-dark">Orange Money</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6 p-2">
                                        <label class="momo-provider-label">
                                            <input type="radio" name="provider" value="mtn" class="d-none momo-radio">
                                            <div class="momo-provider-card">
                                                <div class="provider-icon" style="background: #FFFBEB; color: #D97706;">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <span class="font-weight-bold text-dark">MTN MoMo</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6 p-2">
                                        <label class="momo-provider-label">
                                            <input type="radio" name="provider" value="moov" class="d-none momo-radio">
                                            <div class="momo-provider-card">
                                                <div class="provider-icon" style="background: #EFF6FF; color: #2563EB;">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <span class="font-weight-bold text-dark">Moov Money</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6 p-2">
                                        <label class="momo-provider-label">
                                            <input type="radio" name="provider" value="wave" class="d-none momo-radio">
                                            <div class="momo-provider-card">
                                                <div class="provider-icon" style="background: #ECFDF5; color: #059669;">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <span class="font-weight-bold text-dark">Wave</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                @error('provider')
                                    <div class="text-danger small mt-2 font-weight-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label for="phone" class="font-weight-bold text-dark">Numéro de téléphone</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0"><i class="fas fa-phone-alt text-muted"></i></span>
                                    </div>
                                    <input type="tel" id="phone" name="phone" 
                                           class="form-control border-left-0 h-auto py-3 @error('phone') is-invalid @enderror" 
                                           placeholder="Ex: 0707070707" required>
                                </div>
                                <small class="text-muted font-italic">Le numéro doit être enregistré à votre nom.</small>
                                @error('phone')
                                    <div class="invalid-feedback d-block font-weight-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="info-box mb-5 shadow-sm">
                                <div class="d-flex">
                                    <i class="fas fa-info-circle fa-lg mr-3 mt-1"></i>
                                    <div>
                                        <h5 class="h6 mb-3">Étapes du paiement :</h5>
                                        <ol class="small pl-3 mb-0">
                                            <li>Sélectionnez votre opérateur mobile.</li>
                                            <li>Entrez votre numéro de téléphone.</li>
                                            <li>Validez la demande de paiement qui apparaîtra sur votre mobile.</li>
                                            <li>Votre accès Premium sera débloqué immédiatement après validation.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <button type="submit" class="btn btn-orange btn-lg btn-block font-weight-bold rounded-pill py-3 shadow">
                                    <i class="fas fa-lock mr-2"></i> Payer en toute sécurité
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="{{ route('creator.subscription.plans') }}" class="btn btn-link text-muted font-weight-bold text-decoration-none">
                                    <i class="fas fa-arrow-left mr-1"></i> Revenir aux plans
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

