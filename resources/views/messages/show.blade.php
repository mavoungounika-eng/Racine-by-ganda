@extends('layouts.frontend')

@section('title', 'Conversation - RACINE BY GANDA')
@section('page-title', 'Conversation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/messages-enhanced.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    {{-- Header avec breadcrumb et actions --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-transparent p-0" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.home') }}" class="text-muted text-decoration-none">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('profile.index') }}" class="text-muted text-decoration-none">
                            <i class="fas fa-user me-1"></i>Profil
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('messages.index') }}" class="text-muted text-decoration-none">
                            <i class="fas fa-comments me-1"></i>Messagerie
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        @if($conversation->subject)
                            {{ Str::limit($conversation->subject, 30) }}
                        @else
                            Conversation
                        @endif
                    </li>
                </ol>
            </nav>
            <h1 class="mb-0 fw-bold text-racine-black" style="font-size: 1.75rem;">
                @if($conversation->type === 'order_thread')
                    <i class="fas fa-shopping-bag text-info me-2"></i>
                    Commande #{{ $conversation->order->order_number ?? 'N/A' }}
                @elseif($conversation->type === 'product_thread')
                    <i class="fas fa-box text-success me-2"></i>
                    {{ $conversation->product->title ?? 'Produit' }}
                @else
                    @php
                        $otherParticipant = $conversation->participants->where('user_id', '!=', auth()->id())->first();
                    @endphp
                    <i class="fas fa-user text-racine-orange me-2"></i>
                    {{ $otherParticipant->user->name ?? 'Utilisateur' }}
                @endif
            </h1>
            @if($conversation->subject)
                <p class="text-muted mb-0 small">
                    <i class="fas fa-tag me-1"></i>{{ $conversation->subject }}
                </p>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            @php
                $user = auth()->user();
                $dashboardRoute = 'account.dashboard';
                $dashboardLabel = 'Tableau de bord';
                
                if ($user->isAdmin()) {
                    $dashboardRoute = 'admin.dashboard';
                    $dashboardLabel = 'Dashboard Admin';
                } elseif ($user->isCreator()) {
                    $dashboardRoute = 'creator.dashboard';
                    $dashboardLabel = 'Dashboard Créateur';
                }
            @endphp
            <a href="{{ route($dashboardRoute) }}" class="btn btn-racine-orange btn-sm" title="Retour au tableau de bord">
                <i class="fas fa-tachometer-alt me-1"></i>
                <span class="d-none d-md-inline">{{ $dashboardLabel }}</span>
                <span class="d-md-none">Dashboard</span>
            </a>
            <a href="{{ route('messages.index') }}" class="btn btn-outline-racine-orange btn-sm" title="Retour aux conversations">
                <i class="fas fa-arrow-left me-1"></i>
                <span class="d-none d-md-inline">Retour</span>
            </a>
            <a href="{{ route('profile.index') }}" class="btn btn-outline-racine-orange btn-sm" title="Mon profil">
                <i class="fas fa-user me-1"></i>
                <span class="d-none d-md-inline">Profil</span>
            </a>
            <button class="btn btn-outline-secondary btn-sm d-lg-none" id="toggle-sidebar-btn" title="Afficher/Masquer les conversations">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

