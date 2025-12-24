@extends('layouts.creator')

@section('title', 'Mes Messages - RACINE BY GANDA')
@section('page-title', 'Messagerie Client')

@push('styles')
<style>
    .message-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #E5DDD3;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .message-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        border-color: #D4A574;
    }
    
    .message-card.unread {
        border-left: 4px solid #ED5F1E;
        background: #FFFAF8;
    }

    .avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        font-size: 1.1rem;
    }

    .badge-order {
        background: #E0F2F1;
        color: #00695C;
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-product {
        background: #FFF3E0;
        color: #E65100;
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#2C1810]">Boîte de réception</h2>
        
        <div class="flex gap-2">
            <a href="{{ route('creator.messages.index') }}" class="px-4 py-2 rounded-lg {{ request('filter') != 'unread' ? 'bg-[#2C1810] text-white' : 'bg-white text-[#2C1810] border border-[#E5DDD3]' }}">
                Tous
            </a>
            <a href="{{ route('creator.messages.index', ['filter' => 'unread']) }}" class="px-4 py-2 rounded-lg {{ request('filter') == 'unread' ? 'bg-[#2C1810] text-white' : 'bg-white text-[#2C1810] border border-[#E5DDD3]' }}">
                Non lus
            </a>
        </div>
    </div>

    @if($conversations->count() > 0)
        <div class="space-y-4">
            @foreach($conversations as $conversation)
                <a href="{{ route('creator.messages.show', $conversation) }}" class="message-card block p-6 {{ $conversation->getUnreadCountForUser(Auth::id()) > 0 ? 'unread' : '' }}">
                    <div class="flex items-start gap-4">
                        {{-- Avatar --}}
                        @php
                            $otherParticipant = $conversation->participants->where('user_id', '!=', Auth::id())->first();
                            $name = $otherParticipant ? ($otherParticipant->user->name ?? 'Utilisateur') : 'Système';
                            $initial = strtoupper(substr($name, 0, 1));
                            $color = $conversation->type == 'order_thread' ? 'bg-teal-600' : 'bg-orange-500';
                        @endphp
                        <div class="avatar-circle {{ $color }} flex-shrink-0">
                            {{ $initial }}
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-bold text-[#2C1810] truncate">{{ $name }}</h3>
                                <span class="text-xs text-gray-500 whitespace-nowrap">{{ $conversation->last_message_at->diffForHumans() }}</span>
                            </div>
                            
                            <div class="flex items-center gap-2 mb-2">
                                @if($conversation->type == 'order_thread')
                                    <span class="badge-order"><i class="fas fa-shopping-bag mr-1"></i> Commande #{{ $conversation->order->order_number ?? 'N/A' }}</span>
                                @elseif($conversation->type == 'product_thread')
                                    <span class="badge-product"><i class="fas fa-box mr-1"></i> Produit</span>
                                @endif
                                <span class="text-sm font-medium text-gray-700 truncate">{{ $conversation->subject }}</span>
                            </div>
                            
                            <p class="text-gray-600 text-sm truncate">
                                {{ $conversation->lastMessage->first()->content ?? 'Aucun message' }}
                            </p>
                        </div>
                        
                        @if($conversation->getUnreadCountForUser(Auth::id()) > 0)
                            <div class="bg-[#ED5F1E] w-3 h-3 rounded-full mt-2"></div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        
        {{-- Pagination si nécessaire --}}
        {{-- {{ $conversations->links() }} --}}
    @else
        <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-[#E5DDD3]">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <i class="far fa-envelope text-3xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-[#2C1810]">Aucun message pour le moment</h3>
            <p class="text-[#8B7355] mt-2 max-w-md mx-auto">Les messages apparaîtront ici lorsque des clients vous contacteront à propos de vos produits ou commandes.</p>
        </div>
    @endif
</div>
@endsection
