@extends('layouts.internal')

@section('title', 'Sécurité du Compte - RACINE BY GANDA')
@section('page-title', 'Sécurité du Compte')
@section('page-subtitle', 'Gérez vos appareils connectés et paramètres de sécurité')

@section('content')

{{-- SESSIONS ACTIVES --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius: 18px;">
    <div class="card-header py-3" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-laptop me-2" style="color: #ED5F1E;"></i>Appareils connectés
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="color: #160D0C;">Appareil</th>
                        <th style="color: #160D0C;">Navigateur</th>
                        <th style="color: #160D0C;">Adresse IP</th>
                        <th style="color: #160D0C;">Dernière activité</th>
                        <th style="color: #160D0C;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>
                                <i class="fas fa-{{ $session['device'] === 'Mobile' ? 'mobile-alt' : ($session['device'] === 'Tablette' ? 'tablet-alt' : 'laptop') }} me-2" style="color: #ED5F1E;"></i>
                                {{ $session['device'] }}
                                @if($session['is_current'])
                                    <span class="badge bg-success ms-2">Cet appareil</span>
                                @endif
                            </td>
                            <td>{{ $session['browser'] }}</td>
                            <td><code>{{ $session['ip'] }}</code></td>
                            <td>
                                {{ $session['last_active']->diffForHumans() }}
                                <br>
                                <small class="text-muted">{{ $session['last_active']->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                @if($session['is_current'])
                                    <span class="text-muted">Appareil actuel</span>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-danger logout-session-btn" data-session-id="{{ $session['id'] }}">
                                        <i class="fas fa-sign-out-alt me-1"></i>Révoquer
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Aucun appareil connecté trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button type="button" id="logout-all-btn" class="btn btn-danger" style="background: #ED5F1E; border-color: #ED5F1E;">
                <i class="fas fa-sign-out-alt me-2"></i>Déconnecter tous les autres appareils
            </button>
            <div id="logout-result" class="mt-2"></div>
        </div>
    </div>
</div>

{{-- INFORMATIONS SÉCURITÉ --}}
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
    <div class="card-header py-3" style="border-bottom: 2px solid #FFB800; background: none; border-radius: 18px 18px 0 0;">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-shield-alt me-2" style="color: #ED5F1E;"></i>Informations de Sécurité
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold" style="color: #160D0C;">Dernière connexion</label>
                <p class="mb-0">{{ Auth::user()->updated_at->diffForHumans() }}</p>
                <small class="text-muted">{{ Auth::user()->updated_at->format('d/m/Y H:i') }}</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold" style="color: #160D0C;">Sessions actives</label>
                <p class="mb-0">
                    <span class="badge" style="font-size: 16px; background: #ED5F1E; color: white;">{{ count($sessions) }}</span>
                </p>
                <small class="text-muted">Nombre d'appareils connectés sur différents appareils</small>
            </div>

            <div class="col-md-12">
                <hr style="border-color: #FFB800;">
                <p class="mb-2 fw-bold" style="color: #160D0C;">Liens rapides :</p>
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary me-2 mb-2" style="border-color: #ED5F1E; color: #ED5F1E;">
                    <i class="fas fa-key me-1"></i>Changer mon mot de passe
                </a>
                @if(Auth::user()->two_factor_secret)
                    <a href="{{ route('2fa.manage') }}" class="btn btn-outline-secondary mb-2" style="border-color: #160D0C; color: #160D0C;">
                        <i class="fas fa-mobile-alt me-1"></i>Gérer la double vérification
                    </a>
                @else
                    <a href="{{ route('2fa.enable') }}" class="btn btn-outline-success mb-2" style="border-color: #FFB800; color: #160D0C;">
                        <i class="fas fa-shield-alt me-1"></i>Activer la double vérification
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- VANILLA JS POUR DÉCONNEXION --}}
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    const logoutAllBtn = document.getElementById('logout-all-btn');
    const resultDiv = document.getElementById('logout-result');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Déconnecter tous les autres appareils
    logoutAllBtn.addEventListener('click', function() {
        if (!confirm('Êtes-vous sûr de vouloir déconnecter toutes les autres sessions ?')) {
            return;
        }

        logoutAllBtn.disabled = true;
        logoutAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>En cours...';

        fetch('/account/security/logout-others', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const alertClass = data.success ? 'success' : 'danger';
            const iconClass = data.success ? 'check-circle' : 'exclamation-circle';

            // Création sécurisée du DOM sans innerHTML
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${alertClass} alert-dismissible fade show`;
            alertDiv.setAttribute('role', 'alert');

            const icon = document.createElement('i');
            icon.className = `fas fa-${iconClass} me-2`;

            const message = document.createTextNode(data.message);

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');

            alertDiv.appendChild(icon);
            alertDiv.appendChild(message);
            alertDiv.appendChild(closeBtn);

            resultDiv.innerHTML = '';
            resultDiv.appendChild(alertDiv);

            logoutAllBtn.disabled = false;
            logoutAllBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Déconnecter tous les autres appareils';

            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.setAttribute('role', 'alert');

            const icon = document.createElement('i');
            icon.className = 'fas fa-exclamation-circle me-2';

            const message = document.createTextNode('Erreur lors de la déconnexion');

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');

            alertDiv.appendChild(icon);
            alertDiv.appendChild(message);
            alertDiv.appendChild(closeBtn);

            resultDiv.innerHTML = '';
            resultDiv.appendChild(alertDiv);

            logoutAllBtn.disabled = false;
            logoutAllBtn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Déconnecter tous les autres appareils';
        });
    });

    // Déconnecter une session individuelle
    document.querySelectorAll('.logout-session-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sessionId = this.dataset.sessionId;

            if (!confirm('Voulez-vous vraiment déconnecter cette session ?')) {
                return;
            }

            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`/account/security/sessions/${sessionId}/logout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer la ligne du tableau
                    this.closest('tr').remove();

                    // Afficher notification - création sécurisée du DOM
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.setAttribute('role', 'alert');

                    const icon = document.createElement('i');
                    icon.className = 'fas fa-check-circle me-2';

                    const message = document.createTextNode(data.message);

                    const closeBtn = document.createElement('button');
                    closeBtn.type = 'button';
                    closeBtn.className = 'btn-close';
                    closeBtn.setAttribute('data-bs-dismiss', 'alert');

                    alertDiv.appendChild(icon);
                    alertDiv.appendChild(message);
                    alertDiv.appendChild(closeBtn);

                    resultDiv.innerHTML = '';
                    resultDiv.appendChild(alertDiv);
                } else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.setAttribute('role', 'alert');

                    const icon = document.createElement('i');
                    icon.className = 'fas fa-exclamation-circle me-2';

                    const message = document.createTextNode(data.message);

                    const closeBtn = document.createElement('button');
                    closeBtn.type = 'button';
                    closeBtn.className = 'btn-close';
                    closeBtn.setAttribute('data-bs-dismiss', 'alert');

                    alertDiv.appendChild(icon);
                    alertDiv.appendChild(message);
                    alertDiv.appendChild(closeBtn);

                    resultDiv.innerHTML = '';
                    resultDiv.appendChild(alertDiv);

                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.setAttribute('role', 'alert');

                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-circle me-2';

                const message = document.createTextNode('Erreur lors de la déconnexion');

                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn-close';
                closeBtn.setAttribute('data-bs-dismiss', 'alert');

                alertDiv.appendChild(icon);
                alertDiv.appendChild(message);
                alertDiv.appendChild(closeBtn);

                resultDiv.innerHTML = '';
                resultDiv.appendChild(alertDiv);

                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });
});
</script>

@endsection
