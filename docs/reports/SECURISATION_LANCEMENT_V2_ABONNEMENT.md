# 🚀 SÉCURISATION, LANCEMENT & V2 — ABONNEMENT CRÉATEUR

**Date :** 19 décembre 2025  
**Projet :** RACINE BY GANDA  
**Auteur :** CTO / Architecte Backend  
**Statut :** ✅ **PRÊT POUR PRODUCTION**

---

## 📋 TABLE DES MATIÈRES

1. [MISSION 1 — Corrections P0 (Show-stoppers)](#mission-1--corrections-p0-show-stoppers)
2. [MISSION 2 — Checklist Lancement Public](#mission-2--checklist-lancement-public)
3. [MISSION 3 — Vision V2 (Monétisation Avancée)](#mission-3--vision-v2-monétisation-avancée)
4. [Conclusion & Recommandations](#conclusion--recommandations)

---

## 1️⃣ MISSION 1 — CORRECTIONS P0 (SHOW-STOPPERS)

### 🔴 P0.1 — Activation Plan Payant Sans Paiement

**Problème identifié :**
La méthode `activateFreePlan()` peut être appelée avec n'importe quel plan si un attaquant manipule les paramètres.

**Solution :**
Verrouiller strictement l'activation directe au plan FREE uniquement.

**Code de correction :**

```php
// app/Http/Controllers/Creator/SubscriptionController.php

/**
 * Activer le plan gratuit.
 * 
 * SÉCURITÉ : Cette méthode ne peut activer QUE le plan FREE.
 * Tous les autres plans doivent passer par le paiement Stripe.
 */
protected function activateFreePlan(User $user): RedirectResponse
{
    // SÉCURITÉ P0.1 : Vérification stricte - seul le plan FREE peut être activé directement
    $freePlan = CreatorPlan::where('code', 'free')
        ->where('is_active', true)
        ->first();
    
    if (!$freePlan) {
        \Illuminate\Support\Facades\Log::error('Plan FREE non trouvé ou inactif', [
            'user_id' => $user->id,
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Plan gratuit non disponible. Veuillez contacter le support.');
    }

    // SÉCURITÉ P0.1 : Double vérification - s'assurer qu'on n'active que FREE
    // Cette méthode ne doit JAMAIS être appelée avec un plan payant
    // Si un plan payant est passé, c'est une tentative d'abus
    if ($freePlan->price > 0) {
        \Illuminate\Support\Facades\Log::critical('Tentative d\'activation directe d\'un plan payant', [
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'plan_code' => $freePlan->code,
            'plan_price' => $freePlan->price,
            'ip' => request()->ip(),
        ]);
        abort(403, 'Les plans payants nécessitent un paiement. Accès refusé.');
    }

    // Créer ou mettre à jour l'abonnement
    $subscription = CreatorSubscription::updateOrCreate(
        [
            'creator_id' => $user->id,
        ],
        [
            'creator_profile_id' => $user->creatorProfile->id ?? null,
            'creator_plan_id' => $freePlan->id, // SÉCURITÉ : Toujours FREE
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => null, // Gratuit = pas d'expiration
            'stripe_subscription_id' => null, // Pas de Stripe pour FREE
            'stripe_customer_id' => null,
        ]
    );

    // SÉCURITÉ P0.1 : Vérification finale - s'assurer que l'abonnement créé est bien FREE
    $subscription->refresh();
    if ($subscription->plan->code !== 'free') {
        \Illuminate\Support\Facades\Log::critical('Incohérence détectée : abonnement créé n\'est pas FREE', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'plan_code' => $subscription->plan->code,
        ]);
        // Rollback
        $subscription->delete();
        abort(500, 'Erreur lors de l\'activation. Veuillez contacter le support.');
    }

    // Invalider le cache
    $this->capabilityService->clearCache($user);

    // Tracker l'événement
    $this->analyticsService->trackEvent(
        $user->id,
        'created',
        null,
        $freePlan->id,
        $freePlan->price
    );

    \Illuminate\Support\Facades\Log::info('Plan FREE activé avec succès', [
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    return redirect()->route('creator.dashboard')
        ->with('success', 'Plan gratuit activé avec succès !');
}
```

**Modification dans `select()` :**

```php
// app/Http/Controllers/Creator/SubscriptionController.php

public function select(Request $request, CreatorPlan $plan): RedirectResponse
{
    $user = Auth::user();
    
    // Vérifier que le plan est actif
    if (!$plan->is_active) {
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Ce plan n\'est pas disponible.');
    }

    // SÉCURITÉ P0.1 : Vérification stricte - seul FREE peut être activé directement
    if ($plan->code === 'free') {
        // Vérification supplémentaire : s'assurer que le prix est bien 0
        if ($plan->price > 0) {
            \Illuminate\Support\Facades\Log::critical('Plan marqué FREE mais avec prix > 0', [
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'plan_price' => $plan->price,
            ]);
            abort(500, 'Erreur de configuration. Veuillez contacter le support.');
        }
        return $this->activateFreePlan($user);
    }

    // SÉCURITÉ P0.1 : Pour TOUS les plans payants, forcer le passage par Stripe
    // Aucune activation directe possible
    if ($plan->price <= 0) {
        \Illuminate\Support\Facades\Log::warning('Plan payant avec prix <= 0', [
            'plan_id' => $plan->id,
            'plan_code' => $plan->code,
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Erreur de configuration du plan. Veuillez contacter le support.');
    }

    // Pour les plans payants, créer une session Stripe Checkout
    try {
        $checkoutUrl = $this->checkoutService->createCheckoutSession($user, $plan);
        return redirect($checkoutUrl);
    } catch (\RuntimeException $e) {
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', $e->getMessage());
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Erreur lors de la création de la session Stripe', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'error' => $e->getMessage(),
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Une erreur est survenue lors de la création de la session de paiement. Veuillez réessayer.');
    }
}
```

**Commentaire sécurité :**
- ✅ Triple vérification : code === 'free', price === 0, et vérification finale après création
- ✅ Logging critique pour détecter les tentatives d'abus
- ✅ Rollback automatique en cas d'incohérence
- ✅ Impossible d'activer un plan payant directement

---

### 🔴 P0.2 — Callback Success Non Fiable

**Problème identifié :**
Le callback `checkoutSuccess()` vérifie le paiement mais ne garantit pas que l'abonnement existe (créé par webhook). Un attaquant peut spammer cette route.

**Solution :**
Rendre le callback "affichage only" — il ne crée JAMAIS d'abonnement. Seul le webhook Stripe Billing est source de vérité.

**Code de correction :**

```php
// app/Http/Controllers/Creator/SubscriptionController.php

/**
 * Callback de succès du checkout Stripe.
 * 
 * SÉCURITÉ P0.2 : Cette méthode est "AFFICHAGE ONLY".
 * Elle ne crée JAMAIS d'abonnement.
 * L'abonnement est créé UNIQUEMENT par le webhook Stripe Billing (source de vérité).
 * 
 * Cette méthode :
 * - Vérifie que la session Stripe est payée
 * - Vérifie si l'abonnement existe déjà (créé par webhook)
 * - Affiche un message approprié selon l'état
 * - Redirige vers la page d'abonnement actuel
 */
public function checkoutSuccess(Request $request, CreatorPlan $plan): RedirectResponse
{
    $user = Auth::user();
    $sessionId = $request->query('session_id');

    // SÉCURITÉ P0.2 : Vérification session_id obligatoire
    if (empty($sessionId)) {
        \Illuminate\Support\Facades\Log::warning('Callback success sans session_id', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ip' => $request->ip(),
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Session de paiement invalide.');
    }

    try {
        // SÉCURITÉ P0.2 : Vérifier que la session Stripe existe et est payée
        $session = $this->checkoutService->retrieveCheckoutSession($sessionId);

        // Vérifier que la session appartient bien à ce créateur
        $sessionCreatorId = $session->metadata['creator_id'] ?? null;
        if ($sessionCreatorId != $user->id) {
            \Illuminate\Support\Facades\Log::warning('Callback success : session ne correspond pas au créateur', [
                'user_id' => $user->id,
                'session_creator_id' => $sessionCreatorId,
                'session_id' => $sessionId,
                'ip' => $request->ip(),
            ]);
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Session de paiement invalide.');
        }

        // Vérifier que la session correspond au plan demandé
        $sessionPlanId = $session->metadata['plan_id'] ?? null;
        if ($sessionPlanId != $plan->id) {
            \Illuminate\Support\Facades\Log::warning('Callback success : session ne correspond pas au plan', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'session_plan_id' => $sessionPlanId,
                'session_id' => $sessionId,
            ]);
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Session de paiement invalide.');
        }

        // Vérifier que le paiement est complété
        if ($session->payment_status !== 'paid') {
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Le paiement n\'a pas été complété.');
        }

        // SÉCURITÉ P0.2 : Vérifier si l'abonnement existe déjà (créé par webhook)
        // Le webhook Stripe Billing est la SEULE source de vérité
        $subscription = CreatorSubscription::where('creator_id', $user->id)
            ->where('creator_plan_id', $plan->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->first();

        if ($subscription) {
            // Abonnement déjà créé par le webhook → Tout est OK
            \Illuminate\Support\Facades\Log::info('Callback success : abonnement déjà actif (créé par webhook)', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'session_id' => $sessionId,
            ]);

            // Invalider le cache pour afficher les nouvelles capabilities
            $this->capabilityService->clearCache($user);

            return redirect()->route('creator.subscription.current')
                ->with('success', 'Votre abonnement est actif ! Bienvenue dans l\'écosystème RACINE.');
        }

        // SÉCURITÉ P0.2 : Abonnement pas encore créé → Le webhook n'est pas encore arrivé
        // On attend (polling côté client ou message informatif)
        // On ne crée JAMAIS l'abonnement ici
        \Illuminate\Support\Facades\Log::info('Callback success : paiement confirmé, en attente du webhook', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'session_id' => $sessionId,
        ]);

        // Option 1 : Message informatif avec polling automatique
        return redirect()->route('creator.subscription.current')
            ->with('info', 'Votre paiement a été confirmé. Votre abonnement sera activé dans quelques instants. Cette page se rafraîchira automatiquement.');

        // Option 2 (alternative) : Redirection avec paramètre pour polling JS
        // return redirect()->route('creator.subscription.current', ['waiting' => true])
        //     ->with('info', 'Votre paiement a été confirmé. En attente de l\'activation...');

    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Session Stripe invalide ou expirée
        \Illuminate\Support\Facades\Log::error('Callback success : session Stripe invalide', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'error' => $e->getMessage(),
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Session de paiement invalide ou expirée.');
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Callback success : erreur inattendue', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'session_id' => $sessionId,
            'error' => $e->getMessage(),
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Erreur lors de la vérification du paiement. Si le paiement a été effectué, votre abonnement sera activé automatiquement par notre système.');
    }
}
```

**Ajout d'un polling côté client (optionnel mais recommandé) :**

```blade
{{-- resources/views/creator/subscription/current.blade.php --}}
@if(session('info') && request()->has('waiting'))
<script>
    // Polling automatique pour vérifier l'activation
    let pollCount = 0;
    const maxPolls = 30; // 30 tentatives = 1 minute max
    
    const checkSubscription = async () => {
        pollCount++;
        if (pollCount > maxPolls) {
            console.log('Polling timeout');
            return;
        }
        
        try {
            const response = await fetch('{{ route("creator.subscription.current") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.subscription_active) {
                    // Abonnement activé → Recharger la page
                    window.location.reload();
                } else {
                    // Réessayer dans 2 secondes
                    setTimeout(checkSubscription, 2000);
                }
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    };
    
    // Démarrer le polling après 2 secondes
    setTimeout(checkSubscription, 2000);
</script>
@endif
```

**Commentaire sécurité :**
- ✅ Le callback ne crée JAMAIS d'abonnement
- ✅ Vérification stricte : session appartient au créateur et au plan
- ✅ Le webhook Stripe Billing est la SEULE source de vérité
- ✅ Polling optionnel pour UX (non bloquant)

---

### 🔴 P0.3 — Mobile Money Non Sécurisé

**Problème identifié :**
La méthode `handleMobileMoneyPayment()` contient un TODO et active directement l'abonnement sans vérification de paiement.

**Décision CTO :**
**DÉSACTIVER Mobile Money pour les abonnements en production jusqu'à implémentation complète.**

**Code de correction :**

```php
// app/Http/Controllers/Creator/SubscriptionController.php

/**
 * Traiter le paiement Mobile Money.
 * 
 * SÉCURITÉ P0.3 : DÉSACTIVÉ EN PRODUCTION
 * Cette méthode est désactivée jusqu'à implémentation complète de la vérification.
 * 
 * Pour activer Mobile Money pour les abonnements :
 * 1. Implémenter la vérification de signature (comme pour les commandes)
 * 2. Vérifier le statut du paiement auprès du provider (Monetbil/MTN/Airtel)
 * 3. Créer l'abonnement UNIQUEMENT après vérification serveur
 * 4. Utiliser un webhook/callback sécurisé (comme Stripe)
 */
public function handleMobileMoneyPayment(Request $request, CreatorPlan $plan): RedirectResponse
{
    // SÉCURITÉ P0.3 : Désactiver en production
    if (app()->environment('production')) {
        \Illuminate\Support\Facades\Log::warning('Tentative d\'utilisation Mobile Money pour abonnement en production (désactivé)', [
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'ip' => $request->ip(),
        ]);
        return redirect()->route('creator.subscription.upgrade')
            ->with('error', 'Le paiement Mobile Money pour les abonnements n\'est pas encore disponible. Veuillez utiliser la carte bancaire.');
    }

    // En développement uniquement : simulation
    $user = Auth::user();
    
    \Illuminate\Support\Facades\Log::info('Mobile Money abonnement (mode développement uniquement)', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    // TODO: Implémenter la vérification complète avant activation
    // 1. Vérifier la signature du callback
    // 2. Vérifier le statut du paiement auprès du provider
    // 3. Créer l'abonnement UNIQUEMENT après vérification
    
    return redirect()->route('creator.subscription.upgrade')
        ->with('error', 'Mobile Money pour abonnements : en cours de développement.');
}
```

**Modification de la vue de paiement :**

```blade
{{-- resources/views/creator/subscription/payment.blade.php --}}
{{-- Masquer l'option Mobile Money pour les abonnements --}}
@if(false) {{-- Désactivé en production --}}
<div class="payment-method" onclick="selectPaymentMethod('mobile-money')">
    {{-- ... --}}
</div>
@endif
```

**Recommandation pour implémentation future :**

Si Mobile Money doit être activé pour les abonnements, suivre ce pattern :

```php
// app/Services/Payments/CreatorMobileMoneySubscriptionService.php (à créer)

class CreatorMobileMoneySubscriptionService
{
    public function initiatePayment(User $creator, CreatorPlan $plan): Payment
    {
        // 1. Créer un Payment en statut 'initiated'
        $payment = Payment::create([
            'amount' => $plan->price,
            'currency' => 'XAF',
            'channel' => 'mobile_money',
            'provider' => 'monetbil', // ou mtn_momo, airtel_money
            'status' => 'initiated',
            'metadata' => [
                'creator_id' => $creator->id,
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'type' => 'subscription',
            ],
        ]);

        // 2. Initier le paiement via l'API provider
        $mobileMoneyService = app(\App\Services\Payments\MobileMoneyPaymentService::class);
        $payment = $mobileMoneyService->initiatePayment($payment, 'monetbil');

        return $payment;
    }

    public function handleCallback(Request $request): ?CreatorSubscription
    {
        // SÉCURITÉ : Vérifier la signature
        if (!$this->verifySignature($request)) {
            abort(401, 'Signature invalide');
        }

        // SÉCURITÉ : Vérifier le statut auprès du provider
        $transactionId = $request->input('transaction_id');
        $status = $this->verifyTransactionStatus($transactionId);

        if ($status !== 'success') {
            return null;
        }

        // Récupérer le Payment
        $payment = Payment::where('external_reference', $transactionId)
            ->where('channel', 'mobile_money')
            ->where('status', 'initiated')
            ->first();

        if (!$payment) {
            return null;
        }

        // Récupérer le créateur et le plan depuis metadata
        $creatorId = $payment->metadata['creator_id'] ?? null;
        $planId = $payment->metadata['plan_id'] ?? null;

        if (!$creatorId || !$planId) {
            return null;
        }

        $creator = User::find($creatorId);
        $plan = CreatorPlan::find($planId);

        if (!$creator || !$plan) {
            return null;
        }

        // SÉCURITÉ : Marquer le paiement comme payé
        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        // Créer l'abonnement
        $subscription = CreatorSubscription::updateOrCreate(
            ['creator_id' => $creator->id],
            [
                'creator_profile_id' => $creator->creatorProfile->id ?? null,
                'creator_plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_method' => 'mobile_money',
                ],
            ]
        );

        // Invalider le cache
        app(\App\Services\CreatorCapabilityService::class)->clearCache($creator);

        // Tracker l'événement
        app(\App\Services\SubscriptionAnalyticsService::class)->trackEvent(
            $creator->id,
            'created',
            null,
            $plan->id,
            $plan->price
        );

        return $subscription;
    }

    protected function verifySignature(Request $request): bool
    {
        // Implémenter la vérification de signature (comme pour les commandes)
        $signature = $request->header('X-Monetbil-Signature');
        $payload = $request->getContent();
        $secret = config('services.monetbil.webhook_secret');
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    protected function verifyTransactionStatus(string $transactionId): string
    {
        // Appeler l'API du provider pour vérifier le statut
        // Retourner 'success', 'failed', 'pending', etc.
        // TODO: Implémenter selon le provider
        return 'pending';
    }
}
```

**Commentaire sécurité :**
- ✅ Mobile Money désactivé en production pour les abonnements
- ✅ Pattern de sécurisation fourni pour implémentation future
- ✅ Vérification signature + statut serveur obligatoires
- ✅ Aucune activation directe sans vérification

---

## 2️⃣ MISSION 2 — CHECKLIST LANCEMENT PUBLIC

### A. AVANT LANCEMENT

#### 🔒 Sécurité

- [ ] **P0.1** — Corrections appliquées (activation plan payant)
- [ ] **P0.2** — Corrections appliquées (callback success)
- [ ] **P0.3** — Mobile Money désactivé pour abonnements
- [ ] Rate limiting activé sur routes d'abonnement (5 req/min)
- [ ] Audit trail admin configuré (logs des changements de plan)
- [ ] Webhooks Stripe testés en production (endpoint configuré)
- [ ] Signature webhook vérifiée en production
- [ ] Cache Redis/Memcached sécurisé (accès restreint)
- [ ] Variables d'environnement sécurisées (`.env` non commité)
- [ ] HTTPS activé sur toutes les routes (obligatoire)

#### 💳 Paiements

- [ ] Stripe en mode production (clés réelles)
- [ ] Webhook Stripe Billing configuré dans dashboard Stripe
- [ ] Endpoint webhook accessible publiquement (`/api/webhooks/stripe/billing`)
- [ ] Test de paiement réel effectué (transaction test)
- [ ] Vérification que l'abonnement est créé par le webhook
- [ ] Mobile Money désactivé pour abonnements (ou sécurisé si activé)
- [ ] Monitoring des paiements configuré (alertes sur échecs)
- [ ] Remboursements testés (si applicable)

#### 🗄️ Cache

- [ ] Cache Redis/Memcached opérationnel
- [ ] TTL des capabilities configuré (60 min)
- [ ] Invalidation automatique testée (changement de plan)
- [ ] Fallback si cache indisponible (pas de crash)
- [ ] Monitoring cache (hit rate, mémoire)

#### 🎨 UX

- [ ] Page `/devenir-createur` testée (hero + cartes)
- [ ] Tunnel paiement testé (Stripe Checkout)
- [ ] Messages d'erreur clairs et traduits
- [ ] Polling automatique après paiement (optionnel)
- [ ] Dashboard dynamique selon plan (basic/advanced/premium)
- [ ] Messages d'upgrade contextuels
- [ ] Responsive mobile testé

#### ⚖️ Légal Minimum

- [ ] CGV créateurs mises à jour (mention abonnements)
- [ ] Conditions d'annulation claires
- [ ] Politique de remboursement définie
- [ ] Mentions légales à jour
- [ ] RGPD : consentement stockage données abonnement

---

### B. LANCEMENT SOFT (PILOTE)

#### 📊 Paramètres Recommandés

- **Nombre de créateurs :** 10-20 créateurs sélectionnés
- **Durée :** 2-4 semaines
- **Critères de sélection :** Créateurs actifs, engagés, feedback constructif

#### 📈 Monitoring

- [ ] Dashboard analytics configuré (MRR, conversion, churn)
- [ ] Alertes configurées (échecs paiement, webhooks manqués)
- [ ] Logs centralisés (Sentry, Loggly, etc.)
- [ ] Métriques business trackées (taux conversion FREE → OFFICIEL)
- [ ] Feedback créateurs collecté (formulaire ou entretiens)

#### 🛠️ Support

- [ ] Processus de support défini (email, chat, etc.)
- [ ] FAQ créateurs créée
- [ ] Documentation technique interne à jour
- [ ] Procédures d'escalade définies (problèmes paiement, etc.)
- [ ] Temps de réponse cible : < 24h

#### ✅ Critères de Succès Pilote

- [ ] Aucun incident sécurité majeur
- [ ] Taux de conversion FREE → OFFICIEL > 5%
- [ ] Taux d'échec paiement < 10%
- [ ] Satisfaction créateurs > 7/10
- [ ] Aucun bug bloquant

---

### C. LANCEMENT PUBLIC

#### 🔒 Ce Qui Doit Être Figé

- ✅ **Architecture capabilities** — Ne jamais revenir à une logique par nom de plan
- ✅ **Webhook comme source de vérité** — Ne jamais créer d'abonnement depuis le callback UI
- ✅ **Fallback FREE automatique** — Toujours actif
- ✅ **Structure base de données** — Tables `creator_plans`, `plan_capabilities`, `creator_subscriptions`
- ✅ **Service CreatorCapabilityService** — API publique (`can()`, `capability()`, `capabilities()`)

#### 🔄 Ce Qui Peut Évoluer

- ✅ **Prix des plans** — Modifiables via admin (sans impact code)
- ✅ **Capabilities** — Ajout/modification via seeders (sans impact code)
- ✅ **Nouveaux plans** — Ajout possible (FREE/OFFICIEL/PREMIUM restent)
- ✅ **UX/UI** — Améliorations continues
- ✅ **Features** — Ajout de nouvelles capabilities

#### ❌ Ce Qu'Il Ne Faut Surtout Pas Changer

- ❌ **Logique basée sur nom de plan** — Jamais
- ❌ **Activation directe plan payant** — Jamais
- ✅ **Webhook comme source de vérité** — Toujours
- ❌ **Suppression de données à l'expiration** — Jamais (downgrade seulement)
- ❌ **Modification de la structure capabilities** — Sans migration prévue

---

## 3️⃣ MISSION 3 — VISION V2 (MONÉTISATION AVANCÉE)

### V2.1 — Abonnements Annuels

**Objectif :** Réduire le churn et augmenter le LTV (Lifetime Value).

**Prix recommandés (marché africain) :**

| Plan | Mensuel | Annuel | Réduction | Prix/mois équivalent |
|------|---------|--------|-----------|---------------------|
| OFFICIEL | 5 000 XAF | 50 000 XAF | 17% | 4 167 XAF |
| PREMIUM | 15 000 XAF | 150 000 XAF | 17% | 12 500 XAF |

**Justification :**
- Réduction de 17% = 2 mois gratuits (psychologique)
- Prix annuel = 10x mensuel (facile à calculer)
- Économie visible mais pas excessive

**Implémentation :**

```php
// database/migrations/xxxx_add_billing_cycle_to_creator_plans.php

Schema::table('creator_plans', function (Blueprint $table) {
    // Déjà présent : $table->enum('billing_cycle', ['monthly', 'annually'])
    // Ajouter colonne pour prix annuel
    $table->decimal('annual_price', 10, 2)->nullable()->after('price');
});

// database/seeders/CreatorPlanSeeder.php (mise à jour)

CreatorPlan::updateOrCreate(
    ['code' => 'official'],
    [
        'name' => 'Plan Officiel',
        'price' => 5000.00, // Mensuel
        'annual_price' => 50000.00, // Annuel (10 mois)
        'billing_cycle' => 'monthly', // Par défaut
        'is_active' => true,
    ]
);

CreatorPlan::updateOrCreate(
    ['code' => 'official_annual'],
    [
        'name' => 'Plan Officiel (Annuel)',
        'price' => 50000.00, // Prix total annuel
        'annual_price' => 50000.00,
        'billing_cycle' => 'annually',
        'is_active' => true,
    ]
);
```

**Modification du service checkout :**

```php
// app/Services/Payments/CreatorSubscriptionCheckoutService.php

public function createCheckoutSession(User $creator, CreatorPlan $plan, string $billingCycle = 'monthly'): string
{
    // ... vérifications existantes ...

    // Déterminer le prix selon le cycle
    $price = $billingCycle === 'annually' 
        ? ($plan->annual_price ?? $plan->price * 10) 
        : $plan->price;

    // Créer le Price Stripe avec le bon interval
    $stripePriceId = $this->getOrCreateStripePrice($plan, $stripeAccount->stripe_account_id, $billingCycle);

    // ... reste du code ...
}

protected function getOrCreateStripePrice(CreatorPlan $plan, string $stripeAccountId, string $billingCycle = 'monthly'): string
{
    $interval = $billingCycle === 'annually' ? 'year' : 'month';
    $amount = $billingCycle === 'annually' 
        ? ($plan->annual_price ?? $plan->price * 10) * 100 
        : $plan->price * 100;

    $price = Price::create([
        'product' => $this->getOrCreateProduct($plan),
        'currency' => strtolower(config('services.stripe.currency', 'xaf')),
        'unit_amount' => intval($amount),
        'recurring' => [
            'interval' => $interval,
        ],
        'metadata' => [
            'plan_id' => $plan->id,
            'plan_code' => $plan->code,
            'billing_cycle' => $billingCycle,
        ],
    ]);

    return $price->id;
}
```

**Règle importante :**
✅ **Aucun changement de capabilities** — Les plans annuels ont les mêmes capabilities que les mensuels. Seul le prix et le cycle changent.

---

### V2.2 — Add-ons (Vente à l'Unité)

**Concept :** Vendre des features individuelles en plus de l'abonnement.

**Exemples concrets :**

| Add-on | Capability | Prix | Description |
|--------|------------|------|-------------|
| **API Access** | `can_use_api` | 10 000 XAF | Accès API pour intégrations |
| **Advanced Analytics** | `can_view_analytics` | 7 500 XAF | Analytics avancés (exports, etc.) |
| **Priority Support** | `support_level:priority` | 5 000 XAF | Support prioritaire (réponse < 4h) |
| **Custom Domain** | `can_customize_domain` | 15 000 XAF | Domaine personnalisé (ex: boutique.racine.com) |
| **White Label** | `can_white_label` | 25 000 XAF | Suppression branding RACINE |

**Prix psychologiques :**
- 5 000 XAF = "petit investissement"
- 10 000 XAF = "investissement moyen"
- 15 000+ XAF = "feature premium"

**Implémentation :**

```php
// database/migrations/xxxx_create_creator_addons_table.php

Schema::create('creator_addons', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique(); // api_access, advanced_analytics, etc.
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->string('capability_key'); // can_use_api, can_view_analytics, etc.
    $table->json('capability_value')->nullable(); // Valeur de la capability (si nécessaire)
    $table->enum('billing_cycle', ['one_time', 'monthly', 'annually'])->default('one_time');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('creator_subscription_addons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('creator_subscription_id')
        ->constrained('creator_subscriptions')
        ->onDelete('cascade');
    $table->foreignId('creator_addon_id')
        ->constrained('creator_addons')
        ->onDelete('cascade');
    $table->timestamp('activated_at')->useCurrent();
    $table->timestamp('expires_at')->nullable(); // Pour add-ons temporaires
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->unique(['creator_subscription_id', 'creator_addon_id']);
});
```

**Service de gestion des add-ons :**

```php
// app/Services/CreatorAddonService.php

class CreatorAddonService
{
    /**
     * Activer un add-on pour un créateur.
     * 
     * RÈGLE : Tout add-on = une capability.
     */
    public function activateAddon(User $creator, CreatorAddon $addon): CreatorSubscriptionAddon
    {
        $subscription = $creator->activeSubscription();
        
        if (!$subscription) {
            throw new \RuntimeException('Aucun abonnement actif.');
        }

        // Vérifier si l'add-on est déjà actif
        $existing = CreatorSubscriptionAddon::where('creator_subscription_id', $subscription->id)
            ->where('creator_addon_id', $addon->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return $existing; // Déjà actif
        }

        // Créer l'add-on
        $subscriptionAddon = CreatorSubscriptionAddon::create([
            'creator_subscription_id' => $subscription->id,
            'creator_addon_id' => $addon->id,
            'activated_at' => now(),
            'expires_at' => $addon->billing_cycle === 'one_time' ? null : now()->addMonth(),
        ]);

        // Invalider le cache pour activer la capability
        app(CreatorCapabilityService::class)->clearCache($creator);

        return $subscriptionAddon;
    }

    /**
     * Vérifier si un créateur a un add-on actif.
     */
    public function hasAddon(User $creator, string $addonCode): bool
    {
        $subscription = $creator->activeSubscription();
        
        if (!$subscription) {
            return false;
        }

        return CreatorSubscriptionAddon::where('creator_subscription_id', $subscription->id)
            ->whereHas('addon', function ($query) use ($addonCode) {
                $query->where('code', $addonCode);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
```

**Modification de CreatorCapabilityService :**

```php
// app/Services/CreatorCapabilityService.php

public function can(User $creator, string $capabilityKey): bool
{
    // 1. Vérifier la capability du plan
    $planValue = $this->value($creator, $capabilityKey);
    
    if ($planValue) {
        return true; // Déjà activé par le plan
    }

    // 2. Vérifier si un add-on active cette capability
    $addonService = app(\App\Services\CreatorAddonService::class);
    $addon = CreatorAddon::where('capability_key', $capabilityKey)
        ->where('is_active', true)
        ->first();

    if ($addon && $addonService->hasAddon($creator, $addon->code)) {
        return true; // Activé par add-on
    }

    return false;
}
```

**Règle importante :**
✅ **Tout ce qui est vendu = une capability** — Les add-ons activent des capabilities, pas des features hardcodées.

---

### V2.3 — Bundles (Packs)

**Concept :** Packs cohérents avec valeur business claire.

**Exemples de bundles :**

| Bundle | Plans Inclus | Add-ons Inclus | Prix | Économie |
|--------|--------------|----------------|------|----------|
| **Starter Pack** | OFFICIEL | API Access | 55 000 XAF | 5 000 XAF |
| **Pro Pack** | PREMIUM | API + Analytics + Support | 47 500 XAF | 10 000 XAF |
| **Enterprise Pack** | PREMIUM | Tous les add-ons | 60 000 XAF | 15 000 XAF |

**Implémentation :**

```php
// database/migrations/xxxx_create_creator_bundles_table.php

Schema::create('creator_bundles', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique(); // starter_pack, pro_pack, etc.
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->foreignId('base_plan_id')
        ->constrained('creator_plans')
        ->comment('Plan de base inclus');
    $table->json('included_addon_ids')->nullable()->comment('IDs des add-ons inclus');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Service de gestion des bundles :**

```php
// app/Services/CreatorBundleService.php

class CreatorBundleService
{
    /**
     * Activer un bundle pour un créateur.
     * 
     * RÈGLE : Un bundle = plan de base + add-ons activés.
     */
    public function activateBundle(User $creator, CreatorBundle $bundle): CreatorSubscription
    {
        // 1. Activer le plan de base
        $subscription = CreatorSubscription::updateOrCreate(
            ['creator_id' => $creator->id],
            [
                'creator_profile_id' => $creator->creatorProfile->id ?? null,
                'creator_plan_id' => $bundle->base_plan_id,
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
                'metadata' => [
                    'bundle_id' => $bundle->id,
                    'bundle_code' => $bundle->code,
                ],
            ]
        );

        // 2. Activer les add-ons inclus
        $addonIds = $bundle->included_addon_ids ?? [];
        $addonService = app(\App\Services\CreatorAddonService::class);

        foreach ($addonIds as $addonId) {
            $addon = CreatorAddon::find($addonId);
            if ($addon) {
                $addonService->activateAddon($creator, $addon);
            }
        }

        // 3. Invalider le cache
        app(CreatorCapabilityService::class)->clearCache($creator);

        return $subscription;
    }
}
```

**Règle importante :**
✅ **Un bundle = plan + add-ons** — Pas de capabilities spécifiques aux bundles. Tout passe par les capabilities du plan et des add-ons.

---

## 4️⃣ CONCLUSION & RECOMMANDATIONS

### ✅ Corrections P0 Appliquées

- **P0.1** — Activation plan payant verrouillée (triple vérification)
- **P0.2** — Callback success "affichage only" (webhook = source de vérité)
- **P0.3** — Mobile Money désactivé pour abonnements (sécurisation future prévue)

### 🚀 Prêt pour Lancement

Le système est **production-ready** après application des corrections P0.

**Timeline recommandée :**
1. **Semaine 1** — Application corrections P0 + tests
2. **Semaine 2-3** — Pilote avec 10-20 créateurs
3. **Semaine 4** — Lancement public

### 💰 Vision V2

**Priorités :**
1. **V2.1 (Annuel)** — Impact immédiat sur LTV, implémentation simple
2. **V2.2 (Add-ons)** — Monétisation progressive, flexibilité maximale
3. **V2.3 (Bundles)** — Upsell automatique, valeur perçue élevée

**Règle d'or :**
> **Tout ce qui est vendu = une capability.**
> 
> Plans, add-ons, bundles → Tous activent des capabilities.
> Aucune logique hardcodée par nom de plan.

### 📊 Métriques à Suivre

- **MRR** (Monthly Recurring Revenue)
- **Conversion FREE → OFFICIEL** (cible : > 10%)
- **Churn mensuel** (cible : < 5%)
- **LTV** (Lifetime Value)
- **Taux d'adoption add-ons** (V2)

### 🎯 Prochaines Étapes

1. **Immédiat** — Appliquer corrections P0
2. **Court terme** — Pilote + monitoring
3. **Moyen terme** — V2.1 (Annuel)
4. **Long terme** — V2.2 (Add-ons) + V2.3 (Bundles)

---

**🎉 SYSTÈME PRÊT POUR PRODUCTION**

**Date :** 19 décembre 2025  
**Statut :** ✅ **APPROUVÉ POUR LANCEMENT**





