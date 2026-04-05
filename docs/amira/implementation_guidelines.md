# GUIDELINES D'IMPLÉMENTATION — AMIRA

**Référence** : [Charte Officielle](./charte_officielle_amira.md) | [Scénarios](./scenarios_reponses.md)  
**Statut** : `PRODUCTION-GRADE`

---

## 1. ARCHITECTURE TECHNIQUE

### 1.1 Principe de séparation

```
┌─────────────────────────────────────────────────┐
│                   FRONTEND                      │
│              (Ce que voit le client)            │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │            AMIRA (Visible)              │   │
│  │  - Widget chat                          │   │
│  │  - Réponses simples                     │   │
│  │  - Ton professionnel                    │   │
│  │  - Pas de jargon technique              │   │
│  └─────────────────────────────────────────┘   │
│                      ▲                          │
│                      │ API simple               │
│                      │                          │
└──────────────────────┼──────────────────────────┘
                       │
┌──────────────────────┼──────────────────────────┐
│                      ▼                          │
│                   BACKEND                       │
│            (Intelligence cachée)                │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │      IA DÉCISIONNELLE (Invisible)       │   │
│  │  - Algorithmes de recommandation        │   │
│  │  - Analyse comportementale              │   │
│  │  - Optimisation des suggestions         │   │
│  │  - Scoring et prédictions               │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
└─────────────────────────────────────────────────┘
```

**RÈGLE ABSOLUE** : Amira ne connaît pas et ne mentionne JAMAIS l'IA décisionnelle.

---

### 1.2 Stack technique recommandée

| Composant | Technologie | Justification |
|-----------|-------------|---------------|
| **Widget Frontend** | Vue.js / Alpine.js | Léger, réactif, intégrable |
| **API Chatbot** | Laravel Controller dédié | Isolation claire, testable |
| **Traitement NLP** | Service externe (OpenAI, Claude) | Qualité des réponses |
| **Base de connaissances** | JSON / Database | Réponses pré-validées |
| **Logging** | Laravel Log / Sentry | Monitoring des interactions |

---

## 2. STRUCTURE DU CODE

### 2.1 Organisation des fichiers

```
app/
├── Http/
│   └── Controllers/
│       └── Chatbot/
│           ├── AmiraController.php          # Point d'entrée API
│           └── AmiraResponseValidator.php   # Validation des réponses
│
├── Services/
│   └── Amira/
│       ├── AmiraService.php                 # Logique métier
│       ├── ResponseGenerator.php            # Génération de réponses
│       ├── ContextAnalyzer.php              # Analyse du contexte client
│       └── KnowledgeBase.php                # Base de connaissances
│
├── Rules/
│   └── Amira/
│       ├── ToneValidator.php                # Validation du ton
│       └── ScopeValidator.php               # Validation du périmètre
│
└── Models/
    └── AmiraConversation.php                # Historique des conversations

resources/
├── js/
│   └── components/
│       └── AmiraWidget.vue                  # Widget chat frontend
│
└── views/
    └── components/
        └── amira-chat.blade.php             # Composant Blade

database/
└── migrations/
    └── create_amira_conversations_table.php

config/
└── amira.php                                # Configuration centralisée

storage/
└── amira/
    ├── knowledge_base.json                  # Base de connaissances
    └── prohibited_patterns.json             # Patterns interdits
```

---

