@extends('layouts.frontend')

@section('title', 'Paiement en attente - RACINE BY GANDA')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            {{-- Card principale --}}
            <div class="card shadow-lg">
                {{-- Header --}}
                <div class="card-header bg-dark text-white text-center py-4">
                    <div class="mb-3">
                        <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Chargement...</span>
                        </div>
                    </div>
                    <h1 class="h3 mb-2">Paiement en attente</h1>
                    <p class="mb-0">En attente de confirmation de l'opérateur</p>
                </div>

                {{-- Contenu --}}
                <div class="card-body p-4 text-center">
                    <h3 class="h5 mb-4">Paiement en attente de confirmation</h3>
                    
                    {{-- Instructions --}}
                    <div class="alert alert-info text-left mb-4">
                        <h6 class="font-weight-bold">
                            <i class="fas fa-info-circle mr-2"></i>
                            Instructions
                        </h6>
                        <p class="mb-2">
                            @if($payment->provider === 'mtn_momo')
                                <strong>MTN Mobile Money :</strong> Composez <code>*133*1#</code> sur votre téléphone et suivez les instructions pour valider le paiement.
                            @elseif($payment->provider === 'airtel_money')
                                <strong>Airtel Money :</strong> Composez <code>*150*1#</code> sur votre téléphone et suivez les instructions pour valider le paiement.
                            @else
                                Vérifiez votre téléphone et suivez les instructions reçues pour valider le paiement.
                            @endif
                        </p>
                        <p class="mb-0">
                            <strong>Numéro :</strong> {{ $payment->customer_phone }}<br>
                            <strong>Montant :</strong> {{ number_format($payment->amount, 0, ',', ' ') }} FCFA<br>
                            <strong>Transaction ID :</strong> <code>{{ $payment->external_reference }}</code>
                        </p>
                    </div>

                    {{-- Statut du paiement (mis à jour par JS) --}}
                    <div id="payment-status" class="mb-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Vérification...</span>
                        </div>
                        <p class="text-muted mt-3">Vérification du statut du paiement...</p>
                    </div>

                    {{-- Message timeout (caché par défaut) --}}
                    <div id="timeout-message" class="alert alert-warning mb-4" style="display: none;">
                        <h6 class="font-weight-bold">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Temps d'attente dépassé
                        </h6>
                        <p class="mb-2">Le paiement n'a pas été confirmé dans les délais. Vous pouvez :</p>
                        <ul class="text-left mb-0">
                            <li>Réessayer avec un nouveau paiement</li>
                            <li>Vérifier votre téléphone pour confirmer le paiement</li>
                            <li>Contacter le support si le problème persiste</li>
                        </ul>
                    </div>

                    {{-- Actions --}}
                    <div class="border-top pt-4">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('checkout.mobile-money.cancel', $order) }}" class="btn btn-outline-danger btn-block">
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button type="button" onclick="checkStatus()" class="btn btn-outline-primary btn-block" id="check-status-btn">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    Vérifier
                                </button>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('checkout.mobile-money.form', $order) }}" class="btn btn-primary btn-block" id="retry-btn" style="display: none;">
                                    <i class="fas fa-redo mr-2"></i>
                                    Réessayer
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

@push('scripts')
<script>
    // Gestion du polling et timeout
    let checkInterval;
    const POLLING_INTERVAL = 5000; // 5 secondes
    const TIMEOUT_DURATION = 300000; // 5 minutes
    let startTime = Date.now();
    let timeoutReached = false;

    function checkStatus() {
        if (timeoutReached) {
            return;
        }

        fetch('{{ route("checkout.mobile-money.status", ["order" => $order->id, "payment" => $payment->id]) }}')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('payment-status');
                const checkStatusBtn = document.getElementById('check-status-btn');
                const retryBtn = document.getElementById('retry-btn');
                const timeoutMessage = document.getElementById('timeout-message');
                
                if (data.paid) {
                    statusDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i> Paiement confirmé ! Redirection...</div>';
                    clearInterval(checkInterval);
                    setTimeout(() => {
                        window.location.href = '{{ route("checkout.mobile-money.success", $order) }}';
                    }, 2000);
                } else if (data.failed) {
                    statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle mr-2"></i> Le paiement a échoué. Vous pouvez réessayer.</div>';
                    clearInterval(checkInterval);
                    if (retryBtn) retryBtn.style.display = 'block';
                    if (checkStatusBtn) checkStatusBtn.style.display = 'none';
                } else {
                    const elapsed = Date.now() - startTime;
                    if (elapsed >= TIMEOUT_DURATION && !timeoutReached) {
                        handleTimeout();
                    } else {
                        statusDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Vérification...</span></div><p class="text-muted mt-3">En attente de confirmation...</p>';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                const statusDiv = document.getElementById('payment-status');
                statusDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i> Erreur de connexion. Vérifiez votre connexion internet.</div>';
            });
    }

    function handleTimeout() {
        timeoutReached = true;
        clearInterval(checkInterval);
        
        const statusDiv = document.getElementById('payment-status');
        const timeoutMessage = document.getElementById('timeout-message');
        const checkStatusBtn = document.getElementById('check-status-btn');
        const retryBtn = document.getElementById('retry-btn');
        
        statusDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i> Temps d\'attente dépassé (5 minutes).</div>';
        if (timeoutMessage) timeoutMessage.style.display = 'block';
        if (retryBtn) retryBtn.style.display = 'block';
        if (checkStatusBtn) checkStatusBtn.style.display = 'none';
    }

    // Vérifier automatiquement toutes les 5 secondes
    checkInterval = setInterval(checkStatus, POLLING_INTERVAL);
    checkStatus();

    // Arrêter après 5 minutes
    setTimeout(() => {
        if (!timeoutReached) {
            handleTimeout();
        }
    }, TIMEOUT_DURATION);
</script>
@endpush