<div class="messages-wrapper">
    <div class="container-fluid px-0">
        <div class="row g-0">
            {{-- Sidebar Conversations --}}
            <div class="col-lg-4 col-xl-3 messages-sidebar" id="messages-sidebar">
                <div class="card card-racine h-100 border-0 rounded-0">
                    <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-comments text-racine-orange me-2"></i>
                            Conversations
                        </h5>
                        <div class="d-flex gap-2">
                            @php
                                $user = auth()->user();
                                $dashboardRoute = 'account.dashboard';
                                
                                if ($user->isAdmin()) {
                                    $dashboardRoute = 'admin.dashboard';
                                } elseif ($user->isCreator()) {
                                    $dashboardRoute = 'creator.dashboard';
                                }
                            @endphp
                            <a href="{{ route($dashboardRoute) }}" class="btn btn-sm btn-racine-orange" title="Retour au tableau de bord">
                                <i class="fas fa-tachometer-alt"></i>
                                <span class="d-none d-md-inline ms-1">Dashboard</span>
                            </a>
                            <a href="{{ route('messages.index') }}" class="btn btn-sm btn-outline-secondary" title="Toutes les conversations">
                                <i class="fas fa-list"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="close-sidebar-btn" title="Fermer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body border-bottom p-3">
                        <label for="conversation-search" class="form-label small text-muted mb-1">
                            <i class="fas fa-search me-1"></i> Recherche
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   id="conversation-search" 
                                   class="form-control border-start-0" 
                                   placeholder="Rechercher...">
                        </div>
                    </div>
                    <div class="conversations-list" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                        @php
                            $allConversations = app(\App\Services\ConversationService::class)
                                ->getConversationsForUser(auth()->id(), false);
                        @endphp
                        @forelse($allConversations as $conv)
                            @php
                                $otherParticipant = $conv->participants->where('user_id', '!=', auth()->id())->first();
                                $unreadCount = $conv->getUnreadCountForUser(auth()->id());
                                $isActive = $conv->id == $conversation->id;
                            @endphp
                            <a href="{{ route('messages.show', $conv->id) }}" 
                               class="conversation-item d-block text-decoration-none {{ $isActive ? 'active' : '' }} {{ $unreadCount > 0 ? 'unread' : '' }}">
                                <div class="d-flex align-items-start p-3">
                                    <div class="conversation-avatar me-3">
                                        @if($conv->type === 'order_thread')
                                            <div class="avatar-icon bg-info">
                                                <i class="fas fa-shopping-bag"></i>
                                            </div>
                                        @elseif($conv->type === 'product_thread')
                                            <div class="avatar-icon bg-success">
                                                <i class="fas fa-box"></i>
                                            </div>
                                        @else
                                            <div class="avatar-icon bg-racine-orange">
                                                {{ strtoupper(substr($otherParticipant->user->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                        @if($unreadCount > 0)
                                            <span class="unread-indicator"></span>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0 text-racine-black fw-semibold text-truncate">
                                                @if($conv->type === 'order_thread')
                                                    Commande #{{ $conv->order->order_number ?? 'N/A' }}
                                                @elseif($conv->type === 'product_thread')
                                                    {{ Str::limit($conv->product->title ?? 'Produit', 25) }}
                                                @else
                                                    {{ $otherParticipant->user->name ?? 'Utilisateur' }}
                                                @endif
                                            </h6>
                                            @if($conv->lastMessage)
                                                <small class="text-muted ms-2 flex-shrink-0">
                                                    {{ $conv->lastMessage->created_at->diffForHumans() }}
                                                </small>
                                            @endif
                                        </div>
                                        @if($conv->lastMessage)
                                            <p class="mb-0 text-muted small text-truncate">
                                                {{ Str::limit($conv->lastMessage->content, 50) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state text-center py-5 px-3">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Aucune conversation</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Zone de conversation principale --}}
            <div class="col-lg-8 col-xl-9 messages-main">
                <div class="card card-racine h-100 border-0 rounded-0 d-flex flex-column">
                    {{-- Header conversation --}}
                    <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                        <div class="flex-grow-1">
                            <h5 class="mb-1 text-racine-black fw-bold">
                                @if($conversation->type === 'order_thread')
                                    <i class="fas fa-shopping-bag text-info me-2"></i>
                                    Commande #{{ $conversation->order->order_number ?? 'N/A' }}
                                @elseif($conversation->type === 'product_thread')
                                    <i class="fas fa-box text-success me-2"></i>
                                    {{ $conversation->product->title ?? 'Produit' }}
                                @else
                                    @php
                                        $otherParticipant = $conversation->participants->where('user_id', '!=', auth()->id())->first();
                                    @endphp
                                    <i class="fas fa-user text-racine-orange me-2"></i>
                                    {{ $otherParticipant->user->name ?? 'Utilisateur' }}
                                @endif
                            </h5>
                            @if($conversation->subject)
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i>{{ $conversation->subject }}
                                </small>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" 
                                    id="archive-btn" 
                                    title="Archiver">
                                <i class="fas fa-archive me-1"></i>
                                <span class="d-none d-md-inline">Archiver</span>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                        data-bs-toggle="dropdown"
                                        title="Plus d'actions">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if(auth()->user()->email_messaging_enabled && auth()->user()->hasVerifiedProfessionalEmail())
                                    <li>
                                        <a class="dropdown-item" href="#" id="send-email-btn" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                                            <i class="fas fa-envelope me-2"></i>Envoyer par email
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="#" id="tag-product-btn">
                                            <i class="fas fa-tag me-2"></i>Tagger un produit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" id="delete-conversation-btn">
                                            <i class="fas fa-trash me-2"></i>Supprimer
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Produits tagués --}}
                    @if($taggedProducts->count() > 0)
                        <div class="card-body border-bottom bg-light p-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <small class="text-muted fw-semibold me-2">
                                    <i class="fas fa-tags text-racine-orange me-1"></i>Produits tagués:
                                </small>
                                @foreach($taggedProducts as $tag)
                                    <a href="{{ route('frontend.product', $tag->product->slug ?? $tag->product->id) }}" 
                                       target="_blank"
                                       class="badge bg-white text-dark border text-decoration-none" 
                                       style="padding: 0.5rem;">
                                        @if($tag->product->main_image)
                                            <img src="{{ asset('storage/' . $tag->product->main_image) }}" 
                                                 alt="{{ $tag->product->title }}"
                                                 class="tagged-product-img me-1"
                                                 style="width: 20px; height: 20px; object-fit: cover; border-radius: 3px;">
                                        @else
                                            <i class="fas fa-image me-1 text-muted"></i>
                                        @endif
                                        {{ Str::limit($tag->product->title, 20) }}
                                        @if(auth()->id() == $tag->tagged_by || auth()->user()->isAdmin())
                                            <button class="btn-close btn-close-sm ms-1" 
                                                    onclick="event.preventDefault(); untagProduct({{ $tag->product->id }});"
                                                    style="font-size: 0.6rem;"></button>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Messages --}}
                    <div class="messages-container flex-grow-1" id="messages-container">
                        <div class="messages-list" id="messages-list">
                            @forelse($messages as $message)
                                <div class="message-item {{ $message->user_id == auth()->id() ? 'own' : 'other' }}" 
                                     data-message-id="{{ $message->id }}">
                                    <div class="message-avatar">
                                        @if($message->user_id == auth()->id())
                                            <div class="avatar-circle bg-racine-orange">
                                                {{ strtoupper(substr($message->user->name, 0, 1)) }}
                                            </div>
                                        @else
                                            <div class="avatar-circle bg-secondary">
                                                {{ strtoupper(substr($message->user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="message-content">
                                        <div class="message-header">
                                            <span class="message-author fw-semibold">
                                                {{ $message->user_id == auth()->id() ? 'Vous' : $message->user->name }}
                                            </span>
                                            <span class="message-time text-muted">
                                                {{ $message->created_at->format('d/m/Y à H:i') }}
                                                @if($message->is_edited)
                                                    <span class="text-muted small ms-2">
                                                        <i class="fas fa-edit"></i> Modifié
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="message-bubble {{ $message->user_id == auth()->id() ? 'bg-racine-orange text-white' : 'bg-light' }}">
                                            <div class="message-text">{!! nl2br(e($message->content)) !!}</div>
                                            @if($message->attachments->count() > 0)
                                                <div class="message-attachments mt-2">
                                                    @foreach($message->attachments as $attachment)
                                                        <a href="{{ Storage::url($attachment->file_path) }}" 
                                                           target="_blank"
                                                           class="attachment-link d-inline-flex align-items-center gap-2 p-2 bg-white rounded mt-2">
                                                            <i class="fas fa-paperclip"></i>
                                                            <span>{{ $attachment->file_name }}</span>
                                                            <small class="text-muted">({{ number_format($attachment->file_size / 1024, 2) }} KB)</small>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        @if($message->user_id == auth()->id())
                                            <div class="message-actions">
                                                <button class="btn btn-sm btn-link text-muted p-0" 
                                                        onclick="editMessage({{ $message->id }})"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-link text-danger p-0" 
                                                        onclick="deleteMessage({{ $message->id }})"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun message pour le moment</p>
                                    <p class="text-muted small">Envoyez le premier message pour commencer la conversation</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Zone de saisie --}}
                    <div class="message-input-area border-top bg-white p-3">
                        <form id="message-form" enctype="multipart/form-data">
                            <div class="d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <textarea id="message-input" 
                                              class="form-control form-control-lg" 
                                              rows="2"
                                              placeholder="Tapez votre message..." 
                                              required
                                              style="resize: none; border-radius: 0.5rem;"></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="message-attachments" class="btn btn-sm btn-outline-secondary mb-0" style="cursor: pointer;">
                                                <i class="fas fa-paperclip me-1"></i>Pièce jointe
                                            </label>
                                            <input type="file" 
                                                   id="message-attachments" 
                                                   name="attachments[]" 
                                                   multiple 
                                                   class="d-none"
                                                   accept="image/*,.pdf,.doc,.docx">
                                            <span id="attachments-preview" class="small text-muted"></span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <span id="char-count">0</span>/5000 caractères
                                        </small>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-racine-orange btn-lg" id="send-btn" style="height: fit-content;">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    <span class="d-none d-md-inline">Envoyer</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tagger Produit --}}
@if($availableProducts)
<div class="modal fade" id="tagProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-racine-orange text-white">
                <h5 class="modal-title">
                    <i class="fas fa-tag me-2"></i>Tagger un produit
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="tag-product-form">
                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-semibold">Produit</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">Sélectionner un produit</option>
                            @foreach($availableProducts as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                    {{ $product->title }} - {{ number_format($product->price, 0, ',', ' ') }} FCFA
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tag-note" class="form-label fw-semibold">Note (optionnel)</label>
                        <textarea name="note" id="tag-note" class="form-control" rows="3" 
                                  placeholder="Ajoutez une note sur ce produit..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-racine-orange" id="confirm-tag-product-btn">
                    <i class="fas fa-tag me-1"></i>Tagger
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar sur mobile
    const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
    const sidebar = document.getElementById('messages-sidebar');
    let overlay = document.querySelector('.messages-sidebar-overlay');

    if (toggleSidebarBtn && sidebar) {
        // Créer l'overlay si il n'existe pas
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'messages-sidebar-overlay';
            document.body.appendChild(overlay);
        }

        // Toggle sidebar
        toggleSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        // Fermer le sidebar en cliquant sur l'overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Fermer le sidebar avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

        // Bouton fermer dans le sidebar
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    }
});

const conversationId = {{ $conversation->id }};
const messagesContainer = document.getElementById('messages-container');
const messagesList = document.getElementById('messages-list');
const messageForm = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const sendBtn = document.getElementById('send-btn');
const charCount = document.getElementById('char-count');
const attachmentsInput = document.getElementById('message-attachments');
const attachmentsPreview = document.getElementById('attachments-preview');

// Compteur de caractères
if (messageInput && charCount) {
    messageInput.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;
        if (length > 5000) {
            charCount.classList.add('text-danger');
        } else {
            charCount.classList.remove('text-danger');
        }
    });
}

