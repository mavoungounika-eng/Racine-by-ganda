@extends('layouts.creator')

@section('title', 'Mes Préférences de Paiement - RACINE BY GANDA')
@section('page-title', 'Préférences de Paiement')

@push('styles')
<style>
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
    }

    .payment-option {
        border: 2px solid #E5DDD3;
        border-radius: 16px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s;
        height: 100%;
    }

    .payment-option:hover {
        border-color: #D4A574;
        background: #F8F6F3;
    }

    .payment-option.active {
        border-color: #ED5F1E;
        background: rgba(237, 95, 30, 0.05);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.1);
    }

    .provider-logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
        margin-bottom: 1rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.875rem 1.25rem;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        font-size: 0.95rem;
        color: #2C1810;
        background: white;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #D4A574;
        box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.1);
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        cursor: pointer;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    
    {{-- Feedback Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3">
            <i class="fas fa-check-circle text-xl"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="premium-card">
        <div class="mb-8 pb-6 border-b-2 border-[#E5DDD3]">
            <h2 class="text-2xl font-bold text-[#2C1810]" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-wallet text-[#ED5F1E] mr-2"></i>
                Préférences de Paiement
            </h2>
            <p class="text-[#8B7355] mt-2">Configurez vos moyens de reversement et consultez vos revenus.</p>
            
            {{-- Nouveau modèle économique --}}
            <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-200">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
                    <div class="flex-1">
                        <h4 class="font-bold text-blue-900 mb-1">💰 Nouveau Modèle de Facturation</h4>
                        <p class="text-sm text-blue-800">
                            Vous recevez <strong>100% du prix HT</strong> de vos produits. 
                            RACINE prélève <strong>5% de frais de service</strong> + <strong>TVA 18%</strong> sur le total.
                        </p>
                        <a href="{{ route('creator.finances.index') }}" class="inline-block mt-2 text-sm text-blue-700 hover:text-blue-900 font-semibold underline">
                            <i class="fas fa-chart-line mr-1"></i>
                            Voir mon dashboard financier
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('creator.settings.payment.update') }}">
            @csrf
            @method('PUT')


            {{-- Widget Statut KYC --}}
            @include('creator.partials.kyc-status-widget')

            {{-- Stripe Connect (Phase 2) --}}
            <div class="mt-8 mb-10 p-6 rounded-2xl border-2 {{ $stripeAccount && $stripeAccount->onboarding_status === 'complete' ? 'border-green-200 bg-green-50' : 'border-[#ED5F1E] border-dashed bg-orange-50' }}">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl {{ $stripeAccount && $stripeAccount->onboarding_status === 'complete' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-[#ED5F1E]' }}">
                            <i class="fab fa-stripe"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-[#2C1810]">Stripe Connect</h3>
                            <p class="text-sm text-[#8B7355]">Recevez vos paiements automatiquement sur votre compte bancaire.</p>
                            
                            @if($stripeAccount)
                                <div class="mt-2 flex items-center gap-2">
                                    @if($stripeAccount->onboarding_status === 'complete')
                                        <span class="px-2 py-1 bg-green-200 text-green-800 text-xs font-bold rounded-full uppercase">Actif</span>
                                        <span class="text-xs text-green-700"><i class="fas fa-check-circle mr-1"></i> Prêt pour les versements</span>
                                    @else
                                        <span class="px-2 py-1 bg-orange-200 text-orange-800 text-xs font-bold rounded-full uppercase">Incomplet</span>
                                        <span class="text-xs text-orange-700"><i class="fas fa-exclamation-triangle mr-1"></i> Configuration à terminer</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        @if(!$stripeAccount || $stripeAccount->onboarding_status !== 'complete')
                            <a href="{{ route('creator.settings.stripe.connect') }}" class="premium-btn inline-block whitespace-nowrap">
                                <i class="fas fa-plug mr-2"></i>
                                {{ $stripeAccount ? 'Terminer la configuration' : 'Activer Stripe Connect' }}
                            </a>
                        @else
                            <button type="button" disabled class="px-6 py-3 rounded-xl bg-green-600 text-white font-semibold cursor-default">
                                <i class="fas fa-check-double mr-2"></i>
                                Compte Connecté
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Méthode de Paiement (Legacy / Local) --}}
            <h3 class="text-lg font-semibold text-[#2C1810] mb-4">Moyen de secours (Mobile Money)</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                {{-- Mobile Money --}}
                <label class="payment-option relative flex flex-col items-center text-center {{ old('payout_method', $profile->payout_method) == 'mobile_money' ? 'active' : '' }}">
                    <input type="radio" name="payout_method" value="mobile_money" class="absolute opacity-0" 
                           {{ old('payout_method', $profile->payout_method) == 'mobile_money' ? 'checked' : '' }}
                           onchange="togglePaymentFields(this.value)">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-3 text-orange-600 text-2xl">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <span class="font-bold text-[#2C1810]">Mobile Money</span>
                    <span class="text-sm text-[#8B7355] mt-1">Orange Money, MTN MoMo, Wave...</span>
                </label>

                {{-- Virement Bancaire (Désactivé pour V1.5 mais visible) --}}
                <label class="payment-option relative flex flex-col items-center text-center opacity-60 cursor-not-allowed bg-gray-50">
                    <input type="radio" name="payout_method" value="bank_transfer" class="absolute opacity-0" disabled>
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-3 text-gray-500 text-2xl">
                        <i class="fas fa-university"></i>
                    </div>
                    <span class="font-bold text-gray-500">Virement Bancaire</span>
                    <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded mt-1">Bientôt disponible</span>
                </label>
            </div>

            {{-- Champs Mobile Money --}}
            <div id="mobile_money_fields" class="{{ old('payout_method', $profile->payout_method) == 'mobile_money' ? '' : 'hidden' }}">
                <div class="bg-[#F8F6F3] p-6 rounded-xl border border-[#E5DDD3]">
                    <h4 class="font-semibold text-[#2C1810] mb-4">Détails Mobile Money</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="mobile_money_provider" class="form-label">Opérateur</label>
                            <select id="mobile_money_provider" name="mobile_money_provider" class="form-control">
                                <option value="">-- Choisir un opérateur --</option>
                                <option value="orange" {{ (old('mobile_money_provider') ?? ($profile->payout_details['mobile_money']['provider'] ?? '')) == 'orange' ? 'selected' : '' }}>Orange Money</option>
                                <option value="mtn" {{ (old('mobile_money_provider') ?? ($profile->payout_details['mobile_money']['provider'] ?? '')) == 'mtn' ? 'selected' : '' }}>MTN MoMo</option>
                                <option value="moov" {{ (old('mobile_money_provider') ?? ($profile->payout_details['mobile_money']['provider'] ?? '')) == 'moov' ? 'selected' : '' }}>Moov Money</option>
                                <option value="wave" {{ (old('mobile_money_provider') ?? ($profile->payout_details['mobile_money']['provider'] ?? '')) == 'wave' ? 'selected' : '' }}>Wave</option>
                            </select>
                            @error('mobile_money_provider') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label for="mobile_money_number" class="form-label">Numéro de téléphone</label>
                            <input type="text" id="mobile_money_number" name="mobile_money_number" 
                                   value="{{ old('mobile_money_number') ?? ($profile->payout_details['mobile_money']['number'] ?? '') }}" 
                                   placeholder="Ex: 0707070707" class="form-control">
                            <p class="text-sm text-[#8B7355] mt-1">Le numéro doit être enregistré à votre nom.</p>
                            @error('mobile_money_number') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-4 pt-6 mt-6 border-t-2 border-[#E5DDD3]">
                <a href="{{ route('creator.dashboard') }}" class="px-6 py-3 rounded-xl border-2 border-[#E5DDD3] text-[#2C1810] font-semibold hover:bg-[#F8F6F3] transition-colors">
                    Annuler
                </a>
                <button type="submit" class="premium-btn">
                    <i class="fas fa-save mr-2"></i>
                    Sauvegarder les préférences
                </button>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
    function togglePaymentFields(method) {
        document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
        if(method === 'mobile_money') {
            document.querySelector('input[value="mobile_money"]').closest('.payment-option').classList.add('active');
            document.getElementById('mobile_money_fields').classList.remove('hidden');
        } else {
            document.getElementById('mobile_money_fields').classList.add('hidden');
        }
    }
</script>
@endpush
@endsection
