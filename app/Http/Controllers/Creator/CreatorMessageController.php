<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CreatorMessageController extends Controller
{
    protected ConversationService $conversationService;
    protected MessageService $messageService;

    public function __construct(ConversationService $conversationService, MessageService $messageService)
    {
        $this->conversationService = $conversationService;
        $this->messageService = $messageService;
    }

    /**
     * Liste des conversations du créateur.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'all');

        // Récupérer toutes les conversations où le créateur est participant
        $conversations = $this->conversationService->getConversationsForUser($user->id);

        // Filtrer pour ne garder que les conversations pertinentes pour le business (Commandes et Produits)
        // On exclut les conversations privées "directes" hors contexte business si on veut être strict,
        // mais pour V1.5 on affiche tout pour éviter la confusion.
        
        if ($filter === 'unread') {
            $conversations = $conversations->filter(function ($conv) use ($user) {
                return $conv->getUnreadCountForUser($user->id) > 0;
            });
        }

        return view('creator.messages.index', compact('conversations', 'filter'));
    }

    /**
     * Voir une conversation spécifique.
     */
    public function show(Conversation $conversation): View
    {
        $this->authorize('view', $conversation); // Utilise la policy ConversationPolicy existante

        $user = Auth::user();
        
        // Marquer comme lue
        $this->messageService->markConversationAsRead($conversation->id, $user->id);
        
        // Charger les messages
        $messages = $this->messageService->getMessages($conversation->id, $user->id, 50); // 50 derniers

        return view('creator.messages.show', compact('conversation', 'messages'));
    }

    /**
     * Répondre à un message.
     */
    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'attachments.*' => ['nullable', 'file', 'max:5120'], // 5MB
        ]);

        $user = Auth::user();
        $attachments = $request->file('attachments') ?? [];

        $this->messageService->sendMessage(
            $conversation->id,
            $user->id,
            $validated['content'],
            $attachments
        );

        return redirect()->route('creator.messages.show', $conversation)
            ->with('success', 'Message envoyé.');
    }
}
