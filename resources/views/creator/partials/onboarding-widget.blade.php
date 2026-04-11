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

    // Couleurs basées sur le niveau
    $levelColor = $level === 'poor' ? '#EF4444' : ($level === 'fair' ? '#F59E0B' : '#10B981');
    $levelBg = $level === 'poor' ? '#FEF2F2' : ($level === 'fair' ? '#FFFBEB' : '#ECFDF5');
@endphp

@if(!$isComplete)
<div class="creator-card mb-4 border-dashed" 
     style="background: linear-gradient(135deg, #FFF7ED 0%, #FFFBF5 100%); border: 2px dashed {{ $levelColor }};">
    
    {{-- En-tête --}}
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h3 class="mb-1" style="font-family: 'Playfair Display', serif; font-weight: 700; color: #2C1810; font-size: 1.5rem;">
                <i class="fas fa-rocket text-orange-500 me-2" style="color: var(--racine-orange);"></i>
                Complétez votre profil
            </h3>
            <p class="text-muted mb-0" style="color: #8B7355 !important;">
                {{ $completion['completed_count'] }} sur {{ $completion['total_count'] }} étapes complétées
            </p>
        </div>
        <div class="text-end">
            <div class="h2 mb-0 font-weight-bold" 
                 style="font-family: 'Playfair Display', serif; color: {{ $levelColor }}; font-weight: 900;">
                {{ round($percentage) }}%
            </div>
            <span class="badge" style="background-color: {{ $levelColor }}; color: white; font-size: 0.7rem; text-transform: uppercase;">
                {{ $level === 'poor' ? 'Débutant' : ($level === 'fair' ? 'En cours' : ($level === 'good' ? 'Avancé' : 'Excellent')) }}
            </span>
        </div>
    </div>

    {{-- Barre de progression --}}
    <div class="progress mb-4" style="height: 12px; border-radius: 6px; background-color: rgba(0,0,0,0.05); box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
        <div class="progress-bar transition-all duration-500" role="progressbar" 
             style="width: {{ $percentage }}%; border-radius: 6px; background: linear-gradient(90deg, {{ $levelColor }} 0%, {{ $levelColor }} 100%);" 
             aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
        </div>
    </div>

    {{-- Alertes dynamiques --}}
    @if(count($alerts) > 0)
        <div class="space-y-3 mb-4">
            @foreach($alerts as $alert)
                @php
                    $alertColor = $alert['type'] === 'critical' ? '#EF4444' : ($alert['type'] === 'warning' ? '#F59E0B' : '#10B981');
                    $alertBg = $alert['type'] === 'critical' ? '#FEF2F2' : ($alert['type'] === 'warning' ? '#FFFBEB' : '#ECFDF5');
                @endphp
                <div class="d-flex align-items-start gap-3 p-3 rounded-3 mb-3" style="background-color: {{ $alertBg }}; border: 1px solid {{ $alertColor }}44;">
                    <span class="h4 mb-0 me-3">{{ $alert['icon'] }}</span>
                    <div class="flex-grow-1">
                        <h5 class="mb-1 font-weight-bold" style="color: #2C1810; font-size: 1rem;">{{ $alert['title'] }}</h5>
                        <p class="mb-0" style="color: #5D4037; font-size: 0.9rem;">{{ $alert['message'] }}</p>
                    </div>
                    @if($alert['action'])
                        <a href="{{ $alert['action'] }}" 
                           class="btn btn-sm ms-3"
                           style="background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%); color: white; border: none; font-weight: 700; border-radius: 8px; box-shadow: 0 4px 12px rgba(237, 95, 30, 0.2);">
                            {{ $alert['action_label'] }}
                            <i class="fas fa-arrow-right ms-1"></i>
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
        <div class="mt-3 pt-3 border-top" style="border-top: 2px solid #E5DDD3 !important;">
            <h6 class="text-uppercase font-weight-bold mb-3" style="font-size: 0.75rem; letter-spacing: 1px; color: #2C1810;">Prochaines étapes :</h6>
            <div class="list-unstyled">
                @foreach($incompleteSteps as $step)
                    <div class="d-flex align-items-center mb-2" style="font-size: 0.9rem;">
                        <div class="me-3 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid #8B7355;">
                            <div style="width: 6px; height: 6px; border-radius: 50%; background-color: #8B7355;"></div>
                        </div>
                        <span style="color: #2C1810; font-weight: 500;">{{ $step['title'] }}</span>
                        <span class="ms-auto text-muted" style="font-weight: 700; color: #8B7355 !important;">+{{ $step['points'] }} pts</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif
