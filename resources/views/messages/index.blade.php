@extends('layouts.frontend')

@section('title', 'Messagerie - RACINE BY GANDA')
@section('page-title', 'Messagerie')
@section('page-subtitle', 'Gérez vos conversations')

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
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-comments me-1 text-racine-orange"></i>Messagerie
                    </li>
                </ol>
            </nav>
            <h1 class="mb-0 fw-bold text-racine-black" style="font-size: 1.75rem;">
                <i class="fas fa-comments text-racine-orange me-2"></i>
                Messagerie
            </h1>
            <p class="text-muted mb-0 small">Gérez vos conversations et échangez avec l'équipe</p>
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
            <a href="{{ route('profile.index') }}" class="btn btn-outline-racine-orange btn-sm" title="Mon profil">
                <i class="fas fa-user me-1"></i>
                <span class="d-none d-md-inline">Profil</span>
            </a>
            <a href="{{ route('profile.orders') }}" class="btn btn-outline-racine-orange btn-sm" title="Mes commandes">
                <i class="fas fa-shopping-bag me-1"></i>
                <span class="d-none d-md-inline">Commandes</span>
            </a>
            <button class="btn btn-outline-secondary btn-sm d-lg-none" id="toggle-sidebar-btn" title="Afficher/Masquer les conversations">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
    {{-- Statistiques --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            @include('partials.admin.stat-card', [
                'title' => 'Conversations',
                'value' => $totalConversations,
                'icon' => 'fas fa-comments',
                'color' => 'primary',
                'subtitle' => $readConversations . ' lues'
            ])
        </div>
        <div class="col-lg-3 col-md-6">
            @include('partials.admin.stat-card', [
                'title' => 'Non lues',
                'value' => $unreadCount,
                'icon' => 'fas fa-envelope',
                'color' => 'warning',
                'subtitle' => 'À traiter'
            ])
        </div>
        <div class="col-lg-3 col-md-6">
            @include('partials.admin.stat-card', [
                'title' => 'Archivées',
                'value' => $archivedConversations,
                'icon' => 'fas fa-archive',
                'color' => 'info',
                'subtitle' => 'Conversations archivées'
            ])
        </div>
        <div class="col-lg-3 col-md-6">
            @include('partials.admin.stat-card', [
                'title' => 'Commandes',
                'value' => $orderThreads,
                'icon' => 'fas fa-shopping-bag',
                'color' => 'success',
                'subtitle' => $productThreads . ' produits'
            ])
        </div>
    </div>

    <div class="row g-4">
        {{-- Sidebar Conversations --}}
        <div class="col-lg-4 col-xl-3 messages-sidebar" id="messages-sidebar">
            <div class="card card-racine h-100">
                {{-- Header Sidebar --}}
                <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-comments text-racine-orange me-2"></i>
                        Conversations
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-racine-orange btn-sm" id="new-conversation-btn" data-bs-toggle="modal" data-bs-target="#newConversationModal" title="Nouvelle conversation">
                            <i class="fas fa-plus me-1"></i>
                            <span class="d-none d-md-inline">Nouvelle</span>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-lg-none" id="close-sidebar-btn" title="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                {{-- Barre de recherche et filtres améliorés --}}
                <div class="card-body border-bottom p-3">
                    {{-- Recherche --}}
                    <div class="mb-3">
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
                                   placeholder="Rechercher une conversation..."
                                   value="{{ $search }}">
                        </div>
                    </div>

                    {{-- Filtres principaux --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-2">
                            <i class="fas fa-filter me-1"></i> Filtres
                        </label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="conversation-filter" id="filter-all" value="all" {{ $filter === 'all' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm" for="filter-all">
                                <i class="fas fa-list me-1"></i> Tous
                            </label>
                            
                            <input type="radio" class="btn-check" name="conversation-filter" id="filter-unread" value="unread" {{ $filter === 'unread' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm" for="filter-unread">
                                <i class="fas fa-envelope me-1"></i> Non lus
                                @if($unreadCount > 0)
                                    <span class="badge bg-danger ms-1 rounded-pill">{{ $unreadCount }}</span>
                                @endif
                            </label>
                            
                            <input type="radio" class="btn-check" name="conversation-filter" id="filter-archived" value="archived" {{ $filter === 'archived' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm" for="filter-archived">
                                <i class="fas fa-archive me-1"></i> Archivés
                            </label>
                        </div>
                    </div>

                    {{-- Filtres par type --}}
                    @if($orderThreads > 0 || $productThreads > 0 || $directConversations > 0)
                    <div>
                        <label class="form-label small text-muted mb-2">
                            <i class="fas fa-tags me-1"></i> Par type
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            @if($orderThreads > 0)
                                <button class="btn btn-sm btn-outline-info filter-type-btn" data-type="order_thread" title="Conversations de commandes">
                                    <i class="fas fa-shopping-bag me-1"></i>
                                    Commandes
                                    <span class="badge bg-info ms-1 rounded-pill">{{ $orderThreads }}</span>
                                </button>
                            @endif
                            @if($productThreads > 0)
                                <button class="btn btn-sm btn-outline-success filter-type-btn" data-type="product_thread" title="Conversations de produits">
                                    <i class="fas fa-box me-1"></i>
                                    Produits
                                    <span class="badge bg-success ms-1 rounded-pill">{{ $productThreads }}</span>
                                </button>
                            @endif
                            @if($directConversations > 0)
                                <button class="btn btn-sm btn-outline-primary filter-type-btn" data-type="direct" title="Conversations directes">
                                    <i class="fas fa-user me-1"></i>
                                    Directes
                                    <span class="badge bg-primary ms-1 rounded-pill">{{ $directConversations }}</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Liste des conversations --}}
                <div class="conversations-list" id="conversations-list" style="max-height: calc(100vh - 500px); overflow-y: auto;">
                    @forelse($conversations as $conversation)
                        @php
                            $otherParticipant = $conversation->participants->where('user_id', '!=', auth()->id())->first();
                            $unreadCountConv = $conversation->getUnreadCountForUser(auth()->id());
                            $isUnread = $unreadCountConv > 0;
                        @endphp
                        <a href="{{ route('messages.show', $conversation->id) }}" 
                           class="conversation-item d-block text-decoration-none {{ $isUnread ? 'unread' : '' }}"
                           data-conversation-id="{{ $conversation->id }}"
                           data-type="{{ $conversation->type }}">
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
                                            {{ strtoupper(substr($otherParticipant->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                    @if($isUnread)
                                        <span class="unread-indicator"></span>
                                    @endif
                                </div>

                                {{-- Contenu --}}
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 text-racine-black fw-semibold text-truncate">
                                            @if($conversation->type === 'order_thread')
                                                Commande #{{ $conversation->order->order_number ?? 'N/A' }}
                                            @elseif($conversation->type === 'product_thread')
                                                {{ Str::limit($conversation->product->title ?? 'Produit', 25) }}
                                            @else
                                                {{ $otherParticipant->user->name ?? 'Utilisateur' }}
                                            @endif
                                        </h6>
                                        @if($conversation->lastMessage)
                                            <small class="text-muted ms-2 flex-shrink-0">
                                                {{ $conversation->lastMessage->created_at->diffForHumans() }}
                                            </small>
                                        @endif
                                    </div>
                                    
                                    @if($conversation->lastMessage)
                                        <p class="mb-0 text-muted small text-truncate">
                                            {{ Str::limit($conversation->lastMessage->content, 60) }}
                                        </p>
                                    @else
                                        <p class="mb-0 text-muted small">Aucun message</p>
                                    @endif

                                    {{-- Badges --}}
                                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                        @if($isUnread)
                                            <span class="badge bg-racine-orange">{{ $unreadCountConv }} non lu{{ $unreadCountConv > 1 ? 's' : '' }}</span>
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
                            </div>
                        </a>
                    @empty
                        <div class="empty-state text-center py-5 px-3">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-50"></i>
                            <p class="text-muted mb-2">Aucune conversation</p>
                            <button class="btn btn-racine-orange btn-sm" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                                <i class="fas fa-plus me-1"></i> Créer une conversation
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Zone de conversation principale --}}
        <div class="col-lg-8 col-xl-9 messages-main">
            <div class="card card-racine h-100 border-0 rounded-0">
                <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-comments text-racine-orange me-2"></i>
                        Sélectionnez une conversation
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 600px;">
                    <div class="text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4"
                             style="width: 120px; height: 120px; background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);">
                            <i class="fas fa-comments fa-3x text-white"></i>
                        </div>
                        <h5 class="text-racine-black mb-2">Aucune conversation sélectionnée</h5>
                        <p class="text-muted mb-4">Choisissez une conversation dans la liste pour commencer à discuter</p>
                        <button class="btn btn-racine-orange" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                            <i class="fas fa-plus me-2"></i>Créer une nouvelle conversation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Nouvelle Conversation --}}
<div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-racine-orange text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Nouvelle conversation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="new-conversation-form">
                    <div class="mb-3">
                        <label for="recipient_id" class="form-label fw-semibold">
                            Destinataire <span class="text-danger">*</span>
                        </label>
                        <select name="recipient_id" id="recipient_id" class="form-select form-select-lg" required>
                            <option value="">Sélectionner un utilisateur</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Sélectionnez la personne avec qui vous souhaitez converser
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label fw-semibold">
                            Sujet (optionnel)
                        </label>
                        <input type="text" 
                               name="subject" 
                               id="subject" 
                               class="form-control form-control-lg" 
                               placeholder="Ex: Question sur ma commande">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Un sujet aide à organiser vos conversations
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-racine-orange" id="create-conversation-btn">
                    <i class="fas fa-paper-plane me-1"></i> Créer la conversation
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar sur mobile
    const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
    const sidebar = document.getElementById('messages-sidebar');
    let overlay = document.querySelector('.messages-sidebar-overlay');

    if (toggleSidebarBtn && sidebar) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'messages-sidebar-overlay';
            document.body.appendChild(overlay);
        }

        toggleSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

        const closeSidebarBtn = document.getElementById('close-sidebar-btn');
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    }

    // Scripts de recherche et filtres
    const searchInput = document.getElementById('conversation-search');
    const filterRadios = document.querySelectorAll('input[name="conversation-filter"]');
    const conversationItems = document.querySelectorAll('.conversation-item');
    const filterTypeBtns = document.querySelectorAll('.filter-type-btn');
    const createBtn = document.getElementById('create-conversation-btn');
    const newConversationForm = document.getElementById('new-conversation-form');

    // Recherche en temps réel
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterConversations(searchTerm, getActiveFilter(), getActiveTypeFilter());
        });
    }

    // Filtres radio
    filterRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            filterConversations(searchTerm, this.value, getActiveTypeFilter());
        });
    });

    // Filtres par type
    filterTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Toggle active state
            filterTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.toggle('active');
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            filterConversations(searchTerm, getActiveFilter(), getActiveTypeFilter());
        });
    });

    function getActiveFilter() {
        const activeFilter = document.querySelector('input[name="conversation-filter"]:checked');
        return activeFilter ? activeFilter.value : 'all';
    }

    function getActiveTypeFilter() {
        const activeTypeBtn = document.querySelector('.filter-type-btn.active');
        return activeTypeBtn ? activeTypeBtn.dataset.type : null;
    }

    function filterConversations(searchTerm, filter, typeFilter) {
        conversationItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            const isUnread = item.classList.contains('unread');
            const isArchived = item.dataset.archived === 'true';
            const itemType = item.dataset.type;
            
            let matchesSearch = !searchTerm || text.includes(searchTerm);
            let matchesFilter = true;
            let matchesType = !typeFilter || itemType === typeFilter;

            if (filter === 'unread') {
                matchesFilter = isUnread;
            } else if (filter === 'archived') {
                matchesFilter = isArchived;
            }

            if (matchesSearch && matchesFilter && matchesType) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Créer une nouvelle conversation
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            const form = newConversationForm;
            const formData = new FormData(form);
            
            fetch('{{ route("messages.create-direct") }}', {
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
                    window.location.href = '{{ route("messages.show", ":id") }}'.replace(':id', data.conversation.id);
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la création de la conversation.');
            });
        });
    }

    // Rafraîchir le nombre de conversations non lues
    function refreshUnreadCount() {
        fetch('{{ route("messages.unread-count") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const unreadBadge = document.querySelector('#filter-unread .badge');
                    if (data.count > 0) {
                        if (unreadBadge) {
                            unreadBadge.textContent = data.count;
                        } else {
                            const label = document.querySelector('label[for="filter-unread"]');
                            if (label) {
                                label.innerHTML = '<i class="fas fa-envelope me-1"></i> Non lus <span class="badge bg-danger ms-1">' + data.count + '</span>';
                            }
                        }
                    } else if (unreadBadge) {
                        unreadBadge.remove();
                    }
                }
            });
    }

    setInterval(refreshUnreadCount, 30000);
});
</script>
@endpush
@endsection
