@extends('layouts.admin')

@section('title', 'Gestion des Créateurs - RACINE BY GANDA')
@section('page_title', 'Créateurs')
@section('page_subtitle', 'Gérer les créateurs partenaires')
@section('breadcrumb', 'Créateurs')

@section('content')

<div class="card-racine">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 2px solid var(--racine-beige);">
        <h5 class="mb-0">Liste des créateurs ({{ $creators->total() }})</h5>
    </div>
    <div class="card-body p-0">
        @if($creators->count())
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="small text-muted">
                    <tr>
                        <th>Créateur</th>
                        <th>Email</th>
                        <th>Produits</th>
                        <th>Statut</th>
                        <th>Inscription</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($creators as $creator)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($creator->logo_path)
                                        <img src="{{ asset('storage/' . $creator->logo_path) }}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold">{{ $creator->brand_name }}</div>
                                        <div class="small text-muted">{{ $creator->user->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $creator->user->email }}</td>
                            <td>{{ $creator->products_count }}</td>
                            <td>
                                @if($creator->is_verified)
                                    <span class="badge bg-success">Vérifié</span>
                                @else
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @endif
                                @if($creator->is_active)
                                    <span class="badge bg-primary">Actif</span>
                                @else
                                    <span class="badge-racine-orange" style="font-size: 0.75rem;">Inactif</span>
                                @endif
                            </td>
                            <td>{{ $creator->created_at->format('d/m/Y') }}</td>
                            <td>
                                <form action="{{ route('admin.creators.verify', $creator->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $creator->is_verified ? 'btn-warning' : 'btn-success' }}">
                                        <i class="fas fa-{{ $creator->is_verified ? 'times' : 'check' }}"></i>
                                        {{ $creator->is_verified ? 'Retirer' : 'Vérifier' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-5 text-center text-muted">
                <i class="fas fa-user-ninja fa-3x mb-3"></i>
                <p>Aucun créateur enregistré.</p>
            </div>
        @endif
    </div>
    @if($creators->hasPages())
        <div class="card-footer bg-white">
            {{ $creators->links() }}
        </div>
    @endif
</div>

@endsection
