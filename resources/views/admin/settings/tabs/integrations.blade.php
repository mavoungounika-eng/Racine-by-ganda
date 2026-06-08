<form action="{{ route('admin.settings.update', 'integrations') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- GOOGLE OAUTH --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fab fa-google me-2" style="color: #4285F4;"></i>Google OAuth</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="google_oauth_enabled" id="google_oauth_enabled"
                               value="1" {{ old('google_oauth_enabled', $settings['google_oauth_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="google_oauth_enabled">
                            Google OAuth activé
                        </label>
                        <small class="d-block text-muted">Autoriser la connexion via Google</small>
                    </div>
                    @error('google_oauth_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">URI de redirection</label>
                    <input type="text" name="google_redirect_uri" class="form-control"
                           value="{{ old('google_redirect_uri', $settings['google_redirect_uri'] ?? '') }}"
                           placeholder="https://racinebyganda.com/auth/google/callback">
                    <small class="text-muted">Ex: https://racinebyganda.com/auth/google/callback</small>
                    @error('google_redirect_uri')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Clés Google OAuth :</strong> Les clés (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET) sont configurées dans le fichier .env.
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="button" id="test-google-btn" class="btn btn-outline-primary">
                        <i class="fas fa-plug me-2"></i>Tester Google OAuth
                    </button>
                    <div id="google-test-result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- RECAPTCHA V3 --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-shield-alt me-2" style="color: #ED5F1E;"></i>reCAPTCHA v3</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="recaptcha_enabled" id="recaptcha_enabled"
                               value="1" {{ old('recaptcha_enabled', $settings['recaptcha_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="recaptcha_enabled">
                            reCAPTCHA activé
                        </label>
                        <small class="d-block text-muted">Activer la protection anti-bot</small>
                    </div>
                    @error('recaptcha_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Score minimum (0.0-1.0) <span class="text-danger">*</span></label>
                    <input type="number" name="recaptcha_threshold" class="form-control" step="0.1" min="0" max="1"
                           value="{{ old('recaptcha_threshold', $settings['recaptcha_threshold'] ?? '0.5') }}" required>
                    <small class="text-muted">0.0 = permissif, 1.0 = strict</small>
                    @error('recaptcha_threshold')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="alert alert-warning mb-0" style="border-left: 4px solid #ffc107;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Configuration reCAPTCHA :</strong> Les clés (RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY) sont dans le fichier .env. Le reCAPTCHA ne fonctionnera correctement qu'une fois le domaine racinebyganda.com pointé sur ce serveur.
                    </div>
                </div>

                <div class="col-md-12">
                    @php
                        $recaptchaEnabled = old('recaptcha_enabled', $settings['recaptcha_enabled'] ?? false);
                    @endphp
                    @if($recaptchaEnabled)
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actuellement activé</span>
                    @else
                        <span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Actuellement désactivé</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- OPENAI & AMIRA --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-robot me-2" style="color: #ED5F1E;"></i>OpenAI & Amira</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="openai_enabled" id="openai_enabled"
                               value="1" {{ old('openai_enabled', $settings['openai_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="openai_enabled">
                            Module Amira/IA activé
                        </label>
                        <small class="d-block text-muted">Activer l'assistante IA Amira</small>
                    </div>
                    @error('openai_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Provider IA <span class="text-danger">*</span></label>
                    <select name="amira_provider" class="form-select" required>
                        <option value="openai" {{ old('amira_provider', $settings['amira_provider'] ?? 'openai') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                        <option value="gemini" {{ old('amira_provider', $settings['amira_provider'] ?? 'openai') === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                        <option value="anthropic" {{ old('amira_provider', $settings['amira_provider'] ?? 'openai') === 'anthropic' ? 'selected' : '' }}>Anthropic Claude</option>
                    </select>
                    <small class="text-muted">Provider IA utilisé pour Amira</small>
                    @error('amira_provider')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Modèle <span class="text-danger">*</span></label>
                    <input type="text" name="openai_model" class="form-control"
                           value="{{ old('openai_model', $settings['openai_model'] ?? 'gemini-2.0-flash') }}" required>
                    <small class="text-muted">Ex: gpt-4o-mini, gemini-2.0-flash</small>
                    @error('openai_model')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Max tokens par requête <span class="text-danger">*</span></label>
                    <input type="number" name="openai_max_tokens" class="form-control" min="100" max="8000"
                           value="{{ old('openai_max_tokens', $settings['openai_max_tokens'] ?? '1000') }}" required>
                    <small class="text-muted">Limite de tokens générés</small>
                    @error('openai_max_tokens')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Température (0-2) <span class="text-danger">*</span></label>
                    <input type="number" name="openai_temperature" class="form-control" step="0.1" min="0" max="2"
                           value="{{ old('openai_temperature', $settings['openai_temperature'] ?? '0.7') }}" required>
                    <small class="text-muted">0 = déterministe, 1 = créatif</small>
                    @error('openai_temperature')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Clé API :</strong> La clé API (OPENAI_API_KEY ou équivalent selon provider) est dans le fichier .env.
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="button" id="test-ai-btn" class="btn btn-outline-primary">
                        <i class="fas fa-plug me-2"></i>Tester connexion IA
                    </button>
                    <div id="ai-test-result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SENTRY MONITORING --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-bug me-2" style="color: #ED5F1E;"></i>Sentry Monitoring</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sentry_enabled" id="sentry_enabled"
                               value="1" {{ old('sentry_enabled', $settings['sentry_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="sentry_enabled">
                            Monitoring Sentry activé
                        </label>
                        <small class="d-block text-muted">Activer le suivi des erreurs</small>
                    </div>
                    @error('sentry_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Taux échantillonnage <span class="text-danger">*</span></label>
                    <input type="number" name="sentry_traces_rate" class="form-control" step="0.01" min="0" max="1"
                           value="{{ old('sentry_traces_rate', $settings['sentry_traces_rate'] ?? '0.1') }}" required>
                    <small class="text-muted">0.1 = 10% des requêtes</small>
                    @error('sentry_traces_rate')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>DSN Sentry :</strong> Le DSN Sentry (SENTRY_LARAVEL_DSN) est configuré dans le fichier .env.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- EXCHANGE RATE API --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-exchange-alt me-2" style="color: #ED5F1E;"></i>Exchange Rate API</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Cache taux de change (heures) <span class="text-danger">*</span></label>
                    <input type="number" name="exchange_rate_cache_ttl" class="form-control" min="1" max="168"
                           value="{{ old('exchange_rate_cache_ttl', $settings['exchange_rate_cache_ttl'] ?? '24') }}" required>
                    <small class="text-muted">Durée du cache des taux de change</small>
                    @error('exchange_rate_cache_ttl')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Clé API :</strong> La clé Exchange Rate API (EXCHANGE_RATE_API_KEY) est dans le fichier .env.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plug me-2"></i>Enregistrer Intégrations
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
    // Test Google OAuth
    const testGoogleBtn = document.getElementById('test-google-btn');
    const googleResult = document.getElementById('google-test-result');

    if (testGoogleBtn) {
        testGoogleBtn.addEventListener('click', function() {
            testGoogleBtn.disabled = true;
            testGoogleBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Test en cours...';
            googleResult.innerHTML = '';

            fetch('{{ route("admin.settings.test.google") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                testGoogleBtn.disabled = false;
                testGoogleBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester Google OAuth';

                const alertClass = data.success ? 'alert-success' : 'alert-danger';
                const icon = data.success ? 'check-circle' : 'times-circle';
                googleResult.innerHTML = '<div class="alert ' + alertClass + '"><i class="fas fa-' + icon + ' me-2"></i>' + data.message + '</div>';
            })
            .catch(error => {
                testGoogleBtn.disabled = false;
                testGoogleBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester Google OAuth';
                googleResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erreur réseau: ' + error.message + '</div>';
            });
        });
    }

    // Test AI
    const testAiBtn = document.getElementById('test-ai-btn');
    const aiResult = document.getElementById('ai-test-result');

    if (testAiBtn) {
        testAiBtn.addEventListener('click', function() {
            testAiBtn.disabled = true;
            testAiBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Test en cours...';
            aiResult.innerHTML = '';

            fetch('{{ route("admin.settings.test.ai") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                testAiBtn.disabled = false;
                testAiBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester connexion IA';

                const alertClass = data.success ? 'alert-success' : 'alert-danger';
                const icon = data.success ? 'check-circle' : 'times-circle';
                aiResult.innerHTML = '<div class="alert ' + alertClass + '"><i class="fas fa-' + icon + ' me-2"></i>' + data.message + '</div>';
            })
            .catch(error => {
                testAiBtn.disabled = false;
                testAiBtn.innerHTML = '<i class="fas fa-plug me-2"></i>Tester connexion IA';
                aiResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erreur réseau: ' + error.message + '</div>';
            });
        });
    }
});
</script>
