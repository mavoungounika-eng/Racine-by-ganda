{{-- COMPOSANT : Badge créateur en attente --}}
{{-- Usage : @include('components.creator-pending-badge') --}}

<div class="alert alert-warning border-0 shadow-sm mb-4" style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b !important;">
    <div class="d-flex align-items-start">
        <i class="fas fa-clock text-warning mr-3 mt-1" style="font-size: 1.2rem;"></i>
        <div>
            <strong class="text-warning">
                <span class="badge badge-warning mr-2">⏳ Créateur en attente de validation</span>
            </strong>
            <p class="mb-0 mt-2 text-dark" style="font-size: 0.95rem;">
                Votre compte client fonctionne normalement. Vous pouvez continuer à acheter pendant ce temps.
            </p>
        </div>
    </div>
</div>