### 2.2 Configuration (`config/amira.php`)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Amira - Configuration
    |--------------------------------------------------------------------------
    | Configuration officielle du chatbot Amira selon la charte production-grade
    */

    'enabled' => env('AMIRA_ENABLED', true),

    // Périmètre d'apparition (selon charte section 5)
    'visibility' => [
        'allowed_routes' => [
            'shop.*',           // Boutique
            'products.*',       // Fiches produits
            'cart.*',           // Panier
            'orders.*',         // Commandes client
            'support.*',        // Support client
        ],
        'forbidden_routes' => [
            'admin.*',          // Back-office admin
            'creator.*',        // Espace créateurs
            'dashboard.*',      // Dashboards internes
        ],
    ],

    // Ton et langage (selon charte section 6)
    'tone' => [
        'style' => 'professional',
        'max_response_length' => 200, // Phrases courtes
        'avoid_jargon' => true,
        'avoid_technical_terms' => true,
    ],

    // Interdictions (selon charte section 4)
    'prohibited_keywords' => [
        'algorithme',
        'système',
        'optimisation',
        'intelligence artificielle',
        'IA',
        'machine learning',
        'analyse',
        'détection',
        'prédiction',
        'scoring',
    ],

    // Limites fonctionnelles
    'limits' => [
        'max_conversation_turns' => 10,
        'redirect_to_human_after' => 3, // Tentatives avant redirection
        'session_timeout' => 1800, // 30 minutes
    ],

    // Intégration NLP
    'nlp' => [
        'provider' => env('AMIRA_NLP_PROVIDER', 'openai'),
        'model' => env('AMIRA_NLP_MODEL', 'gpt-4'),
        'api_key' => env('AMIRA_NLP_API_KEY'),
        'temperature' => 0.3, // Réponses prévisibles et cohérentes
        'max_tokens' => 150,
    ],

    // Monitoring
    'monitoring' => [
        'log_conversations' => true,
        'track_satisfaction' => true,
        'alert_on_prohibited_response' => true,
    ],
];
```

---

## 3. IMPLÉMENTATION DES COMPOSANTS

### 3.1 Controller (`AmiraController.php`)

```php
<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Services\Amira\AmiraService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AmiraController extends Controller
{
    public function __construct(
        private AmiraService $amiraService
    ) {}

    /**
     * Point d'entrée principal du chatbot
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'conversation_id' => 'nullable|uuid',
            'context' => 'nullable|array',
        ]);

        // Vérification du périmètre (selon charte section 5)
        if (!$this->isAllowedRoute($request)) {
            return response()->json([
                'error' => 'Amira n\'est pas disponible sur cette page.'
            ], 403);
        }

        try {
            $response = $this->amiraService->generateResponse(
                message: $validated['message'],
                conversationId: $validated['conversation_id'] ?? null,
                context: $validated['context'] ?? []
            );

            return response()->json([
                'response' => $response['message'],
                'conversation_id' => $response['conversation_id'],
                'suggestions' => $response['suggestions'] ?? [],
                'redirect_to_human' => $response['redirect_to_human'] ?? false,
            ]);

        } catch (\Exception $e) {
            \Log::error('Amira error', [
                'message' => $validated['message'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'response' => 'Je rencontre un problème technique. Veuillez réessayer ou contacter le support.',
                'error' => true
            ], 500);
        }
    }

    /**
     * Vérifie si Amira est autorisée sur cette route
     */
    private function isAllowedRoute(Request $request): bool
    {
        $currentRoute = $request->route()->getName();
        $allowedRoutes = config('amira.visibility.allowed_routes');
        $forbiddenRoutes = config('amira.visibility.forbidden_routes');

        // Vérifier les routes interdites en priorité
        foreach ($forbiddenRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return false;
            }
        }

        // Vérifier les routes autorisées
        foreach ($allowedRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    }
}
```

---

### 3.2 Service Principal (`AmiraService.php`)

```php
<?php

namespace App\Services\Amira;

use App\Models\AmiraConversation;
use App\Services\Amira\ResponseGenerator;
use App\Services\Amira\ContextAnalyzer;
use App\Rules\Amira\ToneValidator;
use App\Rules\Amira\ScopeValidator;
use Illuminate\Support\Str;

class AmiraService
{
    public function __construct(
        private ResponseGenerator $responseGenerator,
        private ContextAnalyzer $contextAnalyzer,
        private ToneValidator $toneValidator,
        private ScopeValidator $scopeValidator
    ) {}

    /**
     * Génère une réponse validée selon la charte
     */
    public function generateResponse(
        string $message,
        ?string $conversationId = null,
        array $context = []
    ): array {
        // 1. Récupérer ou créer la conversation
        $conversation = $this->getOrCreateConversation($conversationId);

        // 2. Analyser le contexte
        $enrichedContext = $this->contextAnalyzer->analyze($message, $context);

        // 3. Générer la réponse brute
        $rawResponse = $this->responseGenerator->generate($message, $enrichedContext);

        // 4. VALIDATION CRITIQUE : Vérifier la conformité à la charte
        $validatedResponse = $this->validateResponse($rawResponse);

        // 5. Enregistrer l'interaction
        $this->logInteraction($conversation, $message, $validatedResponse);

        // 6. Déterminer si redirection vers humain nécessaire
        $shouldRedirect = $this->shouldRedirectToHuman($conversation, $validatedResponse);

        return [
            'message' => $validatedResponse['text'],
            'conversation_id' => $conversation->id,
            'suggestions' => $validatedResponse['suggestions'] ?? [],
            'redirect_to_human' => $shouldRedirect,
        ];
    }

