<form action="{{ route('admin.settings.update', 'marketplace') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- COMMISSIONS & CRÉATEURS --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-percentage me-2" style="color: #ED5F1E;"></i>Commissions & Créateurs</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Taux de commission (%) <span class="text-danger">*</span></label>
                    <input type="number" name="commission_rate" class="form-control" step="0.01" min="0" max="100"
                           value="{{ old('commission_rate', $settings['commission_rate'] ?? '15.00') }}" required>
                    <small class="text-muted">Prélevé sur ventes créateurs marketplace</small>
                    @error('commission_rate')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Livraison gratuite à partir de (FCFA) <span class="text-danger">*</span></label>
                    <input type="number" name="free_shipping_threshold" class="form-control" step="100" min="0"
                           value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold'] ?? '50000') }}" required>
                    <small class="text-muted">Montant minimum pour activer livraison gratuite</small>
                    @error('free_shipping_threshold')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Frais de livraison par défaut (FCFA) <span class="text-danger">*</span></label>
                    <input type="number" name="shipping_fee" class="form-control" step="100" min="0"
                           value="{{ old('shipping_fee', $settings['shipping_fee'] ?? '2000') }}" required>
                    <small class="text-muted">Frais standard si seuil non atteint</small>
                    @error('shipping_fee')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Devise principale <span class="text-danger">*</span></label>
                    <select name="currency" class="form-select" required>
                        <option value="FCFA" {{ old('currency', $settings['currency'] ?? '') === 'FCFA' ? 'selected' : '' }}>FCFA (XAF)</option>
                        <option value="EUR" {{ old('currency', $settings['currency'] ?? '') === 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                        <option value="USD" {{ old('currency', $settings['currency'] ?? '') === 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                    </select>
                    <small class="text-muted">Devise affichée sur le site</small>
                    @error('currency')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- STOCK & CATALOGUE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-boxes me-2" style="color: #ED5F1E;"></i>Stock & Catalogue</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Seuil stock faible (alerte orange) <span class="text-danger">*</span></label>
                    <input type="number" name="low_stock_threshold" class="form-control" min="1"
                           value="{{ old('low_stock_threshold', $settings['low_stock_threshold'] ?? '10') }}" required>
                    <small class="text-muted">Alerte affichée si stock ≤ cette valeur</small>
                    @error('low_stock_threshold')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Seuil stock critique (alerte rouge) <span class="text-danger">*</span></label>
                    <input type="number" name="low_stock_critical" class="form-control" min="1"
                           value="{{ old('low_stock_critical', $settings['low_stock_critical'] ?? '3') }}" required>
                    <small class="text-muted">Alerte rouge + notification admin</small>
                    @error('low_stock_critical')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Max variantes par produit <span class="text-danger">*</span></label>
                    <input type="number" name="max_variants_per_product" class="form-control" min="1" max="100"
                           value="{{ old('max_variants_per_product', $settings['max_variants_per_product'] ?? '20') }}" required>
                    <small class="text-muted">Limite autorisée (couleurs, tailles...)</small>
                    @error('max_variants_per_product')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="auto_reorder_enabled" id="auto_reorder_enabled"
                               value="1" {{ old('auto_reorder_enabled', $settings['auto_reorder_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="auto_reorder_enabled">
                            Réapprovisionnement automatique
                        </label>
                        <small class="d-block text-muted">Commander automatiquement si stock < seuil critique</small>
                    </div>
                    @error('auto_reorder_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- COMMANDES --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-shopping-cart me-2" style="color: #ED5F1E;"></i>Commandes</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Annulation auto commande non payée (heures) <span class="text-danger">*</span></label>
                    <input type="number" name="order_auto_cancel_hours" class="form-control" min="0" max="168"
                           value="{{ old('order_auto_cancel_hours', $settings['order_auto_cancel_hours'] ?? '24') }}" required>
                    <small class="text-muted">0 = désactivé, max 168h (7 jours)</small>
                    @error('order_auto_cancel_hours')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-store me-2"></i>Enregistrer Marketplace
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.settings.index', 'general') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</form>
