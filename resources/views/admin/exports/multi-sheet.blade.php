@extends('layouts.admin-master')

@section('title', 'Export / Import Global - Admin')
@section('page-title', 'Export / Import Global')
@section('page-subtitle', 'Extraction complète + mise à jour batch via Excel')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- EXPORT --}}
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-download text-success"></i>
                <h5 class="mb-0">Export Excel Multi-onglets</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Télécharge un fichier Excel avec 7 onglets contenant toutes les données de la plateforme.</p>

                <table class="table table-sm table-borderless mb-4">
                    <tbody>
                        <tr>
                            <td><span class="badge bg-primary">Commandes</span></td>
                            <td class="text-muted small">ID, numéro, client, statut, total, notes</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-info">Clients</span></td>
                            <td class="text-muted small">ID, nom, email, statut, nb commandes</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark">Produits</span></td>
                            <td class="text-muted small">ID, titre, prix, stock, statut</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success">Créateurs</span></td>
                            <td class="text-muted small">ID, marque, statut, plan, score</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary">Finances</span></td>
                            <td class="text-muted small">Revenus mensuels 12 mois (lecture seule)</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-dark">POS</span></td>
                            <td class="text-muted small">Sessions caisse, totaux (lecture seule)</td>
                        </tr>
                        <tr>
                            <td><span class="badge" style="background:#ED5F1E;">Promos</span></td>
                            <td class="text-muted small">ID, code, type, valeur, actif</td>
                        </tr>
                    </tbody>
                </table>

                <a href="{{ route('admin.export.multi-sheet') }}" class="btn btn-success w-100">
                    <i class="fas fa-file-excel me-2"></i>Télécharger l'export complet (.xlsx)
                </a>
            </div>
        </div>
    </div>

    {{-- IMPORT --}}
    <div class="col-lg-6">
        <div class="card card-racine h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-upload text-warning"></i>
                <h5 class="mb-0">Import — Mise à jour batch</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Réimporte le fichier Excel modifié pour mettre à jour la base de données.
                    Seuls les champs autorisés sont modifiables.
                </p>

                <div class="alert alert-warning small mb-4">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Champs modifiables :</strong>
                    <ul class="mb-0 mt-1">
                        <li><strong>Commandes</strong> : statut, notes_admin</li>
                        <li><strong>Clients</strong> : statut (actif / desactive)</li>
                        <li><strong>Produits</strong> : prix, stock, statut</li>
                        <li><strong>Créateurs</strong> : statut, verifie (oui / non)</li>
                        <li><strong>Promos</strong> : actif (oui / non), max_utilisations</li>
                    </ul>
                    <div class="mt-1">Onglets <strong>Finances</strong> et <strong>POS</strong> ignorés à l'import.</div>
                </div>

                <form action="{{ route('admin.export.multi-sheet.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="import-file" class="form-label">Fichier Excel (.xlsx)</label>
                        <input type="file"
                               id="import-file"
                               name="file"
                               class="form-control @error('file') is-invalid @enderror"
                               accept=".xlsx,.xls"
                               required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit"
                            class="btn btn-warning w-100"
                            onclick="return confirm('Confirmer la mise à jour batch ? Cette action modifie des données en base.')">
                        <i class="fas fa-sync-alt me-2"></i>Importer et mettre à jour
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
