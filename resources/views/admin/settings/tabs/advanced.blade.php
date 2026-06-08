<form action="{{ route('admin.settings.update', 'advanced') }}" method="POST">
    @csrf

    {{-- MAINTENANCE & ACCÈS --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-tools me-2" style="color: #ED5F1E;"></i>Maintenance & Accès</h5>
        </div>
        <div class="card-body">
            @if(old('maintenance_mode', $settings['maintenance_mode'] ?? false))
                <div class="alert alert-danger mb-3" style="border-left: 4px solid #dc3545;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>⚠️ Mode maintenance actif :</strong> Le site est actuellement en maintenance pour les visiteurs.
                </div>
            @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Mode maintenance</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="maintenance_mode" id="maintenance_mode" value="1"
                               {{ old('maintenance_mode', $settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="maintenance_mode">Activé</label>
                    </div>
                    <small class="text-muted">Le site affiche une page de maintenance pour les visiteurs</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Inscriptions publiques</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="registrations_enabled" id="registrations_enabled" value="1"
                               {{ old('registrations_enabled', $settings['registrations_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="registrations_enabled">Activées</label>
                    </div>
                    <small class="text-muted">Autoriser les nouvelles inscriptions publiques</small>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Message de maintenance</label>
                    <textarea name="maintenance_message" class="form-control" rows="3" maxlength="500"
                              placeholder="Le site est temporairement en maintenance. Nous revenons bientôt.">{{ old('maintenance_message', $settings['maintenance_message'] ?? '') }}</textarea>
                    <small class="text-muted">Affiché aux visiteurs pendant la maintenance</small>
                    @error('maintenance_message')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- LOGS & DEBUG --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-bug me-2" style="color: #ED5F1E;"></i>Logs & Debug</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-3" style="border-left: 4px solid #ffc107;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>⚠️ Mode debug :</strong> Le mode debug expose des informations sensibles. Réservé au développement uniquement.
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Mode debug (APP_DEBUG)</label>
                    <div class="d-flex align-items-center gap-2">
                        @if(config('app.debug'))
                            <span class="badge bg-danger" style="font-size: 14px;">
                                <i class="fas fa-exclamation-circle me-1"></i>ACTIVÉ
                            </span>
                            <small class="text-danger">Mode debug actif dans .env — ne jamais activer en production</small>
                        @else
                            <span class="badge bg-success" style="font-size: 14px;">
                                <i class="fas fa-check-circle me-1"></i>DÉSACTIVÉ
                            </span>
                            <small class="text-muted">Mode debug désactivé (recommandé en production)</small>
                        @endif
                    </div>
                    <small class="text-muted d-block mt-1">Valeur contrôlée par APP_DEBUG dans le fichier .env</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Niveau de logs <span class="text-danger">*</span></label>
                    <select name="log_level" class="form-select" required>
                        <option value="debug" {{ old('log_level', $settings['log_level'] ?? 'error') === 'debug' ? 'selected' : '' }}>Debug</option>
                        <option value="info" {{ old('log_level', $settings['log_level'] ?? 'error') === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ old('log_level', $settings['log_level'] ?? 'error') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="error" {{ old('log_level', $settings['log_level'] ?? 'error') === 'error' ? 'selected' : '' }}>Error</option>
                    </select>
                    <small class="text-muted">Niveau minimum des logs enregistrés</small>
                    @error('log_level')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Canal de logs <span class="text-danger">*</span></label>
                    <select name="log_channel" class="form-select" required>
                        <option value="single" {{ old('log_channel', $settings['log_channel'] ?? 'daily') === 'single' ? 'selected' : '' }}>Single</option>
                        <option value="daily" {{ old('log_channel', $settings['log_channel'] ?? 'daily') === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="stack" {{ old('log_channel', $settings['log_channel'] ?? 'daily') === 'stack' ? 'selected' : '' }}>Stack</option>
                    </select>
                    <small class="text-muted">Type de stockage des logs</small>
                    @error('log_channel')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Rétention des logs (jours) <span class="text-danger">*</span></label>
                    <input type="number" name="logs_retention_days" class="form-control" min="1" max="365" required
                           value="{{ old('logs_retention_days', $settings['logs_retention_days'] ?? 30) }}">
                    <small class="text-muted">Durée de conservation des fichiers logs</small>
                    @error('logs_retention_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- CACHE & QUEUE --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-database me-2" style="color: #ED5F1E;"></i>Cache & Queue</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Cache Redis</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="cache_enabled" id="cache_enabled" value="1"
                               {{ old('cache_enabled', $settings['cache_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="cache_enabled">Activé</label>
                    </div>
                    <small class="text-muted">Utiliser Redis pour le cache applicatif</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Connexion queue <span class="text-danger">*</span></label>
                    <select name="queue_connection" class="form-select" required>
                        <option value="redis" {{ old('queue_connection', $settings['queue_connection'] ?? 'redis') === 'redis' ? 'selected' : '' }}>Redis</option>
                        <option value="database" {{ old('queue_connection', $settings['queue_connection'] ?? 'redis') === 'database' ? 'selected' : '' }}>Database</option>
                        <option value="sync" {{ old('queue_connection', $settings['queue_connection'] ?? 'redis') === 'sync' ? 'selected' : '' }}>Sync</option>
                    </select>
                    <small class="text-muted">Driver pour les jobs en file d'attente</small>
                    @error('queue_connection')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <div class="d-flex gap-2">
                        <button type="button" id="clear-cache-btn" class="btn btn-outline-primary">
                            <i class="fas fa-broom me-2"></i>Vider le cache
                        </button>
                        <button type="button" id="retry-jobs-btn" class="btn btn-outline-warning">
                            <i class="fas fa-redo me-2"></i>Relancer les jobs échoués
                        </button>
                    </div>
                    <div id="action-result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SAUVEGARDES --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:18px;">
        <div class="card-header" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
            <h5 class="mb-0"><i class="fas fa-hdd me-2" style="color: #ED5F1E;"></i>Sauvegardes</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Sauvegardes automatiques</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="backup_enabled" id="backup_enabled" value="1"
                               {{ old('backup_enabled', $settings['backup_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="backup_enabled">Activées</label>
                    </div>
                    <small class="text-muted">Activer les sauvegardes automatiques programmées</small>
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info mb-0" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        Les sauvegardes sont stockées dans <code>storage/app/backups</code>. Configurer un cron pour l'automatisation.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOUTONS D'ACTION --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-cog me-2"></i>Enregistrer Avancé
        </button>
        <button type="reset" class="btn btn-secondary">
            <i class="fas fa-undo me-2"></i>Réinitialiser
        </button>
        <a href="{{ route('admin.settings.index', 'general') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</form>

{{-- VANILLA JS POUR ACTIONS CACHE/QUEUE --}}
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    const clearCacheBtn = document.getElementById('clear-cache-btn');
    const retryJobsBtn = document.getElementById('retry-jobs-btn');
    const resultDiv = document.getElementById('action-result');

    function showResult(message, isSuccess) {
        resultDiv.innerHTML = `
            <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                <i class="fas fa-${isSuccess ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    clearCacheBtn.addEventListener('click', function() {
        clearCacheBtn.disabled = true;
        clearCacheBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>En cours...';

        fetch('/admin/settings/actions/clear-cache', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            showResult(data.message, data.success);
            clearCacheBtn.disabled = false;
            clearCacheBtn.innerHTML = '<i class="fas fa-broom me-2"></i>Vider le cache';
        })
        .catch(error => {
            showResult('Erreur lors du vidage du cache', false);
            clearCacheBtn.disabled = false;
            clearCacheBtn.innerHTML = '<i class="fas fa-broom me-2"></i>Vider le cache';
        });
    });

    retryJobsBtn.addEventListener('click', function() {
        retryJobsBtn.disabled = true;
        retryJobsBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>En cours...';

        fetch('/admin/settings/actions/retry-jobs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            showResult(data.message, data.success);
            retryJobsBtn.disabled = false;
            retryJobsBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Relancer les jobs échoués';
        })
        .catch(error => {
            showResult('Erreur lors du relancement des jobs', false);
            retryJobsBtn.disabled = false;
            retryJobsBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Relancer les jobs échoués';
        });
    });
});
</script>
