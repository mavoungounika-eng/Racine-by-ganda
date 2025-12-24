{{-- Widget de progression de l'onboarding créateur --}}
@php
    $user = Auth::user();
    $profile = $user->creatorProfile;
    
    // Utiliser le service de complétion
    $completionService = app(\App\Services\ProfileCompletionService::class);
    $completion = $completionService->calculateCompletionScore($profile);
    
    $percentage = $completion['percentage'];
    $isComplete = $percentage === 100;
    $level = $completion['level'];
    $alerts = $completion['alerts'];
@endphp

@if(!$isComplete)
<div class="mb-6 p-6 rounded-2xl border-2 border-dashed" 
     style="background: linear-gradient(135deg, #FFF7ED 0%, #FFFBF5 100%); border-color: {{ $level === 'poor' ? '#EF4444' : ($level === 'fair' ? '#F59E0B' : '#10B981') }};">
    
    {{-- En-tête --}}
    <div class="flex items-start justify-between mb-4">
        <div class="flex-1">
            <h3 class="text-xl font-bold text-[#2C1810] mb-1" style="font-family: 'Libre Baskerville', serif;">
                <i class="fas fa-rocket text-[#ED5F1E] mr-2"></i>
                Complétez votre profil
            </h3>
            <p class="text-sm text-[#8B7355]">
                {{ $completion['completed_count'] }} sur {{ $completion['total_count'] }} étapes complétées
            </p>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold" 
                 style="font-family: 'Playfair Display', serif; color: {{ $level === 'poor' ? '#EF4444' : ($level === 'fair' ? '#F59E0B' : ($level === 'good' ? '#10B981' : '#ED5F1E')) }};">
                {{ round($percentage) }}%
            </div>
            <span class="text-xs text-[#8B7355]">
                @if($level === 'poor') Débutant
                @elseif($level === 'fair') En cours
                @elseif($level === 'good') Avancé
                @else Excellent
                @endif
            </span>
        </div>
    </div>

    {{-- Barre de progression --}}
    <div class="mb-6 h-3 bg-white rounded-full overflow-hidden shadow-inner">
        <div class="h-full rounded-full transition-all duration-500" 
             style="width: {{ $percentage }}%; background: linear-gradient(90deg, 
                {{ $level === 'poor' ? '#EF4444' : ($level === 'fair' ? '#F59E0B' : '#10B981') }} 0%, 
                {{ $level === 'poor' ? '#DC2626' : ($level === 'fair' ? '#D97706' : '#059669') }} 100%);">
        </div>
    </div>

    {{-- Alertes dynamiques --}}
    @if(count($alerts) > 0)
        <div class="space-y-3 mb-4">
            @foreach($alerts as $alert)
                <div class="flex items-start gap-3 p-4 rounded-xl {{ $alert['type'] === 'critical' ? 'bg-red-50 border border-red-200' : ($alert['type'] === 'warning' ? 'bg-orange-50 border border-orange-200' : 'bg-green-50 border border-green-200') }}">
                    <span class="text-2xl">{{ $alert['icon'] }}</span>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-[#2C1810] mb-1">{{ $alert['title'] }}</h4>
                        <p class="text-sm text-[#8B7355]">{{ $alert['message'] }}</p>
                    </div>
                    @if($alert['action'])
                        <a href="{{ $alert['action'] }}" 
                           class="flex-shrink-0 px-4 py-2 rounded-lg font-semibold text-sm transition-all hover:scale-105 whitespace-nowrap"
                           style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color: white; box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);">
                            {{ $alert['action_label'] }}
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Étapes restantes (afficher max 3) --}}
    @php
        $incompleteSteps = collect($completion['steps'])->where('completed', false)->take(3);
    @endphp
    
    @if($incompleteSteps->count() > 0)
        <div class="mt-4 pt-4 border-t-2 border-[#E5DDD3]">
            <h4 class="text-sm font-bold text-[#2C1810] mb-3">Prochaines étapes :</h4>
            <div class="space-y-2">
                @foreach($incompleteSteps as $step)
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-6 h-6 rounded-full border-2 border-[#8B7355] flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-circle text-[8px] text-[#8B7355]"></i>
                        </div>
                        <span class="text-[#2C1810]">{{ $step['title'] }}</span>
                        <span class="text-[#8B7355] ml-auto">+{{ $step['points'] }} pts</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif

