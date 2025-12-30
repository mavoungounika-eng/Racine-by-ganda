<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDirectConversationRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\TagProductRequest;
use App\Services\ConversationService;
use App\Services\MessageService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\ConversationProductTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MessageController extends Controller
{
    protected ConversationService $conversationService;
    protected MessageService $messageService;

    public function __construct(ConversationService $conversationService, MessageService $messageService)
    {
        $this->conversationService = $conversationService;
        $this->messageService = $messageService;
    }

    /**
     * Afficher la liste des conversations de l'utilisateur.
     * 
     * @param Request $request Requête avec paramètres de recherche/filtres
     * @return View|JsonResponse Vue avec liste des conversations ou réponse JSON
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $archived = $request->boolean('archived', false);
        $search = $request->input('search');
        $filter = $request->input('filter', 'all'); // all, unread, archived

        $conversations = $this->conversationService->getConversationsForUser($user->id, $archived);
        
        // Recherche
        if ($search) {
            $conversations = $conversations->filter(function ($conv) use ($search) {
                $subjectMatch = stripos($conv->subject ?? '', $search) !== false;
                $lastMessageMatch = $conv->lastMessage && stripos($conv->lastMessage->content, $search) !== false;
                $participantMatch = $conv->participants->some(function ($p) use ($search) {
                    return $p->user && (stripos($p->user->name, $search) !== false || stripos($p->user->email, $search) !== false);
                });
                return $subjectMatch || $lastMessageMatch || $participantMatch;
            });
        }

        // Filtre
        if ($filter === 'unread') {
            $conversations = $conversations->filter(function ($conv) use ($user) {
                return $conv->getUnreadCountForUser($user->id) > 0;
            });
        }

        // Récupérer toutes les conversations pour les statistiques (avant filtres)
        $allConversations = $this->conversationService->getConversationsForUser($user->id, false);
        
        // Statistiques globales
        $totalConversations = $allConversations->count();
        $unreadCount = $this->conversationService->getUnreadConversationsCount($user->id);
        $archivedConversations = $this->conversationService->getConversationsForUser($user->id, true)->count();
        $readConversations = $totalConversations - $unreadCount;
        
        // Statistiques par type (sur toutes les conversations)
        $orderThreads = $allConversations->where('type', 'order_thread')->count();
        $productThreads = $allConversations->where('type', 'product_thread')->count();
        $directConversations = $allConversations->where('type', 'direct')->count();

        // Liste des utilisateurs pour le modal
        $users = User::where('id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'conversations' => $conversations->values(),
                'unread_count' => $unreadCount,
            ]);
        }

        return view('messages.index', compact(
            'conversations', 
            'unreadCount', 
            'archived', 
            'search', 
            'filter', 
            'users',
            'totalConversations',
            'archivedConversations',
            'readConversations',
            'orderThreads',
            'productThreads',
            'directConversations'
        ));
    }

    /**
     * Afficher une conversation avec ses messages.
     * 
     * @param int $id ID de la conversation
     * @param Request $request Requête HTTP
     * @return View|JsonResponse Vue avec conversation et messages ou réponse JSON
     */
    public function show(int $id, Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $conversation = $this->conversationService->getConversationWithMessages($id, $user->id);

        if (!$conversation) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Conversation non trouvée'], 404);
            }
            abort(404);
        }

        // Marquer comme lue
        $this->messageService->markConversationAsRead($id, $user->id);

        // Charger les messages
        $messages = $this->messageService->getMessages($id, $user->id, 50);

        // Charger les produits tagués
        $taggedProducts = $conversation->taggedProducts()->with(['taggedBy', 'product'])->get();

        // Liste des produits disponibles pour tagging (si admin/staff) - avec cache
        $availableProducts = null;
        if ($user->isAdmin() || in_array($user->getRoleSlug(), ['staff', 'admin', 'super_admin'])) {
            $availableProducts = \Illuminate\Support\Facades\Cache::remember(
                'available_products_for_tagging',
                300, // 5 minutes
                function () {
                    return Product::where('stock', '>', 0)
                        ->orderBy('title')
                        ->get(['id', 'title', 'price', 'main_image', 'sku']);
                }
            );
        }

        // Page précédente pour le retour
        $previousUrl = $request->header('referer') ?? route('messages.index');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'conversation' => $conversation,
                'messages' => $messages,
                'tagged_products' => $taggedProducts,
            ]);
        }

        return view('messages.show', compact('conversation', 'messages', 'taggedProducts', 'availableProducts', 'previousUrl'));
    }

    /**
     * Créer une conversation directe.
     * 
     * @param CreateDirectConversationRequest $request Requête validée
     * @return JsonResponse
     */
    public function createDirect(CreateDirectConversationRequest $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $conversation = $this->conversationService->createDirectConversation(
                $user->id,
                $request->recipient_id,
                $request->subject
            );

            return response()->json([
                'success' => true,
                'conversation' => $conversation->load(['participants.user', 'lastMessage']),
                'message' => 'Conversation créée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la conversation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer un message dans une conversation.
     * 
     * @param SendMessageRequest $request Requête validée
     * @param int $conversationId ID de la conversation
     * @return JsonResponse
     */
    public function sendMessage(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $user = Auth::user();

        try {
            // Récupérer les fichiers
            $attachments = $request->hasFile('attachments') 
                ? $request->file('attachments') 
                : [];

            // Valider les fichiers
            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    // Limite de taille : 10MB
                    if ($file->getSize() > 10 * 1024 * 1024) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La taille maximale d\'un fichier est de 10MB.',
                        ], 400);
                    }

                    // Types autorisés
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Type de fichier non autorisé. Types acceptés: images (JPEG, PNG, GIF, WebP), PDF, Word.',
                        ], 400);
                    }
                }
            }

            $message = $this->messageService->sendMessage(
                $conversationId,
                $user->id,
                $request->content ?? '',
                $attachments
            );

            return response()->json([
                'success' => true,
                'message' => $message->load(['user', 'attachments']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les messages d'une conversation (AJAX).
     * 
     * @param int $conversationId ID de la conversation
     * @param Request $request Requête avec paramètre limit optionnel
     * @return JsonResponse Liste paginée des messages
     */
    public function getMessages(int $conversationId, Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->input('limit', 50);
        $lastMessageId = $request->input('last_message_id');

        $messages = $this->messageService->getMessages($conversationId, $user->id, $limit);

        // Si last_message_id est fourni, ne retourner que les nouveaux messages
        if ($lastMessageId) {
            $messages = $messages->filter(function ($message) use ($lastMessageId) {
                return $message->id > $lastMessageId;
            })->values();
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'has_more' => $messages->count() >= $limit,
        ]);
    }

    /**
     * Éditer un message existant.
     * 
     * @param Request $request Requête avec nouveau contenu
     * @param int $messageId ID du message à éditer
     * @return JsonResponse Message édité ou erreur
     */
    public function editMessage(Request $request, int $messageId): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $user = Auth::user();

        try {
            $message = $this->messageService->editMessage($messageId, $user->id, $request->content);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Supprimer un message.
     * 
     * @param int $messageId ID du message à supprimer
     * @return JsonResponse Confirmation de suppression ou erreur
     */
    public function deleteMessage(int $messageId): JsonResponse
    {
        $user = Auth::user();

        try {
            $this->messageService->deleteMessage($messageId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Message supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Archiver une conversation pour l'utilisateur actuel.
     * 
     * @param int $conversationId ID de la conversation à archiver
     * @return JsonResponse Confirmation d'archivage
     */
    public function archive(int $conversationId): JsonResponse
    {
        $user = Auth::user();

        $this->conversationService->archiveForUser($conversationId, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation archivée',
        ]);
    }

    /**
     * Désarchiver une conversation pour l'utilisateur actuel.
     * 
     * @param int $conversationId ID de la conversation à désarchiver
     * @return JsonResponse Confirmation de désarchivage
     */
    public function unarchive(int $conversationId): JsonResponse
    {
        $user = Auth::user();

        $this->conversationService->unarchiveForUser($conversationId, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation désarchivée',
        ]);
    }

    /**
     * Obtenir le nombre de conversations non lues pour l'utilisateur actuel.
     * 
     * @return JsonResponse Nombre de conversations non lues
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->conversationService->getUnreadConversationsCount($user->id);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Créer un thread de discussion pour une commande.
     * 
     * @param Order $order Commande concernée
     * @return \Illuminate\Http\RedirectResponse Redirection vers la conversation créée
     */
    public function createOrderThread(Order $order): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est propriétaire de la commande ou admin
        if ($order->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Vous n\'avez pas accès à cette commande.');
        }

        try {
            $conversation = $this->conversationService->createOrderThread($order, $user->id);

            return redirect()
                ->route('messages.show', $conversation->id)
                ->with('success', 'Conversation créée avec succès');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la création de la conversation: ' . $e->getMessage());
        }
    }

    /**
     * Créer un thread de discussion pour un produit.
     * 
     * @param Product $product Produit concerné
     * @return \Illuminate\Http\RedirectResponse Redirection vers la conversation créée
     */
    public function createProductThread(Product $product): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        try {
            $conversation = $this->conversationService->createProductThread($product, $user->id);

            return redirect()
                ->route('messages.show', $conversation->id)
                ->with('success', 'Conversation créée avec succès');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la création de la conversation: ' . $e->getMessage());
        }
    }

    /**
     * Tagger un produit dans une conversation.
     * 
     * @param TagProductRequest $request Requête validée
     * @param Conversation $conversation Conversation concernée
     * @return JsonResponse
     */
    public function tagProduct(TagProductRequest $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        try {

            $tag = ConversationProductTag::create([
                'conversation_id' => $conversation->id,
                'product_id' => $request->product_id,
                'tagged_by' => $user->id,
                'note' => $request->note,
            ]);

            $tag->load(['product', 'taggedBy']);

            return response()->json([
                'success' => true,
                'tag' => $tag,
                'message' => 'Produit tagué avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du tag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retirer un tag produit d'une conversation.
     * 
     * @param Conversation $conversation Conversation concernée
     * @param Product $product Produit dont le tag doit être retiré
     * @return JsonResponse Confirmation de retrait ou erreur
     */
    public function untagProduct(Conversation $conversation, Product $product): JsonResponse
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur est participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas participant de cette conversation.',
            ], 403);
        }

        try {
            $tag = ConversationProductTag::where('conversation_id', $conversation->id)
                ->where('product_id', $product->id)
                ->first();

            if (!$tag) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag non trouvé.',
                ], 404);
            }

            // Seul celui qui a tagué ou un admin peut retirer
            if ($tag->tagged_by !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas la permission de retirer ce tag.',
                ], 403);
            }

            $tag->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tag retiré avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait du tag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer un email depuis la messagerie.
     * 
     * @param Request $request Requête avec sujet et contenu
     * @param Conversation $conversation Conversation concernée
     * @return JsonResponse
     */
    public function sendEmail(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB max
        ]);

        try {
            $emailService = app(\App\Services\EmailMessagingService::class);
            
            $attachments = [];
            if ($request->hasFile('attachments')) {
                $attachments = $request->file('attachments');
            }

            $emailService->sendEmailFromMessaging(
                $conversation,
                $user,
                $validated['subject'],
                $validated['content'],
                $attachments
            );

            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
