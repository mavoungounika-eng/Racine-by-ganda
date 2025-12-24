@extends('layouts.creator')

@section('title', 'Paramètres Avancés - Paiements - RACINE BY GANDA')
@section('page-title', 'Paramètres Avancés')

@push('styles')
<parameter name="link" rel="stylesheet" href="{{ asset('css/creator/payment-preferences.css') }}">
@endpush

@section('content')
<div class="payment-preferences-container">
    
    {{-- HEADER --}}
    <div class="payment-header">
        <div>
            <a href="{{ route('creator.settings.payment-preferences.index') }}" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Retour
            </a>
            <h1 class="payment-title">Paramètres Avancés</h1>
            <p class="payment-subtitle">Configurez vos préférences de versement et notifications</p>
        </div>
    </div>

    {{-- MESSAGES FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- CALENDRIER DE VERSEMENT --}}
    <div class="payment-card">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--blue">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <h3 class="payment-card-title">Calendrier de Versement</h3>
                <p class="payment-card-subtitle">Choisissez quand recevoir vos paiements</p>
            </div>
        </div>

        <form action="{{ route('creator.settings.payment-preferences.schedule.update') }}" method="POST">
            @csrf
            <div class="radio-group">
                <label class="radio-card {{ $preferences->payout_schedule === 'automatic' ? 'radio-card--active' : '' }}">
                    <input type="radio" name="schedule" value="automatic" {{ $preferences->payout_schedule === 'automatic' ? 'checked' : '' }}>
                    <div class="radio-card-content">
                        <div class="radio-card-header">
                            <i class="fas fa-bolt"></i>
                            <strong>Automatique</strong>
                        </div>
                        <p class="radio-card-description">Versement tous les 7 jours (recommandé)</p>
                    </div>
                </label>

                <label class="radio-card {{ $preferences->payout_schedule === 'monthly' ? 'radio-card--active' : '' }}">
                    <input type="radio" name="schedule" value="monthly" {{ $preferences->payout_schedule === 'monthly' ? 'checked' : '' }}>
                    <div class="radio-card-content">
                        <div class="radio-card-header">
                            <i class="fas fa-calendar"></i>
                            <strong>Mensuel</strong>
                        </div>
                        <p class="radio-card-description">Le 1er de chaque mois</p>
                    </div>
                </label>

                <label class="radio-card {{ $preferences->payout_schedule === 'manual' ? 'radio-card--active' : '' }}">
                    <input type="radio" name="schedule" value="manual" {{ $preferences->payout_schedule === 'manual' ? 'checked' : '' }}>
                    <div class="radio-card-content">
                        <div class="radio-card-header">
                            <i class="fas fa-hand-pointer"></i>
                            <strong>Manuel</strong>
                        </div>
                        <p class="radio-card-description">Vous décidez quand retirer</p>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn-payment btn-payment--primary mt-3">
                <i class="fas fa-save"></i>
                Sauvegarder
            </button>
        </form>
    </div>

    {{-- SEUIL MINIMUM --}}
    <div class="payment-card">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--green">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <h3 class="payment-card-title">Seuil Minimum de Versement</h3>
                <p class="payment-card-subtitle">Montant minimum avant un versement</p>
            </div>
        </div>

        <div class="threshold-display">
            <span class="threshold-value" id="thresholdValue">{{ number_format($preferences->minimum_payout_threshold, 0, ',', ' ') }} FCFA</span>
        </div>

        <div class="slider-container">
            <input 
                type="range" 
                id="thresholdSlider" 
                class="threshold-slider" 
                min="10000" 
                max="100000" 
                step="5000" 
                value="{{ $preferences->minimum_payout_threshold }}"
            >
            <div class="slider-labels">
                <span>10,000 FCFA</span>
                <span>100,000 FCFA</span>
            </div>
        </div>

        <div class="alert alert-info-light mt-3">
            <i class="fas fa-info-circle"></i>
            Les versements ne seront effectués que si votre solde atteint ce montant.
        </div>
    </div>

    {{-- PRÉFÉRENCES DE NOTIFICATION --}}
    <div class="payment-card">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--purple">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <h3 class="payment-card-title">Préférences de Notification</h3>
                <p class="payment-card-subtitle">Choisissez comment être informé</p>
            </div>
        </div>

        <form action="{{ route('creator.settings.payment-preferences.notifications.update') }}" method="POST">
            @csrf
            <div class="toggle-list">
                <div class="toggle-item">
                    <div>
                        <div class="toggle-label">
                            <i class="fas fa-envelope"></i>
                            Notifications Email
                        </div>
                        <p class="toggle-description">Recevoir des emails pour chaque versement</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_email" {{ $preferences->notify_email ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div>
                        <div class="toggle-label">
                            <i class="fas fa-sms"></i>
                            Notifications SMS
                        </div>
                        <p class="toggle-description">Recevoir un SMS pour chaque versement</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_sms" {{ $preferences->notify_sms ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div>
                        <div class="toggle-label">
                            <i class="fas fa-mobile-alt"></i>
                            Notifications Push
                        </div>
                        <p class="toggle-description">Recevoir des notifications dans l'application</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_push" {{ $preferences->notify_push ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-payment btn-payment--primary mt-3">
                <i class="fas fa-save"></i>
                Sauvegarder
            </button>
        </form>
    </div>

    {{-- INFORMATIONS FISCALES --}}
    <div class="payment-card">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--orange">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h3 class="payment-card-title">Informations Fiscales</h3>
                <p class="payment-card-subtitle">Données pour la déclaration fiscale</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-flag text-orange-500"></i>
                <div>
                    <div class="info-label">Pays Fiscal</div>
                    <div class="info-value">{{ $preferences->tax_country ?? 'CG' }} - Congo</div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-hashtag text-orange-500"></i>
                <div>
                    <div class="info-label">Numéro Fiscal</div>
                    <div class="info-value">{{ $preferences->tax_id ?? 'Non renseigné' }}</div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning-light mt-3">
            <i class="fas fa-exclamation-triangle"></i>
            Assurez-vous que vos informations fiscales sont à jour pour éviter tout problème.
        </div>
    </div>

    {{-- HISTORIQUE DES TRANSACTIONS --}}
    <div class="payment-card">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--gray">
                <i class="fas fa-history"></i>
            </div>
            <div>
                <h3 class="payment-card-title">Historique des Transactions</h3>
                <p class="payment-card-subtitle">Vos 10 dernières transactions</p>
            </div>
        </div>

        @if($recentTransactions->count() > 0)
            <div class="transaction-list">
                @foreach($recentTransactions as $transaction)
                    <div class="transaction-item">
                        <div class="transaction-icon">
                            <i class="fas fa-arrow-down text-success"></i>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-title">Versement {{ $transaction->created_at->format('d/m/Y') }}</div>
                            <div class="transaction-subtitle">{{ $transaction->description }}</div>
                        </div>
                        <div class="transaction-amount">
                            +{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Aucune transaction pour le moment</p>
            </div>
        @endif

        <a href="{{ route('creator.finances.index') }}" class="payment-link mt-3">
            Voir l'historique complet
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    {{-- ZONE DE DANGER --}}
    <div class="payment-card payment-card--danger">
        <div class="payment-card-header">
            <div class="payment-card-icon payment-card-icon--red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 class="payment-card-title">Zone de Danger</h3>
                <p class="payment-card-subtitle">Actions irréversibles</p>
            </div>
        </div>

        <div class="alert alert-danger-light">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Attention :</strong> Déconnecter Stripe désactivera tous vos paiements.
        </div>

        <form action="{{ route('creator.settings.payment-preferences.stripe.disconnect') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir déconnecter Stripe? Cette action désactivera tous vos paiements.');">
            @csrf
            <button type="submit" class="btn-payment btn-payment--danger">
                <i class="fas fa-unlink"></i>
                Déconnecter Stripe
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/creator/payment-preferences.js') }}"></script>
<script>
// Slider pour le seuil minimum
const slider = document.getElementById('thresholdSlider');
const valueDisplay = document.getElementById('thresholdValue');

if (slider && valueDisplay) {
    slider.addEventListener('input', function() {
        const value = parseInt(this.value);
        valueDisplay.textContent = value.toLocaleString('fr-FR') + ' FCFA';
    });

    slider.addEventListener('change', function() {
        const value = parseInt(this.value);
        
        // Envoyer la mise à jour au serveur
        fetch('{{ route("creator.settings.payment-preferences.threshold.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ threshold: value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible';
                alert.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    ${data.message}
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                document.querySelector('.payment-preferences-container').insertBefore(alert, document.querySelector('.payment-preferences-container').firstChild);
                
                // Supprimer l'alerte après 5 secondes
                setTimeout(() => alert.remove(), 5000);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    });
}

// Radio cards interactives
document.querySelectorAll('.radio-card input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.radio-card').forEach(card => {
            card.classList.remove('radio-card--active');
        });
        this.closest('.radio-card').classList.add('radio-card--active');
    });
});
</script>
@endpush
