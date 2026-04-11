@extends('layouts.creator')

@section('title', 'Mes Notifications - RACINE BY GANDA')
@section('page-title', 'Mes Notifications')

@push('styles')
<style>
    .notification-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        border: 1px solid #F0EBE5;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .notification-unread {
        border-left: 5px solid var(--racine-orange);
        background: linear-gradient(90deg, #FFF7ED 0%, #FFFFFF 100%);
        border-color: var(--racine-orange);
    }
    
    .notification-unread:hover {
        border-left-width: 8px;
    }

    .notification-icon-box {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        box-shadow: var(--shadow-sm);
    }

    .icon-unread {
        background: linear-gradient(135deg, var(--racine-orange) 0%, var(--racine-yellow) 100%);
        color: white;
    }

    .icon-read {
        background: #F8F6F3;
        color: #8B7355;
        border: 1px solid #E5DDD3;
    }

    .notification-title {
        color: var(--racine-black) !important;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .notification-message {
        color: var(--racine-black-soft) !important;
        line-height: 1.6;
        margin-bottom: 0.75rem;
    }

    .notification-time {
        color: #8B7355;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .unread-badge {
        background: var(--racine-orange);
        color: white;
        font-size: 0.7rem;
        padding: 2px 10px;
        border-radius: 10px;
        text-transform: uppercase;
        font-weight: 800;
        margin-left: 10px;
    }

    .filter-btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 0.6rem 1.25rem;
        transition: all 0.3s;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            
            {{-- Navigation (Optionnelle, mais on garde la cohérence) --}}
            @include('creator.partials.settings-nav')

            {{-- En-tête avec filtres --}}
            <div class="creator-card mb-4 shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h2 class="h3 font-weight-bold text-dark mb-1" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fas fa-bell text-warning mr-2"></i>
                            Mes Notifications
                        </h2>
                        <p class="text-muted mb-0 font-weight-bold">
                            @if($unreadCount > 0)
                                <span class="text-orange">{{ $unreadCount }}</span> notification{{ $unreadCount > 1 ? 's' : '' }} non lue{{ $unreadCount > 1 ? 's' : '' }}
                            @else
                                <span class="text-success">✓</span> Toutes les notifications sont lues
                            @endif
                        </p>
                    </div>
                    
                    <div class="d-flex align-items-center mt-3 mt-md-0">
                        <a href="{{ route('creator.notifications.index', ['filter' => request('filter') === 'unread' ? null : 'unread']) }}" 
                           class="btn {{ request('filter') === 'unread' ? 'btn-outline-dark' : 'btn-light border' }} filter-btn mr-2">
                            @if(request('filter') === 'unread')
                                <i class="fas fa-eye mr-1"></i> Voir toutes
                            @else
                                <i class="fas fa-filter mr-1"></i> Non lues
                            @endif
                        </a>
                        
                        @if($unreadCount > 0)
                        <form action="{{ route('creator.notifications.markAllAsRead') }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success filter-btn shadow-sm">
                                <i class="fas fa-check-double mr-1"></i> Tout marquer lu
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Liste des notifications --}}
            <div class="notifications-list">
                @forelse($notifications as $notification)
                <div class="notification-card {{ !$notification->is_read ? 'notification-unread' : '' }}">
                    <div class="d-flex align-items-start">
                        {{-- Icône --}}
                        <div class="notification-icon-box {{ !$notification->is_read ? 'icon-unread' : 'icon-read' }} mr-4">
                            <span>{{ $notification->display_icon }}</span>
                        </div>
                        
                        {{-- Contenu --}}
                        <div class="flex-grow-1 mr-3">
                            <h4 class="h5 notification-title mt-1">
                                {{ $notification->title }}
                                @if(!$notification->is_read)
                                    <span class="unread-badge">Nouveau</span>
                                @endif
                            </h4>
                            <p class="notification-message">
                                {{ $notification->message }}
                            </p>
                            <div class="notification-time">
                                <i class="far fa-clock mr-1"></i>
                                {{ $notification->created_at->diffForHumans() }}
                            </div>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="d-flex flex-column align-items-end">
                            @if($notification->action_url)
                                <a href="{{ $notification->action_url }}" class="btn btn-sm btn-orange font-weight-bold px-4 rounded-pill mb-2 shadow-sm">
                                    {{ $notification->action_text ?? 'Voir' }}
                                </a>
                            @endif
                            
                            @if(!$notification->is_read)
                            <form action="{{ route('creator.notifications.markAsRead', $notification) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-light border rounded-pill font-weight-bold" title="Marquer comme lu">
                                    <i class="fas fa-check text-success"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="creator-card text-center py-5 border-dashed">
                    <div class="mb-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                            <i class="fas fa-bell-slash text-muted fa-3x"></i>
                        </div>
                    </div>
                    <h4 class="h4 font-weight-bold text-dark mb-2">
                        @if(request('filter') === 'unread')
                            Aucune notification non lue
                        @else
                            Aucune notification
                        @endif
                    </h4>
                    <p class="text-muted">Vous serez notifié ici des événements importants de votre boutique.</p>
                </div>
                @endforelse
            </div>
            
            {{-- Pagination --}}
            @if($notifications->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $notifications->links() }}
            </div>
            @endif

            {{-- Back Button --}}
            <div class="text-center mt-5">
                <a href="{{ route('creator.dashboard') }}" class="btn btn-link text-muted font-weight-bold text-decoration-none">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

