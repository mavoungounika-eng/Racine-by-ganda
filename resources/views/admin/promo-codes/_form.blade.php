{{-- Formulaire partagé création + édition code promo
     Variables attendues :
       $promoCode (optionnel, null pour create)
       $action    (URL de submit)
       $method    (POST pour create, PUT pour update)
--}}
@php
    /** @var \App\Models\PromoCode|null $promoCode */
    $pc = $promoCode ?? null;
    $fmtDateTimeLocal = fn($dt) => $dt ? \Illuminate\Support\Carbon::parse($dt)->format('Y-m-d\TH:i') : '';
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    {{-- Identification --}}
    <div class="mb-5">
        <h5 class="fw-bold mb-4 text-racine-black">
            <i class="fas fa-barcode text-racine-orange me-2"></i>
            Identification
        </h5>
        <div class="row g-4">
            @include('partials.admin.form-group', [
                'label' => 'Code',
                'name' => 'code',
                'type' => 'text',
                'required' => true,
                'col' => 6,
                'value' => old('code', $pc?->code),
                'placeholder' => 'ex. BIENVENUE10',
                'help' => 'Le code sera converti en majuscules et doit être unique.',
            ])

            @include('partials.admin.form-group', [
                'label' => 'Nom interne',
                'name' => 'name',
                'type' => 'text',
                'required' => true,
                'col' => 6,
                'value' => old('name', $pc?->name),
                'placeholder' => 'ex. Campagne de bienvenue',
            ])

            @include('partials.admin.form-group', [
                'label' => 'Description (optionnelle)',
                'name' => 'description',
                'type' => 'textarea',
                'required' => false,
                'col' => 12,
                'rows' => 3,
                'value' => old('description', $pc?->description),
                'placeholder' => 'Note interne sur ce code (non exposé au client)',
            ])
        </div>
    </div>

    {{-- Réduction --}}
    <div class="mb-5">
        <h5 class="fw-bold mb-4 text-racine-black">
            <i class="fas fa-percent text-racine-orange me-2"></i>
            Type de réduction
        </h5>
        <div class="row g-4">
            <div class="col-md-6 mb-4">
                <label for="type" class="form-label fw-semibold mb-2">
                    Type <span class="text-danger">*</span>
                </label>
                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                    @php $selectedType = old('type', $pc?->type ?? 'percentage'); @endphp
                    <option value="percentage"    {{ $selectedType === 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                    <option value="fixed"         {{ $selectedType === 'fixed' ? 'selected' : '' }}>Montant fixe (FCFA)</option>
                    <option value="free_shipping" {{ $selectedType === 'free_shipping' ? 'selected' : '' }}>Livraison gratuite</option>
                </select>
                @error('type')
                    <div class="invalid-feedback d-block">
                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-md-6 mb-4" id="value-field">
                <label for="value" class="form-label fw-semibold mb-2">
                    Valeur <span class="text-danger" id="value-required-star">*</span>
                </label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0"
                           name="value" id="value"
                           value="{{ old('value', $pc?->value) }}"
                           class="form-control @error('value') is-invalid @enderror"
                           placeholder="ex. 10">
                    <span class="input-group-text" id="value-unit">%</span>
                </div>
                <div class="form-text text-muted small mt-1" id="value-help">
                    <i class="fas fa-info-circle me-1"></i>
                    Pourcentage : 1 à 100. Montant fixe : en FCFA.
                </div>
                @error('value')
                    <div class="invalid-feedback d-block">
                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>

            @include('partials.admin.form-group', [
                'label' => 'Montant minimum de commande (optionnel)',
                'name' => 'min_amount',
                'type' => 'number',
                'required' => false,
                'col' => 6,
                'value' => old('min_amount', $pc?->min_amount),
                'placeholder' => 'ex. 25000',
                'help' => 'En FCFA. Le code ne sera appliqué que si le panier dépasse ce montant.',
                'step' => '0.01',
                'min' => '0',
            ])
        </div>
    </div>

    {{-- Limites d utilisation --}}
    <div class="mb-5">
        <h5 class="fw-bold mb-4 text-racine-black">
            <i class="fas fa-users text-racine-orange me-2"></i>
            Limites d utilisation
        </h5>
        <div class="row g-4">
            @include('partials.admin.form-group', [
                'label' => 'Nombre maximum d utilisations (optionnel)',
                'name' => 'max_uses',
                'type' => 'number',
                'required' => false,
                'col' => 6,
                'value' => old('max_uses', $pc?->max_uses),
                'placeholder' => 'ex. 100',
                'help' => 'Laissez vide pour illimité.',
                'min' => '1',
                'step' => '1',
            ])

            @include('partials.admin.form-group', [
                'label' => 'Utilisations max par client (optionnel)',
                'name' => 'max_uses_per_user',
                'type' => 'number',
                'required' => false,
                'col' => 6,
                'value' => old('max_uses_per_user', $pc?->max_uses_per_user),
                'placeholder' => 'ex. 1',
                'help' => 'Laissez vide pour illimité.',
                'min' => '1',
                'step' => '1',
            ])
        </div>
    </div>

    {{-- Période --}}
    <div class="mb-5">
        <h5 class="fw-bold mb-4 text-racine-black">
            <i class="fas fa-calendar-alt text-racine-orange me-2"></i>
            Période de validité
        </h5>
        <div class="row g-4">
            @include('partials.admin.form-group', [
                'label' => 'Date de début (optionnelle)',
                'name' => 'starts_at',
                'type' => 'datetime-local',
                'required' => false,
                'col' => 6,
                'value' => old('starts_at', $fmtDateTimeLocal($pc?->starts_at)),
                'help' => 'Le code sera inactif avant cette date.',
            ])

            @include('partials.admin.form-group', [
                'label' => 'Date d expiration (optionnelle)',
                'name' => 'expires_at',
                'type' => 'datetime-local',
                'required' => false,
                'col' => 6,
                'value' => old('expires_at', $fmtDateTimeLocal($pc?->expires_at)),
                'help' => 'Le code ne pourra plus être utilisé après cette date.',
            ])
        </div>
    </div>

    {{-- Statut --}}
    <div class="mb-5">
        <h5 class="fw-bold mb-4 text-racine-black">
            <i class="fas fa-toggle-on text-racine-orange me-2"></i>
            Statut
        </h5>
        <div class="row g-4">
            @include('partials.admin.form-group', [
                'label' => 'Activation',
                'name' => 'is_active',
                'type' => 'checkbox',
                'required' => false,
                'col' => 12,
                'checked' => old('is_active', $pc?->is_active ?? true),
                'checkLabel' => 'Code actif (disponible au checkout)',
            ])
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
        <a href="{{ route('admin.promo-codes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-times me-2"></i>Annuler
        </a>
        <button type="submit" class="btn btn-racine-orange">
            <i class="fas fa-save me-2"></i>
            {{ $submitLabel ?? 'Enregistrer' }}
        </button>
    </div>
</form>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function() {
    const typeSelect = document.getElementById('type');
    const valueField = document.getElementById('value-field');
    const valueInput = document.getElementById('value');
    const valueUnit  = document.getElementById('value-unit');
    const valueHelp  = document.getElementById('value-help');
    const valueStar  = document.getElementById('value-required-star');

    function syncValueField() {
        const t = typeSelect.value;
        if (t === 'free_shipping') {
            valueField.style.display = 'none';
            valueInput.required = false;
            if (valueStar) valueStar.style.display = 'none';
            valueInput.value = 0;
        } else {
            valueField.style.display = '';
            valueInput.required = true;
            if (valueStar) valueStar.style.display = '';
            if (t === 'percentage') {
                valueUnit.textContent = '%';
                valueInput.max = 100;
                valueHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Pourcentage entre 1 et 100.';
            } else {
                valueUnit.textContent = 'FCFA';
                valueInput.removeAttribute('max');
                valueHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Montant en FCFA retranché du total.';
            }
        }
    }

    typeSelect.addEventListener('change', syncValueField);
    syncValueField();
})();
</script>
@endpush
