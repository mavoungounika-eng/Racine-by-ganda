@php
    $currentRoute = Route::currentRouteName();
    $creatorProfile = Auth::user()->creatorProfile;
    $shopUrl = $creatorProfile ? route('frontend.creator.shop', $creatorProfile->slug) : '#';
@endphp

<div class="creator-nav-container mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
        {{-- Navigation Tabs --}}
        <div class="creator-tabs-wrapper d-flex flex-wrap gap-2">
            <a href="{{ route('creator.settings.shop') }}" 
               class="creator-tab-link {{ $currentRoute === 'creator.settings.shop' ? 'active' : '' }}">
                <i class="fas fa-store"></i>
                Ma Vitrine
            </a>
            
            <a href="{{ route('creator.settings.payment') }}" 
               class="creator-tab-link {{ in_array($currentRoute, ['creator.settings.payment', 'creator.settings.payment-preferences.index']) ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i>
                Paiements
            </a>
            
            <a href="{{ route('creator.finances.index') }}" 
               class="creator-tab-link {{ Str::startsWith($currentRoute, 'creator.finances') ? 'active' : '' }}">
                <i class="fas fa-coins"></i>
                Mes Finances
            </a>
            
            <a href="{{ route('creator.profile.show') }}" 
               class="creator-tab-link {{ Str::startsWith($currentRoute, 'creator.profile') ? 'active' : '' }}">
                <i class="fas fa-user-circle"></i>
                Mon Profil
            </a>

            <a href="{{ route('creator.subscription.upgrade') }}" 
               class="creator-tab-link {{ Str::contains($currentRoute, 'subscription') ? 'active' : '' }}">
                <i class="fas fa-gem"></i>
                Abonnements
            </a>
        </div>


        {{-- Public Action --}}
        <div class="creator-nav-actions">
            <a href="{{ $shopUrl }}" target="_blank" class="creator-btn-view-public">
                <i class="fas fa-external-link-alt me-2"></i>
                Voir ma boutique publique
            </a>
        </div>
    </div>
</div>

<style>
    .creator-tabs-wrapper {
        background: #F8F6F3;
        padding: 6px;
        border-radius: 12px;
        border: 1px solid #E5DDD3;
    }

    .creator-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        color: #8B7355;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .creator-tab-link:hover {
        color: #160D0C;
        background: rgba(212, 165, 116, 0.1);
        text-decoration: none;
    }

    .creator-tab-link.active {
        background: white;
        color: #ED5F1E;
        box-shadow: 0 4px 12px rgba(22, 13, 12, 0.05);
    }

    .creator-tab-link i {
        font-size: 1rem;
    }

    .creator-btn-view-public {
        background: var(--racine-black);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: inline-flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(22, 13, 12, 0.15);
    }

    .creator-btn-view-public:hover {
        background: #ED5F1E;
        color: white;
        transform: translateY(-3px);
        box-shadow: var(--shadow-orange);
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .creator-tabs-wrapper {
            width: 100%;
        }
        .creator-tab-link {
            flex: 1;
            justify-content: center;
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        .creator-btn-view-public {
            width: 100%;
            justify-content: center;
        }
    }
</style>
