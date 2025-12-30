@extends('layouts.frontend')

@section('title', 'Mes Notifications - RACINE BY GANDA')

@push('styles')
<style>
    .notifications-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 3rem 0;
        margin-top: -70px;
        padding-top: calc(3rem + 70px);
    }
    
    .notifications-content {
        padding: 3rem 0;
        background: #F8F6F3;
        min-height: 60vh;
    }
    
    .notifications-filters {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .filter-tabs {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .filter-tab {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .filter-tab.active {
        background: #ED5F1E;
        color: white;
        border-color: #ED5F1E;
    }
    
    .filter-tab:not(.active) {
        background: #f8f9fa;
        color: #6c757d;
    }
    
    .filter-tab:not(.active):hover {
        background: #e9ecef;
        color: #2C1810;
    }
    
    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .notification-item {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        border-left: 4px solid transparent;
        position: relative;
    }
    
    .notification-item.unread {
        border-left-color: #ED5F1E;
        background: linear-gradient(to right, rgba(237, 95, 30, 0.05), white);
    }
    
    .notification-item:hover {
        transform: translateX(4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .notification-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .notification-icon.info { background: rgba(14, 165, 233, 0.1); }
    .notification-icon.success { background: rgba(34, 197, 94, 0.1); }
    .notification-icon.warning { background: rgba(255, 184, 0, 0.1); }
    .notification-icon.danger { background: rgba(220, 38, 38, 0.1); }
    .notification-icon.order { background: rgba(237, 95, 30, 0.1); }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .notification-message {
        color: #6c757d;
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }
    
    .notification-time {
        font-size: 0.85rem;
        color: #8B7355;
    }
    
    .notification-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    
    .notification-action-btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: none;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-mark-read {
        background: rgba(237, 95, 30, 0.1);
        color: #ED5F1E;
    }
    
    .btn-mark-read:hover {
        background: #ED5F1E;
        color: white;
    }
    
    .btn-delete {
        background: rgba(220, 38, 38, 0.1);
        color: #DC2626;
    }
    
    .btn-delete:hover {
        background: #DC2626;
        color: white;
    }
    
    .empty-notifications {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .empty-notifications-icon {
        font-size: 5rem;
        color: #ddd;
        margin-bottom: 1.5rem;
    }
    
    .empty-notifications-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
        font-family: 'Cormorant Garamond', serif;
    }
    
    .empty-notifications-text {
        color: #8B7355;
    }
    
    .notifications-actions-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .btn-mark-all-read {
        background: rgba(237, 95, 30, 0.1);
        color: #ED5F1E;
        border: 1px solid #ED5F1E;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    
    .btn-mark-all-read:hover {
        background: #ED5F1E;
        color: white;
    }
</style>
@endpush

@section('content')
<!-- HERO SECTION -->
<section class="notifications-hero">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h1 style="font-size: 2.5rem; color: white; margin-bottom: 0.5rem; font-family: 'Cormorant Garamond', serif;">
                    <i class="fas fa-bell me-3"></i>Mes Notifications
                </h1>
                <p style="font-size: 1.1rem; color: rgba(255, 255, 255, 0.8); margin: 0;">
                    Restez informé de toutes vos activités
                </p>
            </div>
            @if($unreadCount > 0)
            <div style="background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1.5rem; border-radius: 12px;">
                <span style="font-size: 1.1rem; font-weight: 600;">
                    {{ $unreadCount }} non lue(s)
                </span>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- NOTIFICATIONS CONTENT -->
<section class="notifications-content">
    <div class="container">
        <!-- FILTRES -->
        <div class="notifications-filters">
            <div class="filter-tabs">
                <a href="{{ route('notifications.index') }}" 
                   class="filter-tab {{ $filter === 'all' ? 'active' : '' }}">
                    <i class="fas fa-list me-2"></i>Toutes
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
                   class="filter-tab {{ $filter === 'unread' ? 'active' : '' }}">
                    <i class="fas fa-envelope me-2"></i>Non lues
                    @if($filter === 'unread' && $unreadCount > 0)
                    <span style="background: white; color: #ED5F1E; padding: 0.25rem 0.5rem; border-radius: 8px; margin-left: 0.5rem; font-size: 0.85rem;">
                        {{ $unreadCount }}
                    </span>
                    @endif
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
                   class="filter-tab {{ $filter === 'read' ? 'active' : '' }}">
                    <i class="fas fa-envelope-open me-2"></i>Lues
                </a>
            </div>
        </div>
        
        <!-- ACTIONS GLOBALES -->
        @if($notifications->count() > 0)
        <div class="notifications-actions-top">
            <div>
                <strong style="color: #2C1810;">{{ $notifications->total() }} notification(s)</strong>
            </div>
            @if($unreadCount > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-mark-all-read">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
            </form>
            @endif
        </div>
        @endif
        
        <!-- LISTE NOTIFICATIONS -->
        @if($notifications->count() > 0)
            <div class="notifications-list">
                @foreach($notifications as $notification)
                <div class="notification-item {{ !$notification->is_read ? 'unread' : '' }}">
                    <div class="d-flex">
                        <div class="notification-icon {{ $notification->type ?? 'info' }}">
                            {{ $notification->display_icon ?? '🔔' }}
                        </div>
                        <div class="notification-content">
                            <div class="notification-header">
                                <div>
                                    <h3 class="notification-title">{{ $notification->title }}</h3>
                                    <p class="notification-message">{{ $notification->message }}</p>
                                    <div class="notification-time">
                                        <i class="far fa-clock me-1"></i>
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                @if(!$notification->is_read)
                                <span class="badge" style="background: #ED5F1E; color: white; padding: 0.25rem 0.75rem; border-radius: 8px; font-size: 0.75rem;">
                                    Nouveau
                                </span>
                                @endif
                            </div>
                            
                            @if($notification->action_url)
                            <div class="notification-actions">
                                <a href="{{ $notification->action_url }}" class="notification-action-btn" style="background: rgba(237, 95, 30, 0.1); color: #ED5F1E;">
                                    <i class="fas fa-arrow-right"></i>
                                    {{ $notification->action_text ?? 'Voir' }}
                                </a>
                            </div>
                            @endif
                            
                            <div class="notification-actions">
                                @if(!$notification->is_read)
                                <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="d-inline mark-read-form">
                                    @csrf
                                    <button type="submit" class="notification-action-btn btn-mark-read">
                                        <i class="fas fa-check"></i> Marquer comme lu
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="d-inline delete-notification-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="notification-action-btn btn-delete">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- PAGINATION -->
            @if($notifications->hasPages())
            <div class="mt-5">
                {{ $notifications->links('pagination::bootstrap-4') }}
            </div>
            @endif
        @else
            <div class="empty-notifications">
                <div class="empty-notifications-icon">
                    <i class="far fa-bell-slash"></i>
                </div>
                <h2 class="empty-notifications-title">Aucune notification</h2>
                <p class="empty-notifications-text">
                    @if($filter === 'unread')
                        Vous n'avez aucune notification non lue.
                    @elseif($filter === 'read')
                        Vous n'avez aucune notification lue.
                    @else
                        Vous n'avez aucune notification pour le moment.
                    @endif
                </p>
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
    // AJAX pour marquer comme lu
    document.querySelectorAll('.mark-read-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const item = this.closest('.notification-item');
                    item.classList.remove('unread');
                    this.remove();
                    
                    // Mettre à jour le compteur
                    const unreadBadge = item.querySelector('.badge');
                    if (unreadBadge) {
                        unreadBadge.remove();
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // AJAX pour supprimer
    document.querySelectorAll('.delete-notification-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
                return;
            }
            
            fetch(this.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const item = this.closest('.notification-item');
                    item.style.transition = 'opacity 0.3s, transform 0.3s';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.9)';
                    
                    setTimeout(() => {
                        item.remove();
                        
                        // Si plus de notifications, recharger
                        if (document.querySelectorAll('.notification-item').length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
</script>
@endpush

@include('components.navigation-breadcrumb', [
    'items' => [
        ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
        ['label' => 'Mes Notifications', 'url' => null],
    ],
    'backUrl' => route('account.dashboard'),
    'backText' => 'Retour au tableau de bord',
    'position' => 'bottom',
])
@endsection