// Gestion des pièces jointes
if (attachmentsInput) {
    attachmentsInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        if (files.length > 0) {
            const names = files.map(f => f.name).join(', ');
            attachmentsPreview.textContent = `${files.length} fichier(s): ${names}`;
        } else {
            attachmentsPreview.textContent = '';
        }
    });
}

// Envoyer un message
if (messageForm) {
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const content = messageInput.value.trim();
        if (!content && attachmentsInput.files.length === 0) return;

        const formData = new FormData(this);
        formData.append('content', content);

        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi...';

        fetch(`/profile/messages/${conversationId}/send`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                attachmentsInput.value = '';
                attachmentsPreview.textContent = '';
                charCount.textContent = '0';
                loadMessages();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'envoi du message.');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i><span class="d-none d-md-inline">Envoyer</span>';
        });
    });
}

// Charger les messages
function loadMessages() {
    fetch(`/profile/messages/${conversationId}/messages`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // TODO: Implémenter la mise à jour de l'UI avec les nouveaux messages
                scrollToBottom();
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

// Scroll automatique vers le bas
function scrollToBottom() {
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

// Rafraîchir les messages toutes les 5 secondes
setInterval(loadMessages, 5000);

// Scroll initial
setTimeout(scrollToBottom, 100);

// Archiver la conversation
if (document.getElementById('archive-btn')) {
    document.getElementById('archive-btn').addEventListener('click', function() {
        fetch(`/profile/messages/${conversationId}/archive`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("messages.index") }}';
            }
        });
    });
}

