{{-- FORMULAIRE INFORMATIONS PERSONNELLES --}}
<form action="{{ route('admin.settings.profile.update') }}" method="POST" class="mb-4">
    @csrf

    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-user me-2" style="color: #ED5F1E;"></i>Informations personnelles</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3 text-center">
                    @php
                        $avatarUrl = old('admin_avatar_url', $settings['admin_avatar_url'] ?? '');
                        $displayName = old('admin_display_name', $settings['admin_display_name'] ?? Auth::user()->name ?? '');
                        $initials = strtoupper(substr($displayName, 0, 1));
                    @endphp

                    @if(!empty($avatarUrl))
                        <img src="{{ $avatarUrl }}" alt="Avatar" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #ED5F1E;">
                    @else
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                             style="width: 100px; height: 100px; background: #ED5F1E; color: white; font-size: 36px; font-weight: bold; border: 3px solid #FFB800;">
                            {{ $initials }}
                        </div>
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nom affiché <span class="text-danger">*</span></label>
                    <input type="text" name="admin_display_name" class="form-control" required maxlength="100"
                           value="{{ old('admin_display_name', $settings['admin_display_name'] ?? Auth::user()->name) }}">
                    <small class="text-muted">Votre nom tel qu'affiché dans l'interface</small>
                    @error('admin_display_name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Langue de l'interface <span class="text-danger">*</span></label>
                    <select name="admin_language" class="form-select" required>
                        <option value="fr" {{ old('admin_language', $settings['admin_language'] ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                        <option value="en" {{ old('admin_language', $settings['admin_language'] ?? 'fr') === 'en' ? 'selected' : '' }}>English</option>
                    </select>
                    @error('admin_language')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Biographie</label>
                    <textarea name="admin_bio" class="form-control" rows="3" maxlength="500"
                              placeholder="Une courte description de vous...">{{ old('admin_bio', $settings['admin_bio'] ?? '') }}</textarea>
                    <small class="text-muted">Description publique (optionnelle)</small>
                    @error('admin_bio')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">URL de l'avatar</label>
                    <input type="url" name="admin_avatar_url" class="form-control"
                           value="{{ old('admin_avatar_url', $settings['admin_avatar_url'] ?? '') }}"
                           placeholder="https://example.com/avatar.jpg">
                    <small class="text-muted">Lien vers votre photo de profil</small>
                    @error('admin_avatar_url')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email actuel</label>
                    <input type="email" class="form-control" value="{{ Auth::user()->email }}" readonly style="background: #f8f9fa;">
                    <small class="text-muted">Modifiable depuis les paramètres du compte</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-bell me-2" style="color: #ED5F1E;"></i>Préférences notifications</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Notifications par email</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="admin_notifications_email" id="admin_notifications_email" value="1"
                               {{ old('admin_notifications_email', $settings['admin_notifications_email'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="admin_notifications_email">Activées</label>
                    </div>
                    <small class="text-muted">Recevoir des emails de notification</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Notifications navigateur</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="admin_notifications_browser" id="admin_notifications_browser" value="1"
                               {{ old('admin_notifications_browser', $settings['admin_notifications_browser'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="admin_notifications_browser">Activées</label>
                    </div>
                    <small class="text-muted">Notifications push dans le navigateur</small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-check me-2"></i>Mettre à jour le profil
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
    </div>
</form>

{{-- FORMULAIRE CHANGEMENT MOT DE PASSE --}}
<form action="{{ route('admin.settings.profile.password') }}" method="POST" class="mb-4">
    @csrf

    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-lock me-2" style="color: #ED5F1E;"></i>Changer le mot de passe</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Mot de passe actuel <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    <small class="text-muted">Saisissez votre mot de passe actuel pour confirmer</small>
                    @error('current_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nouveau mot de passe <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                    <small class="text-muted">Minimum 8 caractères</small>
                    @error('new_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                    <input type="password" name="new_password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    <small class="text-muted">Répétez le nouveau mot de passe</small>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="background: #ED5F1E; border-color: #ED5F1E;">
        <i class="fas fa-key me-2"></i>Changer le mot de passe
    </button>
</form>

{{-- SESSIONS ACTIVES --}}
<div class="card border-0 shadow-sm" style="border-radius:18px;">
    <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
        <h5 class="mb-0"><i class="fas fa-laptop me-2" style="color: #ED5F1E;"></i>Sessions actives</h5>
    </div>
    <div class="card-body">
        @php
            $activeSessions = DB::table('sessions')->where('user_id', auth()->id())->count();
            $lastActivity = Auth::user()->updated_at;
        @endphp

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Dernière activité</label>
                <p class="mb-0">{{ $lastActivity ? $lastActivity->diffForHumans() : 'N/A' }}</p>
                <small class="text-muted">{{ $lastActivity ? $lastActivity->format('d/m/Y H:i') : '' }}</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Sessions actives</label>
                <p class="mb-0">
                    <span class="badge bg-primary" style="font-size: 16px;">{{ $activeSessions }}</span>
                </p>
                <small class="text-muted">Nombre de sessions ouvertes sur différents appareils</small>
            </div>

            <div class="col-md-12">
                <button type="button" id="logout-others-btn" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Déconnecter toutes les autres sessions
                </button>
                <div id="logout-result" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- VANILLA JS POUR DÉCONNEXION DES AUTRES SESSIONS --}}
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logout-others-btn');
    const resultDiv = document.getElementById('logout-result');

    logoutBtn.addEventListener('click', function() {
        if (!confirm('Êtes-vous sûr de vouloir déconnecter toutes les autres sessions ?')) {
            return;
        }

        logoutBtn.disabled = true;
        logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>En cours...';

        fetch('/admin/settings/profile/logout-others', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const alertClass = data.success ? 'success' : 'danger';
            const iconClass = data.success ? 'check-circle' : 'exclamation-circle';

            resultDiv.innerHTML = `
                <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${iconClass} me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            logoutBtn.disabled = false;
            logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Déconnecter toutes les autres sessions';

            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>Erreur lors de la déconnexion
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            logoutBtn.disabled = false;
            logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Déconnecter toutes les autres sessions';
        });
    });
});
</script>
