@extends('layouts.admin-master')

@section('title', 'Événements CMS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">📅 Événements CMS</h1>
            <p class="text-muted mb-0">Gérez les événements de votre site</p>
        </div>
        <a href="{{ route('cms.admin.events.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel événement
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Date début</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>{{ $event->id }}</td>
                                <td>
                                    <strong>{{ $event->title }}</strong>
                                    @if($event->description)
                                        <br><small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $event->type_label }}</span>
                                </td>
                                <td>
                                    <small>{{ $event->start_date->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>{{ $event->location ?? '-' }}</td>
                                <td>
                                    @if($event->status === 'upcoming')
                                        <span class="badge bg-primary">À venir</span>
                                    @elseif($event->status === 'ongoing')
                                        <span class="badge bg-success">En cours</span>
                                    @elseif($event->status === 'completed')
                                        <span class="badge bg-secondary">Terminé</span>
                                    @else
                                        <span class="badge bg-danger">Annulé</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cms.admin.events.edit', $event) }}" 
                                           class="btn btn-outline-primary" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('cms.admin.events.destroy', $event) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-calendar-alt fa-3x mb-3 d-block"></i>
                                    Aucun événement créé. <a href="{{ route('cms.admin.events.create') }}">Créer le premier</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="mt-3">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