// Tagger un produit
@if($availableProducts)
if (document.getElementById('tag-product-btn')) {
    document.getElementById('tag-product-btn').addEventListener('click', function(e) {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('tagProductModal'));
        modal.show();
    });
}

if (document.getElementById('confirm-tag-product-btn')) {
    document.getElementById('confirm-tag-product-btn').addEventListener('click', function() {
        const form = document.getElementById('tag-product-form');
        const formData = new FormData(form);
        
        fetch('{{ route("messages.tag-product", $conversation->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    });
}

function untagProduct(productId) {
    if (confirm('Voulez-vous retirer ce tag ?')) {
        fetch(`/profile/messages/{{ $conversation->id }}/untag-product/${productId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}
@endif

// Éditer un message
function editMessage(messageId) {
    const messageItem = document.querySelector(`[data-message-id="${messageId}"]`);
    const messageText = messageItem.querySelector('.message-text').textContent.trim();
    const newContent = prompt('Modifier le message:', messageText);
    
    if (newContent && newContent !== messageText) {
        fetch(`/profile/messages/message/${messageId}/edit`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ content: newContent })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMessages();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
}

// Supprimer un message
function deleteMessage(messageId) {
    if (confirm('Voulez-vous vraiment supprimer ce message ?')) {
        fetch(`/profile/messages/message/${messageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMessages();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
}

// Envoyer un email depuis la messagerie
@if(auth()->user()->email_messaging_enabled && auth()->user()->hasVerifiedProfessionalEmail())
const emailContent = document.getElementById('email-content');
const emailCharCount = document.getElementById('email-char-count');
const confirmSendEmailBtn = document.getElementById('confirm-send-email-btn');

if (emailContent && emailCharCount) {
    emailContent.addEventListener('input', function() {
        const length = this.value.length;
        emailCharCount.textContent = length;
        if (length > 5000) {
            emailCharCount.classList.add('text-danger');
        } else {
            emailCharCount.classList.remove('text-danger');
        }
    });
}

if (confirmSendEmailBtn) {
    confirmSendEmailBtn.addEventListener('click', function() {
        const form = document.getElementById('send-email-form');
        const formData = new FormData(form);
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi...';
        
        fetch('{{ route("messages.send-email", $conversation->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('sendEmailModal'));
                modal.hide();
                alert('Email envoyé avec succès !');
                form.reset();
                emailCharCount.textContent = '0';
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'envoi de l\'email.');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Envoyer l\'email';
        });
    });
}
@endif
</script>
@endpush
@endsection
