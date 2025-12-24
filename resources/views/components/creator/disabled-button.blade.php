@props(['capability', 'plan' => 'Officiel', 'label'])

@if(auth()->user()->hasCapability($capability))
    {{ $slot }}
@else
    <div style="position: relative; display: inline-block;">
        <button type="button" 
                disabled 
                style="opacity: 0.5; cursor: not-allowed; position: relative;"
                data-tooltip="Fonctionnalité disponible avec le plan {{ $plan }}"
                title="Fonctionnalité disponible avec le plan {{ $plan }}">
            {{ $label ?? $slot }}
        </button>
        <div style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 0.5rem; padding: 0.5rem 0.75rem; background: var(--racine-black); color: white; border-radius: var(--radius-sm); font-size: 0.75rem; white-space: nowrap; opacity: 0; pointer-events: none; transition: var(--transition-fast); z-index: 1000;">
            Plan {{ $plan }} requis
            <div style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 4px solid transparent; border-top-color: var(--racine-black);"></div>
        </div>
    </div>
@endif