    /**
     * VALIDATION CRITIQUE : Vérifie que la réponse respecte la charte
     */
    private function validateResponse(array $rawResponse): array
    {
        $text = $rawResponse['text'];

        // 1. Vérifier le ton (section 6 de la charte)
        if (!$this->toneValidator->validate($text)) {
            \Log::warning('Amira tone violation detected', ['response' => $text]);
            $text = $this->toneValidator->correct($text);
        }

        // 2. Vérifier le périmètre (section 4 de la charte - interdictions)
        if (!$this->scopeValidator->validate($text)) {
            \Log::error('Amira scope violation detected', ['response' => $text]);
            
            // RÉPONSE DE SECOURS si violation détectée
            $text = "Je ne peux pas répondre à cette question. Puis-je vous aider avec autre chose ?";
            
            // Alerte critique
            if (config('amira.monitoring.alert_on_prohibited_response')) {
                \Log::critical('AMIRA CHARTER VIOLATION', [
                    'original_response' => $rawResponse['text'],
                    'timestamp' => now(),
                ]);
            }
        }

        // 3. Vérifier la longueur (phrases courtes)
        $maxLength = config('amira.tone.max_response_length');
        if (strlen($text) > $maxLength) {
            $text = $this->truncateGracefully($text, $maxLength);
        }

        return [
            'text' => $text,
            'suggestions' => $rawResponse['suggestions'] ?? [],
            'validated' => true,
        ];
    }

