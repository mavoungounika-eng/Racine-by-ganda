@extends('layouts.creator')

@section('title', 'Conversation - RACINE BY GANDA')
@section('page-title', 'Conversation')

@push('styles')
<style>
    .chat-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #E5DDD3;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 200px);
        min-height: 500px;
    }

    .chat-header {
        padding: 1.25rem;
        background: #F8F6F3;
        border-bottom: 1px solid #E5DDD3;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background-color: #ffffff;
        background-image: radial-gradient(#E5DDD3 1px, transparent 1px);
        background-size: 20px 20px;
    }

    .message-bubble {
        max-width: 75%;
        padding: 1rem;
        border-radius: 16px;
        margin-bottom: 1rem;
        position: relative;
    }

    .message-sent {
        background: linear-gradient(135deg, #2C1810 0%, #4A2C21 100%);
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }

    .message-received {
        background: #F3F4F6;
        color: #1F2937;
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }

    .chat-input-area {
        padding: 1.25rem;
        background: white;
        border-top: 1px solid #E5DDD3;
    }

    .message-meta {
        font-size: 0.7rem;
        margin-top: 0.5rem;
        opacity: 0.7;
        text-align: right;
    }
    
    .context-card {
        background: white;
        border: 1px solid #E5DDD3;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .context-image {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-4 py-4">
    
    <div class="mb-4">
        <a href="{{ route('creator.messages.index') }}" class="text-[#ED5F1E] hover:underline flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Retour aux messages
        </a>
    </div>

    <div class="chat-container">
        {{-- Header --}}
        <div class="chat-header">
            <div class="flex items-center gap-3">
                @php
                    $otherParticipant = $conversation->participants->where('user_id', '!=', Auth::id())->first();
                    $name = $otherParticipant ? ($otherParticipant->user->name ?? 'Utilisateur') : 'Système';
                    $initial = strtoupper(substr($name, 0, 1));
                    $color = $conversation->type == 'order_thread' ? 'bg-teal-600' : 'bg-orange-500';
                @endphp
                <div class="w-10 h-10 rounded-full {{ $color }} flex items-center justify-center text-white font-bold">
                    {{ $initial }}
                </div>
                <div>
                    <h3 class="font-bold text-[#2C1810]">{{ $name }}</h3>
                    <p class="text-xs text-gray-500">{{ $conversation->subject }}</p>
                </div>
            </div>
            
            <div>
                @if($conversation->type == 'order_thread' && $conversation->related_order_id)
                    <a href="{{ route('creator.orders.show', $conversation->related_order_id) }}" class="text-sm bg-teal-50 text-teal-700 px-3 py-1 rounded-full hover:bg-teal-100 transition">
                        <i class="fas fa-eye mr-1"></i> Voir commande
                    </a>
                @elseif($conversation->type == 'product_thread' && $conversation->related_product_id)
                     <a href="{{ route('creator.products.edit', $conversation->related_product_id) }}" class="text-sm bg-orange-50 text-orange-700 px-3 py-1 rounded-full hover:bg-orange-100 transition">
                        <i class="fas fa-eye mr-1"></i> Voir produit
                    </a>
                @endif
            </div>
        </div>

        {{-- Messages List --}}
        <div class="chat-messages" id="messagesList">
            {{-- Context Alert if Order or Product --}}
            @if($conversation->type == 'order_thread' && $conversation->order)
                <div class="flex justify-center mb-4">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                        Discussion liée à la commande #{{ $conversation->order->order_number }}
                    </span>
                </div>
            @endif

            @foreach($messages as $msg)
                <div class="message-bubble {{ $msg->user_id == Auth::id() ? 'message-sent' : 'message-received' }}">
                    <div class="message-content">
                        {{ $msg->content }}
                    </div>
                    
                    @if($msg->attachments->count() > 0)
                        <div class="mt-2 space-y-1">
                            @foreach($msg->attachments as $att)
                                <a href="{{ Storage::url($att->file_path) }}" target="_blank" class="flex items-center gap-2 text-xs opacity-90 hover:underline bg-black/10 p-2 rounded">
                                    <i class="fas fa-paperclip"></i> {{ $att->original_name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="message-meta">
                        {{ $msg->created_at->format('H:i') }}
                        @if($msg->user_id == Auth::id())
                            <i class="fas fa-check-double ml-1 {{ $msg->read_by ? 'text-blue-300' : 'text-gray-400' }}"></i>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Input Area --}}
        <div class="chat-input-area">
            <form action="{{ route('creator.messages.store', $conversation) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="flex gap-4">
                    {{-- Attachment Button --}}
                    <label class="cursor-pointer text-gray-400 hover:text-[#ED5F1E] transition p-2">
                        <i class="fas fa-paperclip text-xl"></i>
                        <input type="file" name="attachments[]" class="hidden" multiple>
                    </label>

                    {{-- Text & Submit --}}
                    <div class="flex-1 relative">
                        <textarea name="content" rows="1" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#ED5F1E] resize-none" placeholder="Écrivez votre message..." required></textarea>
                        <button type="submit" class="absolute right-2 top-2 bg-[#2C1810] text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#ED5F1E] transition">
                            <i class="fas fa-paper-plane text-xs"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Scroll to bottom on load
    const messagesList = document.getElementById('messagesList');
    messagesList.scrollTop = messagesList.scrollHeight;
</script>
@endpush
@endsection
