<form action="{{ route('admin.settings.update', 'email') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- CONFIGURATION SMTP --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-server me-2" style="color: #ED5F1E;"></i>Configuration SMTP</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-3" style="border-left: 4px solid #ffc107;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Note :</strong> Les paramètres SMTP modifiés ici mettent à jour la configuration en base de données. Les valeurs du fichier .env restent en place comme fallback.
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nom de l'expéditeur <span class="text-danger">*</span></label>
                    <input type="text" name="mail_from_name" class="form-control"
                           value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'RACINE BY GANDA') }}" required>
                    <small class="text-muted">Nom affiché dans l'email (From)</small>
                    @error('mail_from_name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email expéditeur (From) <span class="text-danger">*</span></label>
                    <input type="email" name="mail_from_address" class="form-control"
                           value="{{ old('mail_from_address', $settings['mail_from_address'] ?? 'contact@racinebyganda.com') }}" required>
                    <small class="text-muted">Adresse email expéditeur</small>
                    @error('mail_from_address')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <button type="button" id="test-email-btn" class="btn btn-outline-primary">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer un email de test
                    </button>
                    <div id="email-test-result" class="mt-2"></div>
                    <small class="d-block text-muted mt-2">L'email de test sera envoyé à votre adresse ({{ auth()->user()->email }})</small>
                </div>
            </div>
        </div>
    </div>

    {{-- NOTIFICATIONS ADMIN --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-bell me-2" style="color: #ED5F1E;"></i>Notifications Admin</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="admin_notification_enabled" id="admin_notification_enabled"
                               value="1" {{ old('admin_notification_enabled', $settings['admin_notification_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="admin_notification_enabled">
                            Recevoir les notifications admin par email
                        </label>
                        <small class="d-block text-muted">Notifications commandes, paiements, stock faible...</small>
                    </div>
                    @error('admin_notification_enabled')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email de destination des notifications</label>
                    <input type="email" name="admin_notification_email" class="form-control"
                           value="{{ old('admin_notification_email', $settings['admin_notification_email'] ?? '') }}"
                           placeholder="{{ auth()->user()->email }}">
                    <small class="text-muted">Laisser vide pour utiliser l'email du compte admin</small>
                    @error('admin_notification_email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">URL du logo dans les emails</label>
                    <input type="url" name="mail_logo_url" class="form-control"
                           value="{{ old('mail_logo_url', $settings['mail_logo_url'] ?? '') }}"
                           placeholder="https://racinebyganda.com/logo.png">
                    <small class="text-muted">Utilisé dans les templates email transactionnels</small>
                    @error('mail_logo_url')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- STATUT DE CONFIGURATION (READ-ONLY) --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2" style="color: #ED5F1E;"></i>Statut de configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    @php
                        $mailHost = config('mail.mailers.smtp.host');
                        $mailPort = config('mail.mailers.smtp.port');
                        $mailMailer = config('mail.default');
                        $isConfigured = !empty($mailHost) && $mailHost !== '127.0.0.1';
                    @endphp

                    @if($isConfigured)
                        <div class="alert alert-success mb-3" style="border-left: 4px solid #28a745;">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>SMTP Configuré</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Mailer actif:</strong> {{ $mailMailer }}</li>
                                <li><strong>Hôte SMTP:</strong> {{ $mailHost }}</li>
                                <li><strong>Port:</strong> {{ $mailPort }}</li>
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-danger mb-3" style="border-left: 4px solid #dc3545;">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>SMTP Non configuré</strong>
                            <p class="mb-0 mt-2">Aucun serveur SMTP détecté dans la configuration. Les emails ne seront pas envoyés.</p>
                        </div>
                    @endif

                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-cog me-2"></i>
                        <strong>Modifier la configuration SMTP complète :</strong>
                        <p class="mb-0 mt-2">Pour changer le serveur SMTP (hôte, port, credentials), modifiez les variables suivantes dans le fichier .env :</p>
                        <ul class="mb-0 mt-2">
                            <li><code>MAIL_MAILER</code> (smtp, sendmail, log...)</li>
                            <li><code>MAIL_HOST</code> (smtp.gmail.com, smtp.sendgrid.net...)</li>
                            <li><code>MAIL_PORT</code> (587, 465...)</li>
                            <li><code>MAIL_USERNAME</code></li>
                            <li><code>MAIL_PASSWORD</code></li>
                            <li><code>MAIL_ENCRYPTION</code> (tls, ssl)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-envelope me-2"></i>Enregistrer Email & SMTP
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.settings.index', 'general') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</form>

{{-- VANILLA JS POUR TEST EMAIL --}}
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    const testEmailBtn = document.getElementById('test-email-btn');
    const emailResult = document.getElementById('email-test-result');

    if (testEmailBtn) {
        testEmailBtn.addEventListener('click', function() {
            testEmailBtn.disabled = true;
            testEmailBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
            emailResult.innerHTML = '';

            fetch('{{ route("admin.settings.test-email") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                testEmailBtn.disabled = false;
                testEmailBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer un email de test';

                if (data.success) {
                    emailResult.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                } else {
                    emailResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                testEmailBtn.disabled = false;
                testEmailBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer un email de test';
                emailResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erreur réseau: ' + error.message + '</div>';
            });
        });
    }
});
</script>
