<form action="{{ route('admin.settings.update', 'payments') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- STRIPE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fab fa-stripe me-2" style="color: #6772E5;"></i>Stripe</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mode Stripe <span class="text-danger">*</span></label>
                    <select name="stripe_mode" class="form-select" required>
                        <option value="test" {{ old('stripe_mode', $settings['stripe_mode'] ?? 'test') === 'test' ? 'selected' : '' }}>Test (Sandbox)</option>
                        <option value="live" {{ old('stripe_mode', $settings['stripe_mode'] ?? 'test') === 'live' ? 'selected' : '' }}>Live (Production)</option>
                    </select>
                    <small class="text-muted">Basculer entre environnement test et production</small>
                    @error('stripe_mode')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Devise Stripe <span class="text-danger">*</span></label>
                    <select name="stripe_currency" class="form-select" required>
                        <option value="EUR" {{ old('stripe_currency', $settings['stripe_currency'] ?? 'EUR') === 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                        <option value="USD" {{ old('stripe_currency', $settings['stripe_currency'] ?? 'EUR') === 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                        <option value="GBP" {{ old('stripe_currency', $settings['stripe_currency'] ?? 'EUR') === 'GBP' ? 'selected' : '' }}>GBP (Pound)</option>
                    </select>
                    <small class="text-muted">Devise utilisée pour les paiements Stripe</small>
                    @error('stripe_currency')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="payments_enabled" id="payments_enabled"
                               value="1" {{ old('payments_enabled', $settings['payments_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="payments_enabled">
                            Paiements en ligne activés
                        </label>
                        <small class="d-block text-muted">Autoriser les paiements en ligne sur le site</small>
                    </div>
                    @error('payments_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Clés API Stripe :</strong> Les clés API (STRIPE_KEY, STRIPE_SECRET) sont configurées dans le fichier .env pour des raisons de sécurité. Contactez l'administrateur système pour les modifier.
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="button" id="test-stripe-btn" class="btn btn-outline-primary">
                        <i class="fas fa-plug me-2"></i>Tester la connexion Stripe
                    </button>
                    <div id="stripe-test-result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- MONETBIL (MOBILE MONEY) --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-mobile-alt me-2" style="color: #ED5F1E;"></i>Monetbil (Mobile Money)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Auto-approuver si montant &lt; (FCFA) <span class="text-danger">*</span></label>
                    <input type="number" name="monetbil_auto_approve_threshold" class="form-control" min="0"
                           value="{{ old('monetbil_auto_approve_threshold', $settings['monetbil_auto_approve_threshold'] ?? '0') }}" required>
                    <small class="text-muted">0 = validation manuelle toujours requise</small>
                    @error('monetbil_auto_approve_threshold')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Tentatives max par paiement <span class="text-danger">*</span></label>
                    <input type="number" name="payment_max_attempts" class="form-control" min="1" max="10"
                           value="{{ old('payment_max_attempts', $settings['payment_max_attempts'] ?? '3') }}" required>
                    <small class="text-muted">Nombre de tentatives avant blocage</small>
                    @error('payment_max_attempts')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch" style="margin-top: 32px;">
                        <input class="form-check-input" type="checkbox" name="payment_retry_enabled" id="payment_retry_enabled"
                               value="1" {{ old('payment_retry_enabled', $settings['payment_retry_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="payment_retry_enabled">
                            Retry automatique sur échec
                        </label>
                    </div>
                    @error('payment_retry_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Clés Monetbil :</strong> Les clés (MONETBIL_SERVICE_KEY, MONETBIL_SERVICE_SECRET) sont configurées dans le fichier .env.
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="button" id="test-monetbil-btn" class="btn btn-outline-primary">
                        <i class="fas fa-plug me-2"></i>Tester la connexion Monetbil
                    </button>
                    <div id="monetbil-test-result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- OPTIONS GLOBALES --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-cogs me-2" style="color: #ED5F1E;"></i>Options globales</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-secondary mb-0" style="border-left: 4px solid #6c757d;">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Providers actifs :</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Stripe</strong> — Paiements carte bancaire (EUR, USD, GBP)</li>
                    <li><strong>Monetbil</strong> — Mobile Money Afrique (XAF, MTN, Orange, Airtel)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-credit-card me-2"></i>Enregistrer Paiements
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.settings.index', 'general') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</form>

{{-- VANILLA JS POUR TESTS CONNEXION --}}
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    // Test Stripe
    const testStripeBtn = document.getElementById('test-stripe-btn');
    const stripeResult = document.getElementById('stripe-test-result');

    if (testStripeBtn) {
        testStripeBtn.addEventListener('click', function() {
            testStripeBtn.disabled = true;
            testStripeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Test en cours...';
            stripeResult.innerHTML = '';

            fetch('{{ route("admin.settings.test.stripe") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                testStripeBtn.disabled = false;
                testStripeBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester la connexion Stripe';

                if (data.success) {
                    stripeResult.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                } else {
                    stripeResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                testStripeBtn.disabled = false;
                testStripeBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester la connexion Stripe';
                stripeResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erreur réseau: ' + error.message + '</div>';
            });
        });
    }

    // Test Monetbil
    const testMonetbilBtn = document.getElementById('test-monetbil-btn');
    const monetbilResult = document.getElementById('monetbil-test-result');

    if (testMonetbilBtn) {
        testMonetbilBtn.addEventListener('click', function() {
            testMonetbilBtn.disabled = true;
            testMonetbilBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Test en cours...';
            monetbilResult.innerHTML = '';

            fetch('{{ route("admin.settings.test.monetbil") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                testMonetbilBtn.disabled = false;
                testMonetbilBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester la connexion Monetbil';

                if (data.success) {
                    monetbilResult.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                } else {
                    monetbilResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                testMonetbilBtn.disabled = false;
                testMonetbilBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester la connexion Monetbil';
                monetbilResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erreur réseau: ' + error.message + '</div>';
            });
        });
    }
});
</script>
