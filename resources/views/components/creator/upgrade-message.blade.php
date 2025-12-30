@props(['capability', 'plan' => 'Officiel'])

<div class="upgrade-message" style="background: linear-gradient(135deg, #FFF8F0 0%, #FFFBF5 100%); border: 2px solid var(--racine-orange); border-radius: var(--radius-lg); padding: 1.5rem; margin: 1rem 0;">
    <div style="display: flex; align-items: start; gap: 1rem;">
        <div style="flex: 1;">
            <h4 style="margin: 0 0 0.5rem 0; color: var(--racine-black); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-lock" style="color: var(--racine-orange);"></i>
                Fonctionnalité Premium
            </h4>
            <p style="margin: 0 0 1rem 0; color: #8B7355; font-size: 0.95rem;">
                {{ $message ?? "Cette fonctionnalité est disponible avec le plan {$plan} ou Premium." }}
            </p>
            <a href="{{ route('creator.subscription.upgrade') }}" 
               style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%); color: white; border-radius: var(--radius-md); text-decoration: none; font-weight: 600; transition: var(--transition-fast);">
                <i class="fas fa-arrow-up"></i>
                Passer au plan {{ $plan }}
            </a>
        </div>
        <button type="button" onclick="this.parentElement.parentElement.style.display='none'" style="background: none; border: none; color: #8B7355; cursor: pointer; font-size: 1.25rem; padding: 0.25rem 0.5rem; opacity: 0.6; transition: var(--transition-fast);">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

