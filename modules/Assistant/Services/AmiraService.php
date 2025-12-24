<?php

namespace Modules\Assistant\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\CRM\Models\CrmInteraction;
use Modules\CRM\Models\CrmContact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AmiraService
{
    protected array $config;
    protected ?string $userId;
    protected string $userRole;
    protected ?User $user;

    protected array $commands = [
        '/aide' => 'showHelp',
        '/help' => 'showHelp',
        '/stats' => 'showStats',
        '/stocks' => 'showStocks',
        '/erp' => 'showErpStatus',
        '/crm' => 'showRecentInteractions',
        '/commandes' => 'showOrders',
        '/faq' => 'showFaq',
        '/contact' => 'showContact',
        '/livraison' => 'showShipping',
        '/clear' => 'clearConversation',
        '/reset' => 'clearConversation',
    ];

    public function __construct()
    {
        $this->config = config('assistant.amira', []);
        $this->user = Auth::user();
        $this->userId = $this->user ? (string) $this->user->id : session()->getId();
        $this->userRole = $this->user ? ($this->user->getRoleSlug() ?? 'client') : 'guest';
    }

    /**
     * Point d'entrée principal - Traite un message
     */
    public function chat(string $message, array $context = []): array
    {
        if (!$this->checkRateLimit()) {
            return $this->errorResponse('⏳ Doucement ! Attendez quelques secondes...');
        }

        if (!$this->checkDailyLimit()) {
            return $this->errorResponse('Vous avez atteint votre limite quotidienne. Revenez demain ou contactez-nous ! 📧');
        }

        $trimmedMessage = trim($message);
        
        // Commandes spéciales
        if (str_starts_with($trimmedMessage, '/')) {
            return $this->handleCommand($trimmedMessage);
        }

        // Détection d'intention
        $intent = $this->detectIntent($message);
        
        // Réponses locales pour certaines intentions
        if ($intent && $this->canHandleLocally($intent)) {
            $response = $this->handleIntent($intent, $message, $context);
            $this->logConversation($message, $response);
            return $this->successResponse($response);
        }

        // Historique de conversation
        $history = $this->getConversationHistory();
        $history[] = ['role' => 'user', 'content' => $message];

        // Appel IA selon le provider
        $provider = $this->config['ai']['provider'] ?? 'smart';
        
        try {
            $response = match($provider) {
                'openai' => $this->callOpenAI($history, $context),
                'anthropic' => $this->callAnthropic($history, $context),
                'groq' => $this->callGroq($history, $context),
                default => $this->generateSmartResponse($message, $intent),
            };
        } catch (\Exception $e) {
            Log::error('Amira AI Error: ' . $e->getMessage());
            $response = $this->generateSmartResponse($message, $intent);
        }

        // Sauvegarder l'historique
        $history[] = ['role' => 'assistant', 'content' => $response];
        $this->saveConversationHistory($history);
        $this->incrementDailyCount();

        return $this->successResponse($response);
    }

    /**
     * Appel API Groq (GRATUIT - Llama, Mixtral)
     */
    protected function callGroq(array $history, array $context): string
    {
        $apiKey = $this->config['api_keys']['groq'] ?? env('GROQ_API_KEY');
        
        if (!$apiKey) {
            return $this->generateSmartResponse(end($history)['content'] ?? '', null);
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        $model = $this->config['ai']['models']['groq'] ?? 'llama-3.1-70b-versatile';

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $this->config['ai']['max_tokens'] ?? 800,
            'temperature' => $this->config['ai']['temperature'] ?? 0.7,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content') ?? $this->generateSmartResponse('', null);
        }

        Log::error('Groq API Error: ' . $response->body());
        throw new \Exception('Groq API Error');
    }

    /**
     * Appel API OpenAI
     */
    protected function callOpenAI(array $history, array $context): string
    {
        $apiKey = $this->config['api_keys']['openai'] ?? env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            return $this->generateSmartResponse(end($history)['content'] ?? '', null);
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        $model = $this->config['ai']['models']['openai'] ?? 'gpt-4o-mini';

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $this->config['ai']['max_tokens'] ?? 800,
            'temperature' => $this->config['ai']['temperature'] ?? 0.7,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content') ?? $this->generateSmartResponse('', null);
        }

        throw new \Exception('OpenAI API Error: ' . $response->body());
    }

    /**
     * Appel API Anthropic (Claude)
     */
    protected function callAnthropic(array $history, array $context): string
    {
        $apiKey = $this->config['api_keys']['anthropic'] ?? env('ANTHROPIC_API_KEY');
        
        if (!$apiKey) {
            return $this->generateSmartResponse(end($history)['content'] ?? '', null);
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        $model = $this->config['ai']['models']['anthropic'] ?? 'claude-3-haiku-20240307';

        $response = Http::timeout(30)->withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $this->config['ai']['max_tokens'] ?? 800,
            'system' => $systemPrompt,
            'messages' => $history,
        ]);

        if ($response->successful()) {
            return $response->json('content.0.text') ?? $this->generateSmartResponse('', null);
        }

        throw new \Exception('Anthropic API Error: ' . $response->body());
    }

    /**
     * Génère une réponse intelligente sans API externe
     */
    protected function generateSmartResponse(string $message, ?string $intent): string
    {
        $message = strtolower($message);

        // Réponses basées sur les mots-clés FAQ
        $faqResponses = $this->config['knowledge_base']['faq'] ?? [];
        
        foreach ($faqResponses as $key => $faq) {
            if (str_contains($message, $key) || ($intent && str_contains($key, $intent))) {
                return $faq['answer'];
            }
        }

        // Réponses contextuelles
        if ($intent === 'order_status') {
            return $this->handleOrderStatus();
        }

        if ($intent === 'products' || $intent === 'stock') {
            return $this->handleProductsQuery();
        }

        if ($intent === 'price') {
            return $this->handlePriceQuery($message);
        }

        if ($intent === 'size') {
            return $this->handleSizeQuery();
        }

        if ($intent === 'custom') {
            return $this->handleCustomQuery();
        }

        if ($intent === 'complaint') {
            return $this->handleComplaint();
        }

        // Réponse par défaut conversationnelle
        $defaults = [
            "Je comprends votre question ! 🤔 Pour vous aider au mieux, pourriez-vous me donner plus de détails ?\n\nJe peux vous renseigner sur :\n• 📦 Vos commandes\n• 🚚 La livraison\n• ↩️ Les retours\n• 👗 Nos produits\n• 📏 Les tailles",
            "Merci pour votre message ! ✨ Je suis Amira, et je suis là pour vous guider.\n\nSi votre question est complexe, notre équipe est disponible à **contact@racinebyganda.com**",
            "Bonne question ! Je ne suis pas sûre d'avoir bien compris. Vous cherchez des informations sur :\n• Une commande ?\n• Un produit ?\n• La livraison ?\n\nDites-moi ! 💬",
        ];
        
        return $defaults[array_rand($defaults)];
    }

    /**
     * Gestion des commandes utilisateur
     */
    protected function handleOrderStatus(): string
    {
        if ($this->user) {
            try {
                $orders = Order::where('user_id', $this->user->id)
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get();
                
                if ($orders->isNotEmpty()) {
                    $response = "📦 **Vos dernières commandes** :\n\n";
                    foreach ($orders as $order) {
                        $status = match($order->status) {
                            'pending' => '⏳ En attente',
                            'processing' => '🔄 En préparation',
                            'shipped' => '🚚 Expédiée',
                            'delivered', 'completed' => '✅ Livrée',
                            'cancelled' => '❌ Annulée',
                            default => '📋 ' . ucfirst($order->status),
                        };
                        $date = $order->created_at->format('d/m/Y');
                        $amount = number_format($order->total_amount, 0, ',', ' ');
                        $response .= "• **#{$order->id}** - {$status}\n  {$date} • {$amount} FCFA\n\n";
                    }
                    $response .= "Besoin de détails sur une commande ? Donnez-moi son numéro ! 😊";
                    return $response;
                }
                return "Vous n'avez pas encore de commande ! 🛍️\n\nDécouvrez notre [boutique](/boutique) et trouvez votre pièce coup de cœur !";
            } catch (\Exception $e) {
                return "Je ne peux pas accéder à vos commandes pour le moment. Contactez-nous : **contact@racinebyganda.com** 📧";
            }
        }
        
        return "Pour voir vos commandes, **connectez-vous** à votre compte ! 🔐\n\n➡️ [Se connecter](/auth)\n\nSi vous avez passé commande sans compte, contactez-nous avec votre email de commande.";
    }

    /**
     * Gestion des questions produits (Boutique)
     */
    protected function handleProductsQuery(): string
    {
        try {
            $count = Product::where('is_active', true)->count();
            
            // Si l'utilisateur est un créateur, montrer ses propres produits
            if ($this->isCreator()) {
                $creatorProfile = CreatorProfile::where('user_id', $this->userId)->first();
                if ($creatorProfile) {
                    $myCount = Product::where('creator_profile_id', $creatorProfile->id)->count();
                    $lowStock = Product::where('creator_profile_id', $creatorProfile->id)
                        ->where('stock', '<=', 5)
                        ->count();
                    
                    $response = "👗 **Votre Boutique Créateur**\n\n";
                    $response .= "Vous avez **{$myCount}** produits en ligne.\n";
                    if ($lowStock > 0) {
                        $response .= "⚠️ **{$lowStock}** produits ont un stock faible.\n";
                    }
                    $response .= "\n➡️ [Gérer mes produits](/creator/products)";
                    return $response;
                }
            }

            $newProducts = Product::where('is_active', true)
                ->where('stock', '>', 0)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get(['title', 'price']);
            
            $response = "👗 **Notre collection RACINE BY GANDA**\n\n";
            $response .= "Nous avons **{$count}** pièces uniques qui célèbrent l'héritage africain !\n\n";
            
            if ($newProducts->isNotEmpty()) {
                $response .= "✨ **Nouveautés** :\n";
                foreach ($newProducts as $product) {
                    $price = number_format($product->price, 0, ',', ' ');
                    $response .= "• {$product->title} - **{$price} FCFA**\n";
                }
            }
            
            $response .= "\n➡️ [Découvrir la boutique](/boutique)";
            return $response;
        } catch (\Exception $e) {
            return "👗 Découvrez nos créations uniques dans la **Boutique** !\n\nChaque pièce raconte une histoire africaine.\n\n➡️ [Voir la collection](/boutique)";
        }
    }

    /**
     * Gestion des questions ERP (Atelier / Matières premières)
     */
    protected function handleErpQuery(): string
    {
        if (!$this->isTeamMember() && !$this->isCreator()) {
            return "Désolée, ces informations sont réservées aux créateurs et à l'équipe RACINE. 🛡️";
        }

        try {
            $rawMaterials = ErpRawMaterial::orderBy('current_stock', 'asc')->take(5)->get();
            
            if ($rawMaterials->isEmpty()) {
                return "📦 **Atelier** : Aucune matière première enregistrée pour le moment.";
            }

            $response = "🧵 **État de l'Atelier**\n\n";
            $response .= "Voici vos stocks de matières premières prioritaires :\n\n";
            
            foreach ($rawMaterials as $material) {
                $status = $material->current_stock <= $material->min_stock_alert ? '🚨' : '✅';
                $response .= "• {$status} **{$material->name}** : {$material->current_stock} {$material->unit}\n";
            }
            
            $response .= "\n➡️ [Accéder à l'ERP](/admin/erp)";
            return $response;
        } catch (\Exception $e) {
            return "❌ Impossible de récupérer les données de l'Atelier actuellement.";
        }
    }

    /**
     * Gestion des questions CRM (Interactions clients)
     */
    protected function handleCrmQuery(): string
    {
        if (!$this->isTeamMember()) {
            return "Ces informations confidentielles sont réservées aux administrateurs. 🛡️";
        }

        try {
            $interactions = CrmInteraction::with('contact')
                ->orderBy('occurred_at', 'desc')
                ->take(3)
                ->get();
            
            if ($interactions->isEmpty()) {
                return "👥 **CRM** : Aucune interaction client récente.";
            }

            $response = "👥 **Dernières interactions CRM**\n\n";
            foreach ($interactions as $interaction) {
                $date = $interaction->occurred_at->format('d/m H:i');
                $contact = $interaction->contact->full_name ?? 'Client inconnu';
                $response .= "• [{$date}] **{$contact}** : {$interaction->subject}\n";
            }
            
            $response .= "\n➡️ [Ouvrir le CRM](/admin/crm)";
            return $response;
        } catch (\Exception $e) {
            return "❌ Erreur lors de l'accès aux données CRM.";
        }
    }

    /**
     * Gestion des questions sur les abonnements (Plan créateur)
     */
    protected function handleSubscriptionQuery(): string
    {
        if (!$this->isCreator()) {
            return "Cette question concerne les plans créateurs. Souhaitez-vous en devenir un ? ✨\n\n➡️ [Devenir Créateur](/creator/register)";
        }

        try {
            $creatorProfile = CreatorProfile::where('user_id', $this->userId)->first();
            if (!$creatorProfile) return "Profil créateur introuvable.";

            $subscription = CreatorSubscription::with('plan')
                ->where('creator_profile_id', $creatorProfile->id)
                ->first();
            
            if (!$subscription) {
                return "✨ **Votre Plan**\n\nVous n'avez pas encore de plan actif. Choisissez-en un pour commencer à vendre !\n\n➡️ [Voir les abonnements](/creator/subscriptions)";
            }

            $planName = $subscription->plan->name ?? 'Standard';
            $date = $subscription->current_period_end ? $subscription->current_period_end->format('d/m/Y') : 'N/A';
            
            $response = "💎 **Votre Plan : " . ucfirst($planName) . "**\n\n";
            $response .= "• État : **" . ucfirst($subscription->status) . "**\n";
            $response .= "• Prochain renouvellement : **{$date}**\n";
            
            if ($subscription->status === 'active') {
                $response .= "\n✅ Toutes vos fonctionnalités sont débloquées !";
            }
            
            $response .= "\n\n➡️ [Gérer mon abonnement](/creator/subscriptions)";
            return $response;
        } catch (\Exception $e) {
            return "❌ Impossible de vérifier votre abonnement actuellement.";
        }
    }

    /**
     * Gestion des questions de prix
     */
    protected function handlePriceQuery(string $message): string
    {
        // Chercher si un produit est mentionné
        try {
            $products = Product::where('is_active', true)
                ->where('stock', '>', 0)
                ->get(['title', 'price']);
            
            foreach ($products as $product) {
                if (str_contains(strtolower($message), strtolower($product->title))) {
                    $price = number_format($product->price, 0, ',', ' ');
                    return "💰 **{$product->title}**\n\nPrix : **{$price} FCFA**\n\n➡️ [Voir ce produit](/boutique)";
                }
            }
        } catch (\Exception $e) {
            // Continuer avec réponse générique
        }

        return "💰 **Nos prix**\n\nNos créations vont de **15 000 FCFA** à **150 000 FCFA** selon le type de pièce.\n\n• **Accessoires** : 15 000 - 35 000 FCFA\n• **Tops & Chemises** : 25 000 - 55 000 FCFA\n• **Robes & Ensembles** : 45 000 - 150 000 FCFA\n\n➡️ [Voir tous les prix](/boutique)";
    }

    /**
     * Gestion des questions de taille
     */
    protected function handleSizeQuery(): string
    {
        return "📏 **Guide des tailles RACINE**\n\n| Taille | Tour poitrine | Tour taille | Tour hanches |\n|--------|---------------|-------------|---------------|\n| S | 86-90 cm | 66-70 cm | 90-94 cm |\n| M | 90-94 cm | 70-74 cm | 94-98 cm |\n| L | 94-98 cm | 74-78 cm | 98-102 cm |\n| XL | 98-102 cm | 78-82 cm | 102-106 cm |\n\n💡 **Astuce** : Entre deux tailles ? Prenez la plus grande !\n\n📐 Besoin de **sur-mesure** ? C'est possible !";
    }

    /**
     * Gestion des demandes sur-mesure
     */
    protected function handleCustomQuery(): string
    {
        return "✂️ **Service Sur-Mesure RACINE**\n\nNous créons des pièces **uniques** adaptées à vos mensurations !\n\n**Comment ça marche ?**\n1. 📧 Contactez-nous avec votre idée\n2. 📏 Envoyez vos mensurations\n3. 🎨 Choisissez vos tissus\n4. ⏳ Réception en 2-3 semaines\n\n**Consultation gratuite** !\n\n📧 **contact@racinebyganda.com**\n📱 **+242 06 XXX XX XX**";
    }

    /**
     * Gestion des plaintes
     */
    protected function handleComplaint(): string
    {
        return "😔 Je suis vraiment désolée d'apprendre que vous rencontrez un problème.\n\n**Votre satisfaction est notre priorité** !\n\nPour traiter votre demande rapidement :\n\n📧 **contact@racinebyganda.com**\n📱 **+242 06 XXX XX XX** (WhatsApp)\n\nIndiquez :\n• Votre numéro de commande\n• Le problème rencontré\n• Des photos si nécessaire\n\nNotre équipe vous répond sous **24h** ! 🙏";
    }

    /**
     * Détection d'intention améliorée avec limites de mots
     */
    protected function detectIntent(string $message): ?string
    {
        $message = strtolower($message);
        $intents = $this->config['intents'] ?? [];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                // Utiliser des limites de mots pour éviter les correspondances partielles (ex: 'l' dans 'client')
                $pattern = '/\b' . preg_quote(strtolower($keyword), '/') . '\b/i';
                if (preg_match($pattern, $message)) {
                    return $intent;
                }
            }
        }

        return null;
    }

    protected function canHandleLocally(?string $intent): bool
    {
        $localIntents = [
            'greeting', 'farewell', 'thanks', 'shipping', 'return', 
            'payment', 'contact', 'help', 'about', 'size', 'custom', 
            'complaint', 'order_status', 'products', 'price',
            'erp_query', 'crm_query', 'subscription_query'
        ];
        return in_array($intent, $localIntents);
    }

    protected function handleIntent(string $intent, string $message, array $context): string
    {
        return match($intent) {
            'greeting' => $this->handleGreeting(),
            'farewell' => $this->handleFarewell(),
            'thanks' => $this->handleThanks(),
            'shipping' => $this->handleShipping(),
            'return' => $this->handleReturn(),
            'payment' => $this->handlePayment(),
            'contact' => $this->handleContact(),
            'help' => $this->handleHelp(),
            'about' => $this->handleAbout(),
            'size' => $this->handleSizeQuery(),
            'custom' => $this->handleCustomQuery(),
            'complaint' => $this->handleComplaint(),
            'order_status' => $this->handleOrderStatus(),
            'products', 'stock' => $this->handleProductsQuery(),
            'price' => $this->handlePriceQuery($message),
            'erp_query' => $this->handleErpQuery(),
            'crm_query' => $this->handleCrmQuery(),
            'subscription_query' => $this->handleSubscriptionQuery(),
            default => $this->generateSmartResponse($message, $intent),
        };
    }

    protected function handleGreeting(): string
    {
        $name = $this->user ? " **{$this->user->name}**" : "";
        $hour = (int) now()->format('H');
        
        $timeGreeting = match(true) {
            $hour >= 5 && $hour < 12 => "Bonjour",
            $hour >= 12 && $hour < 18 => "Bon après-midi",
            default => "Bonsoir",
        };
        
        $greetings = [
            "{$timeGreeting}{$name} ! ✨ Je suis **Amira**, votre guide chez RACINE BY GANDA. Comment puis-je vous aider ?",
            "Bienvenue{$name} ! 👋 Je suis Amira. Que recherchez-vous aujourd'hui ? Une tenue spéciale, des infos sur une commande ?",
            "{$timeGreeting}{$name} ! 🌟 Ravie de vous accueillir ! Je suis là pour vous guider dans l'univers RACINE.",
        ];
        return $greetings[array_rand($greetings)];
    }

    protected function handleFarewell(): string
    {
        return $this->config['personality']['farewell'] ?? "Au revoir ! ✨ À très bientôt chez RACINE BY GANDA !";
    }

    protected function handleThanks(): string
    {
        $responses = [
            "Avec plaisir ! 😊 N'hésitez pas si vous avez d'autres questions.",
            "De rien ! ✨ Je suis là pour vous. Belle journée !",
            "C'est un plaisir de vous aider ! 🙏 À très bientôt !",
            "Ravie d'avoir pu vous aider ! 💫 Bonne visite sur RACINE !",
        ];
        return $responses[array_rand($responses)];
    }

    protected function handleShipping(): string
    {
        return $this->config['knowledge_base']['faq']['livraison']['answer'] ?? 
            "🚚 **Livraison**\n\n• Pointe-Noire : Gratuite, 24-48h\n• Brazzaville : 2-4 jours\n• International : Sur devis";
    }

    protected function handleReturn(): string
    {
        return $this->config['knowledge_base']['faq']['retours']['answer'] ?? 
            "↩️ **Retours**\n\n• 14 jours pour changer d'avis\n• Article non porté, étiquettes intactes\n• Retours gratuits à Pointe-Noire";
    }

    protected function handlePayment(): string
    {
        return $this->config['knowledge_base']['faq']['paiement']['answer'] ?? 
            "💳 **Paiements**\n\n• Carte bancaire\n• Mobile Money\n• Paiement à la livraison";
    }

    protected function handleContact(): string
    {
        return "📞 **Contactez RACINE BY GANDA**\n\n📱 **Téléphone** : +242 06 XXX XX XX\n📧 **Email** : contact@racinebyganda.com\n💬 **WhatsApp** : +242 06 XXX XX XX\n\n📍 **Adresse** : Pointe-Noire, Congo\n🕐 **Horaires** : Lun-Sam, 9h-18h\n\nNotre équipe répond sous **24h** ! 💌";
    }

    protected function handleHelp(): string
    {
        return "🤝 **Comment puis-je vous aider ?**\n\nJe peux répondre à vos questions sur :\n\n• 📦 **Commandes** - Suivi, statut, problèmes\n• 🚚 **Livraison** - Délais, zones, tarifs\n• ↩️ **Retours** - Procédure, remboursements\n• 💳 **Paiement** - Moyens acceptés\n• 👗 **Produits** - Collections, tailles, prix\n• ✂️ **Sur-mesure** - Créations personnalisées\n\n💬 Posez votre question ou tapez `/faq` !";
    }

    protected function handleAbout(): string
    {
        return "✨ **RACINE BY GANDA**\n\n🌍 Marque de **mode africaine premium** née au Congo-Brazzaville.\n\n**Notre mission** : Célébrer l'héritage africain à travers une mode raffinée et moderne.\n\n**Notre promesse** : Chaque pièce raconte une histoire, chaque création est unique.\n\n**Nos valeurs** :\n• 🎨 Authenticité\n• ⭐ Qualité premium\n• 🌱 Durabilité\n• 💪 Fierté culturelle\n\n➡️ [Découvrir notre histoire](/a-propos)";
    }

    // === COMMANDES SPÉCIALES ===

    protected function handleCommand(string $command): array
    {
        $parts = explode(' ', $command);
        $cmd = strtolower($parts[0]);

        if (!isset($this->commands[$cmd])) {
            return $this->successResponse("❓ Commande inconnue. Tapez `/aide` pour voir la liste !");
        }

        $method = $this->commands[$cmd];
        return $this->successResponse($this->$method());
    }

    protected function showHelp(): string
    {
        $help = "🤖 **Commandes Amira**\n\n";
        $help .= "• `/aide` - Cette aide\n";
        $help .= "• `/faq` - Questions fréquentes\n";
        $help .= "• `/contact` - Nos coordonnées\n";
        $help .= "• `/livraison` - Infos livraison\n";
        $help .= "• `/clear` - Effacer la conversation\n";
        
        if ($this->isTeamMember()) {
            $help .= "\n**🔐 Équipe :**\n";
            $help .= "• `/stats` - Statistiques\n";
            $help .= "• `/stocks` - Alertes stock\n";
            $help .= "• `/commandes` - Commandes en attente\n";
        }
        
        return $help;
    }

    protected function showFaq(): string
    {
        return "❓ **FAQ RACINE BY GANDA**\n\n" .
            "**1. Délais de livraison ?**\n→ Pointe-Noire 24-48h, Brazzaville 2-4j\n\n" .
            "**2. Comment retourner un article ?**\n→ 14 jours, article non porté, contactez-nous\n\n" .
            "**3. Quels paiements acceptez-vous ?**\n→ CB, Mobile Money, espèces à la livraison\n\n" .
            "**4. Proposez-vous du sur-mesure ?**\n→ Oui ! Contactez-nous pour un devis\n\n" .
            "**5. Comment connaître ma taille ?**\n→ Consultez le guide sur chaque fiche produit\n\n" .
            "D'autres questions ? Je suis là ! 💬";
    }

    protected function showContact(): string
    {
        return $this->handleContact();
    }

    protected function showShipping(): string
    {
        return $this->handleShipping();
    }

    protected function clearConversation(): string
    {
        $this->clearHistory();
        return "🗑️ Conversation effacée !\n\nComment puis-je vous aider ? 😊";
    }

    protected function showStats(): string
    {
        if (!$this->isTeamMember()) {
            return "🔒 Commande réservée à l'équipe.";
        }

        try {
            $stats = [
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'orders_pending' => Order::where('status', 'pending')->count(),
                'products' => Product::count(),
                'out_of_stock' => Product::where('stock', '<=', 0)->count(),
                'revenue_today' => Order::whereDate('created_at', today())
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
            ];

            return "📊 **Statistiques**\n\n" .
                "📦 Commandes aujourd'hui : **{$stats['orders_today']}**\n" .
                "⏳ En attente : **{$stats['orders_pending']}**\n" .
                "👗 Produits : **{$stats['products']}** (🚫 {$stats['out_of_stock']} rupture)\n" .
                "💰 CA du jour : **" . number_format($stats['revenue_today'], 0, ',', ' ') . " FCFA**";
        } catch (\Exception $e) {
            return "❌ Erreur lors de la récupération des stats.";
        }
    }

    protected function showStocks(): string
    {
        if (!$this->isTeamMember()) {
            return "🔒 Commande réservée à l'équipe.";
        }

        try {
            $low = Product::where('stock', '<', 5)->where('stock', '>', 0)->orderBy('stock')->take(5)->get();
            $out = Product::where('stock', '<=', 0)->count();

            if ($low->isEmpty() && $out === 0) {
                return "✅ Stocks OK !";
            }

            $response = "📦 **Alertes Stock**\n\n";
            if ($out > 0) $response .= "🚫 **{$out}** en rupture\n\n";
            if ($low->isNotEmpty()) {
                $response .= "⚠️ **Stock faible** :\n";
                foreach ($low as $p) {
                    $response .= "• {$p->title} : **{$p->stock}**\n";
                }
            }
            return $response;
        } catch (\Exception $e) {
            return "❌ Erreur stocks.";
        }
    }

    protected function showOrders(): string
    {
        if (!$this->isTeamMember()) {
            return "🔒 Commande réservée à l'équipe.";
        }

        try {
            $pending = Order::with('user')->where('status', 'pending')->orderBy('created_at')->take(5)->get();

            if ($pending->isEmpty()) {
                return "✅ Aucune commande en attente !";
            }

            $response = "📋 **Commandes en attente** ({$pending->count()})\n\n";
            foreach ($pending as $o) {
                $client = $o->user->name ?? $o->customer_name ?? 'N/A';
                $amount = number_format($o->total_amount, 0, ',', ' ');
                $response .= "• **#{$o->id}** - {$client} - {$amount} FCFA\n";
            }
            return $response;
        } catch (\Exception $e) {
            return "❌ Erreur commandes.";
        }
    }

    protected function showErpStatus(): string
    {
        return $this->handleErpQuery();
    }

    protected function showRecentInteractions(): string
    {
        return $this->handleCrmQuery();
    }

    // === UTILITAIRES ===

    protected function buildSystemPrompt(array $context): string
    {
        $prompt = $this->config['personality']['system_prompt'] ?? '';
        
        $userContext = "\n\n---\n**Contexte utilisateur** :\n";
        $userContext .= "- Rôle : " . $this->userRole . "\n";
        $userContext .= "- Connecté : " . ($this->user ? "Oui ({$this->user->name})" : "Non") . "\n";
        
        if (!empty($context)) {
            $userContext .= "- Page actuelle : " . ($context['page'] ?? 'inconnue') . "\n";
        }

        return $prompt . $userContext;
    }

    protected function isTeamMember(): bool
    {
        return in_array($this->userRole, ['super_admin', 'admin', 'staff']);
    }

    protected function isCreator(): bool
    {
        return in_array($this->userRole, ['createur', 'creator']);
    }

    protected function successResponse(string $message): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'sender' => 'Amira',
        ];
    }

    protected function errorResponse(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function logConversation(string $message, string $response): void
    {
        $history = $this->getConversationHistory();
        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $response];
        $this->saveConversationHistory($history);
        $this->incrementDailyCount();
    }

    protected function checkRateLimit(): bool
    {
        $key = 'amira_rate_' . $this->userId;
        if (Cache::has($key)) return false;
        Cache::put($key, true, $this->config['limits']['rate_limit_seconds'] ?? 1);
        return true;
    }

    protected function checkDailyLimit(): bool
    {
        $key = 'amira_daily_' . $this->userId;
        $count = Cache::get($key, 0);
        $limitKey = $this->isTeamMember() ? 'max_daily_messages_team' : 
                   ($this->user ? 'max_daily_messages_client' : 'max_daily_messages_guest');
        return $count < ($this->config['limits'][$limitKey] ?? 50);
    }

    protected function incrementDailyCount(): void
    {
        $key = 'amira_daily_' . $this->userId;
        Cache::put($key, Cache::get($key, 0) + 1, now()->endOfDay());
    }

    protected function getConversationHistory(): array
    {
        $key = 'amira_history_' . $this->userId;
        $history = Cache::get($key, []);
        $max = ($this->config['limits']['max_context_length'] ?? 10) * 2;
        return count($history) > $max ? array_slice($history, -$max) : $history;
    }

    protected function saveConversationHistory(array $history): void
    {
        Cache::put('amira_history_' . $this->userId, $history, now()->addHours(2));
    }

    public function clearHistory(): void
    {
        Cache::forget('amira_history_' . $this->userId);
    }

    public function getStatus(): array
    {
        return [
            'enabled' => $this->config['enabled'] ?? true,
            'name' => $this->config['name'] ?? 'Amira',
            'version' => $this->config['version'] ?? '4.0.0',
            'provider' => $this->config['ai']['provider'] ?? 'smart',
        ];
    }
}
