<?php

/**
 * Configuration Amira - Assistant IA Complet v4.0
 * RACINE BY GANDA
 * 
 * ═══════════════════════════════════════════════════════════════
 * ACTIVATION IA COMPLÈTE - Ajouter dans .env :
 * ═══════════════════════════════════════════════════════════════
 * 
 * OPTION 1 : OpenAI (GPT-4, GPT-3.5)
 * ─────────────────────────────────────
 * AMIRA_AI_PROVIDER=openai
 * OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx
 * AMIRA_OPENAI_MODEL=gpt-4o-mini
 * 
 * OPTION 2 : Anthropic (Claude)
 * ─────────────────────────────────────
 * AMIRA_AI_PROVIDER=anthropic
 * ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxx
 * AMIRA_ANTHROPIC_MODEL=claude-3-haiku-20240307
 * 
 * OPTION 3 : Groq (Llama, Mixtral - GRATUIT)
 * ─────────────────────────────────────
 * AMIRA_AI_PROVIDER=groq
 * GROQ_API_KEY=gsk_xxxxxxxxxxxxx
 * AMIRA_GROQ_MODEL=llama-3.1-70b-versatile
 * 
 * ═══════════════════════════════════════════════════════════════
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    */
    'enabled' => env('AMIRA_ENABLED', true),
    'name' => env('AMIRA_NAME', 'Amira'),
    'version' => '4.0.0',

    /*
    |--------------------------------------------------------------------------
    | Configuration IA
    |--------------------------------------------------------------------------
    | provider: "openai", "anthropic", "groq", ou "smart" (réponses locales améliorées)
    */
    'ai' => [
        'provider' => env('AMIRA_AI_PROVIDER', 'smart'),
        
        // Modèles par défaut
        'models' => [
            'openai' => env('AMIRA_OPENAI_MODEL', 'gpt-4o-mini'),
            'anthropic' => env('AMIRA_ANTHROPIC_MODEL', 'claude-3-haiku-20240307'),
            'groq' => env('AMIRA_GROQ_MODEL', 'llama-3.1-70b-versatile'),
        ],
        
        'max_tokens' => env('AMIRA_MAX_TOKENS', 800),
        'temperature' => env('AMIRA_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Clés API
    |--------------------------------------------------------------------------
    */
    'api_keys' => [
        'openai' => env('OPENAI_API_KEY'),
        'anthropic' => env('ANTHROPIC_API_KEY'),
        'groq' => env('GROQ_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Personnalité d'Amira - PROMPT SYSTÈME COMPLET
    |--------------------------------------------------------------------------
    */
    'personality' => [
        'system_prompt' => <<<PROMPT
Tu es **Amira**, l'assistante IA officielle de **RACINE BY GANDA**, une marque de mode africaine premium.

## 🎭 TON IDENTITÉ
- **Nom** : Amira (signifie "princesse" en arabe, symbolisant l'élégance)
- **Personnalité** : Chaleureuse, professionnelle, cultivée, fière de l'héritage africain
- **Ton** : Amical mais professionnel, jamais familier
- **Langue** : Français exclusivement
- **Emojis** : Utilise-les avec parcimonie pour exprimer chaleur et enthousiasme

## 🏢 RACINE BY GANDA - INFORMATIONS CLÉS

### La Marque
- **Mission** : Célébrer l'héritage africain à travers une mode raffinée et moderne
- **Vision** : Devenir la référence de la mode africaine premium
- **Valeurs** : Authenticité, Qualité, Durabilité, Fierté culturelle
- **Fondation** : Marque congolaise (Congo-Brazzaville)
- **Slogan** : "L'élégance de nos racines"

### Contact
- **Adresse** : Pointe-Noire, Congo-Brazzaville
- **Téléphone** : +242 06 XXX XX XX
- **Email** : contact@racinebyganda.com
- **Horaires** : Lundi-Samedi, 9h-18h (GMT+1)
- **Réseaux** : Instagram, Facebook, TikTok (@racinebyganda)

### Livraison
- **Pointe-Noire** : GRATUITE, 24-48h ouvrées
- **Brazzaville** : 2-4 jours ouvrés
- **Autres villes Congo** : 3-7 jours ouvrés
- **Afrique Centrale** : 5-10 jours (CEMAC)
- **International** : Sur devis, 7-21 jours

### Paiements Acceptés
- Cartes bancaires (Visa, Mastercard)
- Mobile Money : Airtel Money, MTN MoMo
- Paiement à la livraison (Pointe-Noire uniquement)
- Virement bancaire
- PayPal (international)

### Retours & Échanges
- **Délai** : 14 jours après réception
- **Conditions** : Article non porté, étiquettes intactes, emballage original
- **Retours gratuits** : À Pointe-Noire
- **Remboursement** : Sous 7-10 jours ouvrés

### Collections
- **Femme** : Robes, Ensembles, Tops, Jupes, Accessoires
- **Homme** : Chemises, Pantalons, Costumes, Accessoires
- **Unisexe** : Accessoires, Sacs, Bijoux
- **Sur-mesure** : Service personnalisation disponible

## 📋 TES CAPACITÉS

### Tu PEUX :
- Répondre aux questions sur la marque, produits, livraisons, paiements, retours
- Aider à naviguer sur le site
- Donner des conseils de style et d'entretien des vêtements
- Expliquer les tailles et mesures
- Suivre une commande (si l'utilisateur est connecté)
- Orienter vers le service client pour questions complexes
- Faire des recommandations de produits

### Tu NE DOIS PAS :
- Inventer des prix ou des produits inexistants
- Promettre des délais que tu ne peux pas garantir
- Donner des informations personnelles sur les clients
- Parler de sujets non liés à RACINE BY GANDA
- Être négatif sur la concurrence
- Faire des promesses commerciales non autorisées

## 💬 FORMAT DE RÉPONSE
- Utilise le **Markdown** pour structurer tes réponses
- Sois **concise** mais **complète** (max 150 mots sauf si détails nécessaires)
- Utilise des **listes à puces** pour les informations multiples
- Termine souvent par une **question de suivi** ou une **invitation à l'action**

## 🎯 EXEMPLES DE RÉPONSES

**Salutation** :
"Bonjour ! ✨ Je suis Amira, votre guide chez RACINE BY GANDA. Comment puis-je sublimer votre style aujourd'hui ?"

**Question produit** :
"Nos créations allient tissus africains traditionnels et coupes contemporaines. Chaque pièce est confectionnée avec soin. Que recherchez-vous : une tenue pour une occasion spéciale ou du quotidien ?"

**Question livraison** :
"📦 Pour Pointe-Noire, la livraison est **gratuite** et arrive en 24-48h ! Vers quelle ville souhaitez-vous être livré ?"

Maintenant, réponds à l'utilisateur avec chaleur et professionnalisme !
PROMPT,

        'greeting' => "Bonjour ! ✨ Je suis **Amira**, votre assistante personnelle chez RACINE BY GANDA. Comment puis-je vous aider à trouver la pièce parfaite aujourd'hui ?",
        
        'farewell' => "Merci de votre visite ! 🙏 N'hésitez pas à revenir, je suis là 24h/24. À très bientôt chez RACINE BY GANDA ! ✨",
    ],

    /*
    |--------------------------------------------------------------------------
    | Base de connaissances locale (pour mode "smart")
    |--------------------------------------------------------------------------
    */
    'knowledge_base' => [
        'faq' => [
            'livraison' => [
                'question' => 'Quels sont les délais de livraison ?',
                'answer' => "🚚 **Délais de livraison RACINE BY GANDA**\n\n• **Pointe-Noire** : Gratuite, 24-48h\n• **Brazzaville** : 2-4 jours\n• **Congo** : 3-7 jours\n• **International** : Sur devis\n\nVotre commande est préparée avec soin dans un emballage premium ! 🎁",
            ],
            'retours' => [
                'question' => 'Comment faire un retour ?',
                'answer' => "↩️ **Retours RACINE BY GANDA**\n\n• **14 jours** pour changer d'avis\n• Article non porté, étiquettes intactes\n• Retours **gratuits** à Pointe-Noire\n• Remboursement sous 7-10 jours\n\n📧 Contactez : contact@racinebyganda.com",
            ],
            'paiement' => [
                'question' => 'Quels sont les moyens de paiement ?',
                'answer' => "💳 **Paiements acceptés**\n\n• Carte bancaire (Visa, Mastercard)\n• Mobile Money (Airtel, MTN)\n• Paiement à la livraison (Pointe-Noire)\n• PayPal (international)\n\n🔒 100% sécurisé !",
            ],
            'tailles' => [
                'question' => 'Comment choisir ma taille ?',
                'answer' => "📏 **Guide des tailles**\n\nChaque fiche produit inclut un guide détaillé. En cas de doute :\n\n• Mesurez-vous avec un mètre ruban\n• Comparez avec notre tableau\n• Contactez-nous pour du sur-mesure !\n\n💡 Entre deux tailles ? Prenez la plus grande.",
            ],
            'sur_mesure' => [
                'question' => 'Proposez-vous du sur-mesure ?',
                'answer' => "✂️ **Service Sur-Mesure**\n\nOui ! Nous créons des pièces uniques adaptées à vos mensurations.\n\n• Délai : 2-3 semaines\n• Consultation gratuite\n• Choix des tissus\n\n📧 Demandez un devis : contact@racinebyganda.com",
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'rate_limit_seconds' => env('AMIRA_RATE_LIMIT', 1),
        'max_daily_messages_guest' => env('AMIRA_DAILY_GUEST', 30),
        'max_daily_messages_client' => env('AMIRA_DAILY_CLIENT', 100),
        'max_daily_messages_team' => env('AMIRA_DAILY_TEAM', 500),
        'max_context_length' => env('AMIRA_MAX_CONTEXT', 15),
        'max_message_length' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget UI - Thème RACINE
    |--------------------------------------------------------------------------
    */
    'widget' => [
        'position' => env('AMIRA_POSITION', 'bottom-right'),
        'theme' => [
            'primary' => '#ED5F1E',      // Orange RACINE
            'primary_dark' => '#c44b12',
            'secondary' => '#2C1810',    // Marron foncé
            'gold' => '#D4A574',         // Or/Beige
            'text' => '#1a0f09',
            'background' => '#FAFAF8',
        ],
        'avatar' => '👩🏾‍💼', // Emoji Amira
        'show_on' => ['*'],
        'hide_on' => ['admin/*', 'erp/*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Commandes spéciales (/)
    |--------------------------------------------------------------------------
    */
    'commands' => [
        'enabled' => true,
        'public' => ['/aide', '/help', '/clear', '/reset', '/faq', '/contact', '/livraison'],
        'team_only' => ['/stats', '/stocks', '/commandes', '/contacts', '/analytics', '/erp', '/crm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Intents - Détection d'intention améliorée
    |--------------------------------------------------------------------------
    */
    'intents' => [
        'erp_query' => ['atelier', 'matière', 'tissu', 'wax', 'bobine', 'production', 'stock atelier', 'matière première', 'matières premières', 'fournisseur', 'réappro'],
        'crm_query' => ['interaction', 'interactions', 'client', 'crm', 'contact client', 'dernier client', 'historique client', 'échanges client'],
        'subscription_query' => ['mon plan', 'mon abonnement', 'expire', 'quand finit', 'mon forfait', 'suspendu', 'basique', 'premium', 'avancé', 'abonnements', 'mensuel'],
        'greeting' => ['bonjour', 'salut', 'hello', 'coucou', 'hey', 'bonsoir', 'hi', 'yo', 'bjr'],
        'farewell' => ['au revoir', 'bye', 'à bientôt', 'adieu', 'ciao', 'salut', 'bonne journée', 'bonne soirée'],
        'thanks' => ['merci', 'thanks', 'super', 'parfait', 'génial', 'excellent', 'top', 'cool', 'nickel'],
        'order_status' => ['commande', 'ma commande', 'mes commandes', 'suivi', 'où est ma commande', 'statut commande', 'numéro de commande'],
        'shipping' => ['livraison', 'livrer', 'délai', 'expédition', 'envoi', 'quand', 'combien de temps', 'recevoir'],
        'return' => ['retour', 'échanger', 'rembours', 'échange', 'renvoyer', 'reprendre'],
        'payment' => ['paiement', 'payer', 'carte', 'mobile money', 'airtel', 'mtn', 'paypal', 'virement'],
        'products' => ['produit', 'collection', 'article', 'nouveauté', 'nouveau', 'catalogue', 'vêtement', 'robe', 'chemise'],
        'stock' => ['stock', 'disponible', 'dispo', 'en stock', 'rupture'],
        'contact' => ['contact', 'joindre', 'téléphone', 'email', 'appeler', 'adresse', 'whatsapp'],
        'help' => ['aide', 'help', 'comment', 'quoi faire', 'je comprends pas', 'problème', 'soucis'],
        'price' => ['prix', 'combien', 'coût', 'tarif', 'cher', 'moins cher', 'promotion', 'solde', 'réduction'],
        'size' => ['taille', 'mesure', 'pointure', 'dimension', 's', 'm', 'l', 'xl', 'xxl', 'guide taille'],
        'about' => ['racine', 'marque', 'qui êtes', 'histoire', 'ganda', 'concept', 'valeur', 'mission'],
        'custom' => ['sur mesure', 'personnalisé', 'personnaliser', 'custom', 'unique', 'mes mesures'],
        'complaint' => ['plainte', 'problème', 'pas reçu', 'erreur', 'mauvais', 'déçu', 'insatisfait'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('AMIRA_LOGGING', true),
        'log_channel' => env('AMIRA_LOG_CHANNEL', 'daily'),
        'store_conversations' => env('AMIRA_STORE_CONVERSATIONS', false),
    ],
];
