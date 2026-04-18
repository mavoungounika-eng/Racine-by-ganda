@php
    $kycService = app(\App\Services\CreatorKycService::class);
    $kycStatus = $kycService->checkKycStatus(auth()->user()->creatorProfile);
@endphp

<div class="bg-white rounded-2xl shadow-lg p-6 border-2 {{ $kycStatus['status'] === 'complete' ? 'border-green-200' : 'border-orange-200' }}">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl {{ $kycStatus['status'] === 'complete' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600' }}">
            @if($kycStatus['status'] === 'complete')
                <i class="fas fa-check-circle"></i>
            @elseif($kycStatus['status'] === 'pending_review')
                <i class="fas fa-clock"></i>
            @else
                <i class="fas fa-exclamation-triangle"></i>
            @endif
        </div>
        
        <div class="flex-1">
            <h3 class="text-lg font-bold text-[#160D0C] mb-2">
                @if($kycStatus['status'] === 'complete')
                    ✅ Vérification Complète
                @elseif($kycStatus['status'] === 'pending_review')
                    ⏳ En Cours de Vérification
                @elseif($kycStatus['status'] === 'not_started')
                    🚀 Activer les Paiements
                @else
                    ⚠️ Vérification Incomplète
                @endif
            </h3>
            
            <p class="text-sm text-[#8B7355] mb-4">{{ $kycStatus['message'] }}</p>
            
            @if($kycStatus['status'] !== 'complete')
                @if(isset($kycStatus['requirements']) && count($kycStatus['requirements']) > 0)
                    <div class="mb-4 p-3 bg-orange-50 rounded-lg border border-orange-200">
                        <p class="text-xs font-semibold text-orange-800 mb-2">Documents requis :</p>
                        <ul class="text-xs text-orange-700 space-y-1">
                            @foreach($kycStatus['requirements'] as $requirement)
                                <li>• {{ ucfirst(str_replace('_', ' ', $requirement)) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <a href="{{ route('creator.settings.stripe.connect') }}" class="inline-block px-4 py-2 bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white font-semibold rounded-lg hover:shadow-lg transition-all">
                    <i class="fas fa-plug me-2"></i>
                    {{ $kycStatus['status'] === 'not_started' ? 'Commencer la vérification' : 'Continuer la vérification' }}
                </a>
            @else
                <div class="flex items-center gap-2 text-green-700 text-sm">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="font-semibold">Vous pouvez recevoir des paiements</span>
                </div>
            @endif
        </div>
    </div>
</div>
