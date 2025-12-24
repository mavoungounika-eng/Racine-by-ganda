@extends('layouts.creator')

@section('title', 'Paiement Mobile Money - RACINE BY GANDA')
@section('page-title', 'Paiement Mobile Money')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/creator-premium.css') }}">
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    
    <div class="premium-card">
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-orange-100 flex items-center justify-center">
                <i class="fas fa-mobile-alt text-4xl text-orange-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-[#2C1810] mb-2" style="font-family: 'Libre Baskerville', serif;">
                Paiement Mobile Money
            </h1>
            <p class="text-[#8B7355]">Abonnement : <strong>{{ $plan->name }}</strong></p>
        </div>

        {{-- Plan Summary --}}
        <div class="mb-8 p-6 bg-[#F8F6F3] rounded-xl">
            <div class="flex justify-between items-center mb-4">
                <span class="text-[#8B7355]">Plan sélectionné</span>
                <span class="font-bold text-[#2C1810]">{{ $plan->name }}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <span class="text-[#8B7355]">Montant mensuel</span>
                <span class="font-bold text-[#2C1810]">{{ number_format($plan->price, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between items-center pt-4 border-t-2 border-[#E5DDD3]">
                <span class="font-bold text-[#2C1810]">Total à payer</span>
                <span class="text-2xl font-bold text-[#ED5F1E]">{{ number_format($plan->price, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        {{-- Payment Form --}}
        <form method="POST" action="{{ route('creator.subscription.checkout.momo.process', $plan) }}">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-semibold text-[#2C1810] mb-3">Opérateur Mobile Money</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="orange" class="hidden peer" required>
                        <div class="p-4 border-2 border-[#E5DDD3] rounded-xl peer-checked:border-[#ED5F1E] peer-checked:bg-orange-50 transition-all text-center">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-orange-100 flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-orange-600 text-xl"></i>
                            </div>
                            <div class="font-semibold text-[#2C1810]">Orange Money</div>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="mtn" class="hidden peer">
                        <div class="p-4 border-2 border-[#E5DDD3] rounded-xl peer-checked:border-[#ED5F1E] peer-checked:bg-orange-50 transition-all text-center">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-yellow-100 flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-yellow-600 text-xl"></i>
                            </div>
                            <div class="font-semibold text-[#2C1810]">MTN MoMo</div>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="moov" class="hidden peer">
                        <div class="p-4 border-2 border-[#E5DDD3] rounded-xl peer-checked:border-[#ED5F1E] peer-checked:bg-orange-50 transition-all text-center">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-blue-600 text-xl"></i>
                            </div>
                            <div class="font-semibold text-[#2C1810]">Moov Money</div>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="wave" class="hidden peer">
                        <div class="p-4 border-2 border-[#E5DDD3] rounded-xl peer-checked:border-[#ED5F1E] peer-checked:bg-orange-50 transition-all text-center">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-pink-100 flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-pink-600 text-xl"></i>
                            </div>
                            <div class="font-semibold text-[#2C1810]">Wave</div>
                        </div>
                    </label>
                </div>
                @error('provider')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="phone" class="block text-sm font-semibold text-[#2C1810] mb-2">
                    Numéro de téléphone
                </label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       class="form-control" 
                       placeholder="Ex: 0707070707"
                       required>
                <p class="text-sm text-[#8B7355] mt-1">Le numéro doit être enregistré à votre nom</p>
                @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
                    <div class="text-sm text-blue-800">
                        <strong>Comment ça marche :</strong>
                        <ol class="list-decimal list-inside mt-2 space-y-1">
                            <li>Sélectionnez votre opérateur</li>
                            <li>Entrez votre numéro de téléphone</li>
                            <li>Vous recevrez une notification de paiement</li>
                            <li>Validez le paiement sur votre téléphone</li>
                            <li>Votre abonnement sera activé automatiquement</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('creator.subscription.plans') }}" 
                   class="flex-1 px-6 py-3 rounded-xl border-2 border-[#E5DDD3] text-[#2C1810] font-semibold hover:bg-[#F8F6F3] transition-colors text-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour
                </a>
                
                <button type="submit" class="flex-1 premium-btn">
                    <i class="fas fa-check mr-2"></i>
                    Confirmer le Paiement
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