    /**
     * Détermine si redirection vers support humain nécessaire
     */
    private function shouldRedirectToHuman(AmiraConversation $conversation, array $response): bool
    {
        // Critères de redirection (section 7 de la charte)
        $turnCount = $conversation->turns()->count();
        $maxTurns = config('amira.limits.redirect_to_human_after');

        // Si trop de tours sans résolution
        if ($turnCount >= $maxTurns) {
            return true;
        }

        // Si la réponse contient des marqueurs de limite
        $limitMarkers = [
            'je ne peux pas',
            'je ne suis pas en mesure',
            'contactez le support',
        ];

        foreach ($limitMarkers as $marker) {
            if (stripos($response['text'], $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    // ... autres méthodes helper
}
```

---

### 3.3 Validateurs de Charte

#### `ToneValidator.php`

```php
<?php

namespace App\Rules\Amira;

class ToneValidator
{
    /**
     * Vérifie que le ton respecte la charte (section 6)
     */
    public function validate(string $text): bool
    {
        // Patterns de ton inapproprié
        $inappropriatePatterns = [
            '/!{2,}/',                    // Trop d'exclamations
            '/😀|😊|🎉/',                 // Emojis trop enthousiastes
            '/super|génial|incroyable/i', // Langage trop familier
            '/mon intelligence/i',        // Auto-référence IA
            '/grâce à (moi|mon)/i',      // Auto-promotion
        ];

        foreach ($inappropriatePatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Corrige le ton si nécessaire
     */
    public function correct(string $text): string
    {
        // Supprimer exclamations multiples
        $text = preg_replace('/!{2,}/', '.', $text);
        
        // Supprimer emojis
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        
        // Remplacer termes trop familiers
        $replacements = [
            'super' => 'bien',
            'génial' => 'parfait',
            'incroyable' => 'intéressant',
        ];
        
        $text = str_ireplace(array_keys($replacements), array_values($replacements), $text);
        
        return $text;
    }
}
```

#### `ScopeValidator.php`

```php
<?php

namespace App\Rules\Amira;

class ScopeValidator
{
    /**
     * Vérifie que la réponse respecte le périmètre (section 4 - interdictions)
     */
    public function validate(string $text): bool
    {
        $prohibitedKeywords = config('amira.prohibited_keywords');

        foreach ($prohibitedKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                \Log::warning('Prohibited keyword detected in Amira response', [
                    'keyword' => $keyword,
                    'response' => $text
                ]);
                return false;
            }
        }

        // Patterns interdits spécifiques (section 4)
        $prohibitedPatterns = [
            '/système (a|de|détecte)/i',
            '/algorithme (de|qui|va)/i',
            '/(mon|notre) intelligence/i',
            '/je vais (optimiser|améliorer|analyser)/i',
            '/grâce à (mon|notre) (IA|algorithme|système)/i',
        ];

        foreach ($prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return false;
            }
        }

        return true;
    }
}
```

---

## 4. WIDGET FRONTEND

### 4.1 Composant Vue.js (`AmiraWidget.vue`)

```vue
<template>
  <div v-if="isVisible" class="amira-widget">
    <!-- Bouton d'ouverture -->
    <button 
      v-if="!isOpen" 
      @click="toggleChat"
      class="amira-trigger"
      aria-label="Ouvrir le chat Amira"
    >
      <svg><!-- Icon chat --></svg>
    </button>

    <!-- Fenêtre de chat -->
    <div v-if="isOpen" class="amira-chat-window">
      <!-- Header -->
      <div class="amira-header">
        <h3>Amira - Assistance</h3>
        <button @click="toggleChat" aria-label="Fermer">×</button>
      </div>

      <!-- Messages -->
      <div class="amira-messages" ref="messagesContainer">
        <div 
          v-for="(msg, index) in messages" 
          :key="index"
          :class="['amira-message', msg.sender]"
        >
          <p>{{ msg.text }}</p>
          <span class="amira-time">{{ formatTime(msg.timestamp) }}</span>
        </div>

        <!-- Suggestions -->
        <div v-if="suggestions.length" class="amira-suggestions">
          <button 
            v-for="(suggestion, index) in suggestions"
            :key="index"
            @click="sendMessage(suggestion)"
            class="amira-suggestion-btn"
          >
            {{ suggestion }}
          </button>
        </div>

        <!-- Redirection vers humain -->
        <div v-if="shouldRedirectToHuman" class="amira-redirect">
          <p>Pour mieux vous aider, je vous mets en relation avec notre support :</p>
          <a href="mailto:support@racinebyganda.com" class="amira-support-link">
            Contacter le support
          </a>
        </div>
      </div>

      <!-- Input -->
      <div class="amira-input-container">
        <input 
          v-model="userInput"
          @keyup.enter="sendMessage()"
          placeholder="Posez votre question..."
          :disabled="isLoading"
        />
        <button 
          @click="sendMessage()"
          :disabled="!userInput.trim() || isLoading"
        >
          Envoyer
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AmiraWidget',
  
  data() {
    return {
      isOpen: false,
      isVisible: true,
      isLoading: false,
      userInput: '',
      messages: [],
      suggestions: [],
      conversationId: null,
      shouldRedirectToHuman: false,
    };
  },

  mounted() {
    this.checkVisibility();
    this.addWelcomeMessage();
  },

  methods: {
    /**
     * Vérifie si Amira doit être visible sur cette page
     */
    checkVisibility() {
      const currentRoute = window.location.pathname;
      const forbiddenPaths = ['/admin', '/creator', '/dashboard'];
      
      this.isVisible = !forbiddenPaths.some(path => currentRoute.startsWith(path));
    },

    /**
     * Message de bienvenue (conforme à la charte)
     */
    addWelcomeMessage() {
      this.messages.push({
        sender: 'amira',
        text: 'Bonjour, je peux vous aider à trouver un produit ou répondre à vos questions.',
        timestamp: new Date(),
      });

      this.suggestions = [
        'Trouver une robe',
        'Suivre ma commande',
        'Politique de retour',
      ];
    },

    /**
     * Envoyer un message
     */
    async sendMessage(text = null) {
      const message = text || this.userInput.trim();
      if (!message) return;

      // Ajouter le message utilisateur
      this.messages.push({
        sender: 'user',
        text: message,
        timestamp: new Date(),
      });

      this.userInput = '';
      this.isLoading = true;

      try {
        const response = await fetch('/api/amira/chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            message,
            conversation_id: this.conversationId,
            context: this.getContext(),
          }),
        });

        const data = await response.json();

        // Ajouter la réponse d'Amira
        this.messages.push({
          sender: 'amira',
          text: data.response,
          timestamp: new Date(),
        });

        this.conversationId = data.conversation_id;
        this.suggestions = data.suggestions || [];
        this.shouldRedirectToHuman = data.redirect_to_human || false;

        this.scrollToBottom();

      } catch (error) {
        console.error('Amira error:', error);
        this.messages.push({
          sender: 'amira',
          text: 'Je rencontre un problème technique. Veuillez contacter le support.',
          timestamp: new Date(),
        });
      } finally {
        this.isLoading = false;
      }
    },

    /**
     * Récupérer le contexte de la page
     */
    getContext() {
      return {
        page: window.location.pathname,
        product_id: document.querySelector('[data-product-id]')?.dataset.productId,
        cart_total: document.querySelector('[data-cart-total]')?.dataset.cartTotal,
      };
    },

    toggleChat() {
      this.isOpen = !this.isOpen;
    },

    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer;
        container.scrollTop = container.scrollHeight;
      });
    },

    formatTime(date) {
      return new Date(date).toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    },
  },
};
</script>

