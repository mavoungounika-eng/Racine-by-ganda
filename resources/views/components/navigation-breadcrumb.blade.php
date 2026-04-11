@props([
    'items' => [],
    'showBackButton' => true,
    'backUrl' => null,
    'backText' => 'Retour',
    'position' => 'bottom', // 'top' ou 'bottom'
])

@php
    // Si backUrl n'est pas fourni, utiliser celui du composer ou déterminer la page précédente logique
    if (!$backUrl && $showBackButton) {
        // Utiliser la variable globale du composer si disponible
        $backUrl = isset($backUrl) ? $backUrl : (url()->previous() ?? route('frontend.home'));
    }
    
    // Si toujours null, utiliser l'accueil par défaut
    if (!$backUrl) {
        $backUrl = route('frontend.home');
    }
@endphp

<nav class="navigation-breadcrumb navigation-breadcrumb-{{ $position }}" aria-label="Breadcrumb">
    <div class="container">
        <div class="breadcrumb-wrapper">
            @if($showBackButton)
            <a href="{{ $backUrl }}" class="breadcrumb-back-btn" title="Retour">
                <i class="fas fa-arrow-left"></i>
                <span>{{ $backText }}</span>
            </a>
            @endif
            
            @if(!empty($items))
            <ol class="breadcrumb-list">
                @foreach($items as $index => $item)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $item['label'] ?? $item }}
                        </li>
                    @else
                        <li class="breadcrumb-item">
                            @if(isset($item['url']))
                                <a href="{{ $item['url'] }}">{{ $item['label'] ?? $item }}</a>
                            @else
                                <span>{{ $item['label'] ?? $item }}</span>
                            @endif
                            <i class="fas fa-chevron-right breadcrumb-separator"></i>
                        </li>
                    @endif
                @endforeach
            </ol>
            @endif
        </div>
    </div>
</nav>

@push('styles')
<style>
    .navigation-breadcrumb {
        background: rgba(44, 24, 16, 0.05);
        padding: 1rem 0;
    }
    
    .navigation-breadcrumb-top {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
    }
    
    .navigation-breadcrumb-bottom {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        margin-top: 3rem;
        padding-top: 2rem;
        padding-bottom: 2rem;
        background: rgba(248, 246, 243, 0.98);
        backdrop-filter: blur(10px);
    }
    
    .breadcrumb-wrapper {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    
    .breadcrumb-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: white;
        color: #2C1810;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .breadcrumb-back-btn:hover {
        background: #F8F6F3;
        color: #ED5F1E;
        transform: translateX(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .breadcrumb-back-btn i {
        font-size: 0.85rem;
        transition: transform 0.3s;
    }
    
    .breadcrumb-back-btn:hover i {
        transform: translateX(-2px);
    }
    
    .breadcrumb-list {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }
    
    .breadcrumb-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #8B7355;
    }
    
    .breadcrumb-item a {
        color: #8B7355;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .breadcrumb-item a:hover {
        color: #ED5F1E;
    }
    
    .breadcrumb-item.active {
        color: #2C1810;
        font-weight: 500;
    }
    
    .breadcrumb-separator {
        font-size: 0.7rem;
        color: #ddd;
        margin: 0 0.25rem;
    }
    
    @media (max-width: 768px) {
        .breadcrumb-wrapper {
            gap: 1rem;
        }
        
        .breadcrumb-back-btn span {
            display: none;
        }
        
        .breadcrumb-back-btn {
            padding: 0.5rem;
            width: 40px;
            height: 40px;
            justify-content: center;
            border-radius: 50%;
        }
    }
</style>
@endpush

