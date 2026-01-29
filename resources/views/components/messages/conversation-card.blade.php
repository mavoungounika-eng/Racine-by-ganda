@props(['conversation', 'currentUserId'])

@php
    $otherParticipant = $conversation->participants->where('user_id', '!=', $currentUserId)->first();
    $name = $otherParticipant?->user?->name ?? 'Système';
    $unreadCount = $conversation->getUnreadCountForUser($currentUserId);
   $typeClass = match($conversation->type) {
        'order_thread' => 'bg-info',
        'product_thread' => 'bg-success',
        default => 'bg-racine-orange'
    };
@endphp

<a href="{{ route('messages.show', $conversation) }}" 
   class="conversation-item d-block text-decoration-none {{ $unreadCount > 0 ? 'unread' : '' }}">
    
    <div class="d-flex align-items-start p-3">
        {{-- Avatar --}}
        <div class="conversation-avatar me-3">
            @if($conversation->type === 'order_thread')
                <div class="avatar-icon bg-info">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            @elseif($conversation->type === 'product_thread')
                <div class="avatar-icon bg-success">
                    <i class="fas fa-box"></i>
                </div>
            @else
                <div class="avatar-icon bg-racine-orange">
                    {{ strtoupper(substr($name, 0, 1)) }}
                </div>
            @endif
            @if($unreadCount > 0)
                <span class="unread-indicator"></span>
            @endif
        </div>
        
        {{-- Content --}}
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="mb-0 text-racine-black fw-semibold text-truncate">
                    @if($conversation->type === 'order_thread')
                        Commande #{{ $conversation->order->order_number ?? 'N/A' }}
                    @elseif($conversation->type === 'product_thread')
                        {{ Str::limit($conversation->product->title ?? 'Produit', 25) }}
                    @else
                        {{ $name }}
                    @endif
                </h6>
                @if($conversation->lastMessage)
                    <small class="text-muted ms-2 flex-shrink-0">
                        {{ $conversation->last_message_at?->diffForHumans() }}
                    </small>
                @endif
            </div>
            
            <p class="text-sm text-gray-600 mb-2 truncate">{{ $conversation->subject }}</p>
            
            <p class="mb-0 text-muted small text-truncate">
                {{ $conversation->lastMessage->first()?->content ?? 'Aucun message' }}
            </p>
            
            {{-- Badges --}}
            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                @if($unreadCount > 0)
                    <span class="badge bg-racine-orange">{{ $unreadCount }} non lu{{ $unreadCount > 1 ? 's' : '' }}</span>
                @endif
                @if($conversation->type === 'order_thread')
                    <span class="badge bg-info-subtle text-info">
                        <i class="fas fa-shopping-bag me-1"></i> Commande
                    </span>
                @elseif($conversation->type === 'product_thread')
                    <span class="badge bg-success-subtle text-success">
                        <i class="fas fa-box me-1"></i> Produit
                    </span>
                @else
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="fas fa-user me-1"></i> Direct
                    </span>
                @endif
            </div>
        </div>
        
        @if($unreadCount > 0)
            <div class="w-3 h-3 bg-[#ED5F1E] rounded-full mt-2"></div>
        @endif
    </div>
</a>