<style scoped>
/* Styles minimalistes et professionnels */
/* Conforme à la charte : discret, utile, non intrusif */
</style>
```

---

## 5. TESTS ET VALIDATION

### 5.1 Tests unitaires

```php
<?php

namespace Tests\Unit\Services\Amira;

use Tests\TestCase;
use App\Services\Amira\AmiraService;
use App\Rules\Amira\ScopeValidator;

class AmiraCharterComplianceTest extends TestCase
{
    /**
     * Test : Amira ne doit JAMAIS mentionner l'IA décisionnelle
     */
    public function test_amira_never_mentions_decisional_ai()
    {
        $prohibitedResponses = [
            "Grâce à mon algorithme, je vous recommande...",
            "Mon système a détecté que...",
            "Je vais optimiser votre expérience...",
            "Notre intelligence artificielle analyse...",
        ];

        $validator = new ScopeValidator();

        foreach ($prohibitedResponses as $response) {
            $this->assertFalse(
                $validator->validate($response),
                "Response should be rejected: {$response}"
            );
        }
    }

    /**
     * Test : Le ton doit être professionnel et simple
     */
    public function test_amira_tone_is_professional()
    {
        $acceptableResponses = [
            "Je peux vous aider à trouver un produit.",
            "Voici où suivre votre commande.",
            "Pour ce point, je vous mets en relation avec le support.",
        ];

        $toneValidator = new \App\Rules\Amira\ToneValidator();

        foreach ($acceptableResponses as $response) {
            $this->assertTrue(
                $toneValidator->validate($response),
                "Response should be accepted: {$response}"
            );
        }
    }

    /**
     * Test : Amira assume ses limites
     */
    public function test_amira_acknowledges_limits()
    {
        $service = app(AmiraService::class);

        $response = $service->generateResponse(
            message: "Quelle est votre stratégie de développement durable ?",
            conversationId: null,
            context: []
        );

        $this->assertStringContainsString(
            'je ne peux pas',
            strtolower($response['message']),
            "Amira should acknowledge her limits"
        );
    }
}
```

---

## 6. MONITORING ET AMÉLIORATION CONTINUE

### 6.1 Métriques à suivre

| Métrique | Objectif | Action si écart |
|----------|----------|-----------------|
| **Taux de résolution** | > 70% | Enrichir la base de connaissances |
| **Taux de redirection humain** | < 30% | Améliorer les réponses niveau 1 |
| **Violations de charte** | 0 | Alerte critique + correction immédiate |
| **Satisfaction client** | > 4/5 | Analyser les conversations insatisfaisantes |
| **Temps de réponse** | < 2s | Optimiser l'API NLP |

### 6.2 Dashboard de monitoring

```php
// Route admin pour monitoring (JAMAIS visible côté client)
Route::get('/admin/amira/monitoring', [AmiraMonitoringController::class, 'dashboard'])
    ->middleware(['auth', 'admin']);
```

---

## 7. CHECKLIST DE DÉPLOIEMENT

Avant de mettre Amira en production :

- [ ] Configuration `config/amira.php` validée
- [ ] Routes autorisées/interdites vérifiées
- [ ] Base de connaissances complète
- [ ] Validateurs de charte actifs
- [ ] Tests de conformité passés (100%)
- [ ] Widget frontend testé sur toutes les pages autorisées
- [ ] Widget absent sur toutes les pages interdites
- [ ] Monitoring configuré
- [ ] Alertes critiques activées
- [ ] Documentation technique complète
- [ ] Formation équipe support sur les limites d'Amira

---

**RAPPEL FINAL**

Amira est un **outil de conversion**, pas une démonstration technologique.

Chaque ligne de code doit servir cet objectif unique :
**Réduire la friction entre l'intention du client et l'achat.**

Tout le reste est hors périmètre.
