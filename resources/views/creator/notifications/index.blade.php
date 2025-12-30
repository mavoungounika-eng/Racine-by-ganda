@extends('layouts.creator')

@section('title', 'Mes Notifications - RACINE BY GANDA')
@section('page-title', 'Mes Notifications')

@push('styles')
<style>
    .premium-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(212, 165, 116, 0.1);
        transition: all 0.3s ease;
    }
    
    .notification-item {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        border: 2px solid #E5DDD3;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }
    
    .notification-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        border-color: rgba(212, 165, 116, 0.3);
    }
    
    .notification-unread {
        border-left: 4px solid #ED5F1E;
        background: linear-gradient(90deg, rgba(237, 95, 30, 0.05) 0%, white 100%);
        border-color: rgba(237, 95, 30, 0.2);
    }
    
    .premium-btn {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.4);
        color: white;
    }
    
    .premium-btn-secondary {
        background: white;
        color: #2C1810;
        border: 2px solid #E5DDD3;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .premium-btn-secondary:hover {
        background: #F8F6F3;
        border-color: #D4A574;
        color: #2C1810;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- En-tête avec filtres --}}
    <div class="premium-card mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-2xl font-bold text-[#2C1810] mb-2" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fas fa-bell text-[#ED5F1E] mr-2"></i>
                    Notifications
                </h3>
                <p class="text-sm text-[#8B7355]">
                    @if($unreadCount > 0)
                        <span class="font-semibold text-[#ED5F1E]">{{ $unreadCount }}</span> notification{{ $unreadCount > 1 ? 's' : '' }} non lue{{ $unreadCount > 1 ? 's' : '' }}
                    @else
                        <span class="text-[#22C55E] font-semibold">✓</span> Toutes les notifications sont lues
                    @endif
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('creator.notifications.index', ['filter' => request('filter') === 'unread' ? null : 'unread']) }}" 
                   class="premium-btn-secondary">
                    @if(request('filter') === 'unread')
                        <i class="fas fa-eye"></i>
                        Voir toutes
                    @else
                        <i class="fas fa-filter"></i>
                        Non lues uniquement
                    @endif
                </a>
                
                @if($unreadCount > 0)
                <form action="{{ route('creator.notifications.markAllAsRead') }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="premium-btn" style="background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%); box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);">
                        <i class="fas fa-check-double"></i>
                        Tout marquer comme lu
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Liste des notifications --}}
    <div>
        @forelse($notifications as $notification)
        <div class="notification-item {{ !$notification->is_read ? 'notification-unread' : '' }}">
            <div class="flex items-start gap-4">
                {{-- Icône --}}
                <div class="flex-shrink-0">
                    <div class="h-14 w-14 rounded-2xl {{ !$notification->is_read ? 'bg-gradient-to-br from-[#ED5F1E] to-[#FFB800]' : 'bg-gradient-to-br from-[#8B7355] to-[#64748B]' }} flex items-center justify-center shadow-lg">
                        <span class="text-2xl text-white">{{ $notification->display_icon }}</span>
                    </div>
                </div>
                
                {{-- Contenu --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <h4 class="font-bold text-[#2C1810] mb-2 text-lg">
                                {{ $notification->title }}
                                @if(!$notification->is_read)
                                    <span class="ml-2 px-2.5 py-1 bg-gradient-to-r from-[#ED5F1E] to-[#FFB800] text-white text-xs rounded-full font-semibold">Nouveau</span>
                                @endif
                            </h4>
                            <p class="text-[#8B7355] mb-3 leading-relaxed">{{ $notification->message }}</p>
                            <p class="text-xs text-[#8B7355] font-medium">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($notification->action_url)
                                <a href="{{ $notification->action_url }}" 
                                   class="premium-btn" style="padding: 0.625rem 1.25rem; font-size: 0.875rem;">
                                    {{ $notification->action_text ?? 'Voir' }}
                                </a>
                            @endif
                            
                            @if(!$notification->is_read)
                            <form action="{{ route('creator.notifications.markAsRead', $notification) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="premium-btn-secondary"
                                        style="padding: 0.625rem; min-width: 40px;"
                                        title="Marquer comme lu">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="premium-card text-center py-16">
            <div class="flex flex-col items-center gap-4">
                <div class="h-24 w-24 rounded-full bg-gradient-to-br from-[#F8F6F3] to-[#E5DDD3] flex items-center justify-center">
                    <i class="fas fa-bell-slash text-4xl text-[#8B7355]"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-[#2C1810] mb-2">
                        @if(request('filter') === 'unread')
                            Aucune notification non lue
                        @else
                            Aucune notification
                        @endif
                    </p>
                    <p class="text-[#8B7355]">Vous serez notifié lorsque de nouveaux événements se produiront</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>
    
    {{-- Pagination --}}
    @if($notifications->hasPages())
    <div class="premium-card mt-8">
        <div class="flex justify-center">
            {{ $notifications->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
