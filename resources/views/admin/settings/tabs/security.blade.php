<form action="{{ route('admin.settings.update', 'security') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- AUTHENTIFICATION & 2FA --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-shield-alt me-2" style="color: #ED5F1E;"></i>Authentification & 2FA</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="force_2fa_admin" id="force_2fa_admin"
                               value="1" {{ old('force_2fa_admin', $settings['force_2fa_admin'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="force_2fa_admin">
                            Forcer 2FA pour tous les admins
                        </label>
                        <small class="d-block text-muted">Les admins sans 2FA seront redirigés vers la configuration</small>
                    </div>
                    @error('force_2fa_admin')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="force_2fa_creator" id="force_2fa_creator"
                               value="1" {{ old('force_2fa_creator', $settings['force_2fa_creator'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="force_2fa_creator">
                            Forcer 2FA pour les créateurs
                        </label>
                        <small class="d-block text-muted">Recommandé pour protéger les comptes créateurs</small>
                    </div>
                    @error('force_2fa_creator')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Timeout session (minutes) <span class="text-danger">*</span></label>
                    <input type="number" name="session_timeout" class="form-control" min="15" max="1440"
                           value="{{ old('session_timeout', $settings['session_timeout'] ?? '120') }}" required>
                    <small class="text-muted">Déconnexion automatique après inactivité</small>
                    @error('session_timeout')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Sessions simultanées max <span class="text-danger">*</span></label>
                    <input type="number" name="max_concurrent_sessions" class="form-control" min="1" max="20"
                           value="{{ old('max_concurrent_sessions', $settings['max_concurrent_sessions'] ?? '5') }}" required>
                    <small class="text-muted">Nombre max de sessions par utilisateur</small>
                    @error('max_concurrent_sessions')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- PROTECTION BRUTE FORCE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-user-lock me-2" style="color: #ED5F1E;"></i>Protection Brute Force</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tentatives login max <span class="text-danger">*</span></label>
                    <input type="number" name="login_max_attempts" class="form-control" min="3" max="20"
                           value="{{ old('login_max_attempts', $settings['login_max_attempts'] ?? '5') }}" required>
                    <small class="text-muted">Tentatives avant blocage</small>
                    @error('login_max_attempts')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Durée blocage (minutes) <span class="text-danger">*</span></label>
                    <input type="number" name="login_lockout_minutes" class="form-control" min="1" max="1440"
                           value="{{ old('login_lockout_minutes', $settings['login_lockout_minutes'] ?? '15') }}" required>
                    <small class="text-muted">Durée du blocage temporaire</small>
                    @error('login_lockout_minutes')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note :</strong> Le rate limiting est actuellement géré par AuthOrchestratorService. Ces paramètres seront appliqués dynamiquement.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- POLITIQUE MOT DE PASSE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-key me-2" style="color: #ED5F1E;"></i>Politique Mot de Passe</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Longueur minimale <span class="text-danger">*</span></label>
                    <input type="number" name="password_min_length" class="form-control" min="6" max="32"
                           value="{{ old('password_min_length', $settings['password_min_length'] ?? '8') }}" required>
                    <small class="text-muted">Nombre minimum de caractères</small>
                    @error('password_min_length')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Expiration (jours) <span class="text-danger">*</span></label>
                    <input type="number" name="password_expiry_days" class="form-control" min="0" max="365"
                           value="{{ old('password_expiry_days', $settings['password_expiry_days'] ?? '0') }}" required>
                    <small class="text-muted">0 = jamais</small>
                    @error('password_expiry_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="password_require_uppercase" id="password_require_uppercase"
                               value="1" {{ old('password_require_uppercase', $settings['password_require_uppercase'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="password_require_uppercase">
                            Majuscule obligatoire
                        </label>
                        <small class="d-block text-muted">Ex: A, B, C...</small>
                    </div>
                    @error('password_require_uppercase')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="password_require_numbers" id="password_require_numbers"
                               value="1" {{ old('password_require_numbers', $settings['password_require_numbers'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="password_require_numbers">
                            Chiffre obligatoire
                        </label>
                        <small class="d-block text-muted">Ex: 0, 1, 2...</small>
                    </div>
                    @error('password_require_numbers')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="password_require_special" id="password_require_special"
                               value="1" {{ old('password_require_special', $settings['password_require_special'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="password_require_special">
                            Caractère spécial obligatoire
                        </label>
                        <small class="d-block text-muted">Ex: !@#$%...</small>
                    </div>
                    @error('password_require_special')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- APPAREILS DE CONFIANCE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-mobile-alt me-2" style="color: #ED5F1E;"></i>Appareils de Confiance</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="trusted_device_enabled" id="trusted_device_enabled"
                               value="1" {{ old('trusted_device_enabled', $settings['trusted_device_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="trusted_device_enabled">
                            Appareils de confiance activés
                        </label>
                        <small class="d-block text-muted">Permet de ne pas redemander le 2FA sur un appareil reconnu</small>
                    </div>
                    @error('trusted_device_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Durée de confiance (jours) <span class="text-danger">*</span></label>
                    <input type="number" name="trusted_device_days" class="form-control" min="1" max="365"
                           value="{{ old('trusted_device_days', $settings['trusted_device_days'] ?? '30') }}" required>
                    <small class="text-muted">Durée avant expiration de la confiance</small>
                    @error('trusted_device_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- WHITELIST IP ADMIN --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-network-wired me-2" style="color: #ED5F1E;"></i>Whitelist IP Admin</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ip_whitelist_enabled" id="ip_whitelist_enabled"
                               value="1" {{ old('ip_whitelist_enabled', $settings['ip_whitelist_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="ip_whitelist_enabled">
                            Restreindre l'accès admin par IP
                        </label>
                        <small class="d-block text-muted">Seules les IPs listées ci-dessous pourront accéder à l'admin</small>
                    </div>
                    @error('ip_whitelist_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">IPs autorisées</label>
                    <textarea name="ip_whitelist" class="form-control" rows="3"
                              placeholder="192.168.1.1&#10;10.0.0.1&#10;203.0.113.0">{{ old('ip_whitelist', $settings['ip_whitelist'] ?? '') }}</textarea>
                    <small class="text-muted">Une par ligne ou séparées par virgule. Votre IP actuelle : <strong>{{ request()->ip() }}</strong></small>
                    @error('ip_whitelist')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <div id="ip-whitelist-warning" class="alert alert-danger" style="display: none; border-left: 4px solid #dc3545;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>⚠️ Attention :</strong> Activer la whitelist IP sans IPs configurées vous bloquera hors de l'admin. Ajoutez au minimum votre IP actuelle ({{ request()->ip() }}) dans la liste.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-shield-alt me-2"></i>Enregistrer Sécurité
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.settings.index', 'general') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</form>

{{-- VANILLA JS POUR ALERTE IP WHITELIST --}}
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    const ipWhitelistEnabled = document.getElementById('ip_whitelist_enabled');
    const ipWhitelistInput = document.querySelector('textarea[name="ip_whitelist"]');
    const ipWarning = document.getElementById('ip-whitelist-warning');

    function checkWhitelistWarning() {
        if (ipWhitelistEnabled && ipWhitelistInput && ipWarning) {
            const enabled = ipWhitelistEnabled.checked;
            const ips = ipWhitelistInput.value.trim();

            if (enabled && ips.length === 0) {
                ipWarning.style.display = 'block';
            } else {
                ipWarning.style.display = 'none';
            }
        }
    }

    if (ipWhitelistEnabled) {
        ipWhitelistEnabled.addEventListener('change', checkWhitelistWarning);
    }

    if (ipWhitelistInput) {
        ipWhitelistInput.addEventListener('input', checkWhitelistWarning);
    }

    // Check initial state
    checkWhitelistWarning();
});
</script>
