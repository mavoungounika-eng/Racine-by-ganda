# GUIDE CONFIGURATION STRIPE – RACINE-BACKEND

Ce guide explique comment configurer, tester et passer en production le paiement par carte bancaire (Stripe Checkout) pour RACINE-BACKEND.

---

## 1. Création du compte Stripe

1. Aller sur https://stripe.com
2. Créer un compte (gratuit).
3. Dans le Dashboard :
   - Activer le **mode Test**.
   - Aller dans **Developers → API keys**.

Tu auras :
- **Publishable key** (pk_test_…)
- **Secret key** (sk_test_…)

---

## 2. Configuration `.env` (mode TEST)

Dans le fichier `.env` :

```env
STRIPE_ENABLED=true
STRIPE_PUBLIC_KEY=pk_test_VOTRE_CLE
STRIPE_SECRET_KEY=sk_test_VOTRE_CLE
STRIPE_WEBHOOK_SECRET=null   # ou laisser vide pour l'instant
STRIPE_CURRENCY=XAF
```

Ensuite :

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 3. Routes Stripe dans `routes/web.php`

Vérifier que les routes suivantes existent :

```php
use App\Http\Controllers\Front\CardPaymentController;

Route::post('/checkout/card/pay', [CardPaymentController::class, 'pay'])
    ->name('checkout.card.pay');

Route::get('/checkout/card/{order}/success', [CardPaymentController::class, 'success'])
    ->name('checkout.card.success');

Route::get('/checkout/card/{order}/cancel', [CardPaymentController::class, 'cancel'])
    ->name('checkout.card.cancel');

Route::post('/payment/card/webhook', [CardPaymentController::class, 'webhook'])
    ->name('payment.card.webhook');
```

---

## 4. Service Stripe – `CardPaymentService`

Le service existe déjà dans `app/Services/Payments/CardPaymentService.php` avec :
- `createCheckoutSession(Order $order)` - Crée la session Stripe
- `handleWebhook($payload, $signature)` - Traite les webhooks

Configuration dans `config/stripe.php` :

```php
return [
    'enabled' => env('STRIPE_ENABLED', false),
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'XAF'),
];
```

---

## 5. Contrôleur `CardPaymentController`

Le contrôleur existe déjà dans `app/Http/Controllers/Front/CardPaymentController.php` avec :

- `pay()` - Initie le paiement et redirige vers Stripe
- `success()` - Page de succès après paiement
- `cancel()` - Page d'annulation
- `webhook()` - Reçoit les notifications Stripe

---

## 6. Cartes de test

En mode test, utiliser :

* Numéro : `4242 4242 4242 4242`
* Date : n'importe quelle date future (12/34)
* CVC : 123
* Code postal : 00000

**Autres cartes de test :**
- ❌ Refusée : `4000 0000 0000 0002`
- 🔐 3D Secure : `4000 0027 6000 3184`

---

## 7. Scénario de test complet

### Flux de test pas à pas :

1. **Front :**
   - Aller sur `/`
   - Aller dans `/boutique`
   - Ajouter un produit au panier (bouton "Ajouter au panier")

2. **Panier :**
   - Aller sur `/panier`
   - Vérifier les articles, quantités, total

3. **Checkout :**
   - Aller sur `/checkout`
   - Renseigner les infos client (nom, email, téléphone, adresse)
   - Sélectionner "Carte Bancaire"
   - Valider → création de la `Order` en base (`status = pending`, `payment_status = pending`)

4. **Stripe Checkout :**
   - Redirection vers l'URL Stripe
   - Saisir la carte test `4242 4242 4242 4242`
   - Date : 12/34, CVC : 123
   - Valider

5. **Retour sur ton site :**
   - Stripe redirige vers `checkout.card.success` (succès)
   - Le webhook Stripe est appelé en parallèle sur `/payment/card/webhook`

6. **Côté base de données :**
   - Table `payments` :
     - `status` passe de `initiated` → `paid`
     - `paid_at` renseigné
   - Table `orders` :
     - `payment_status` passe à `paid`
     - `status` passe à `paid`

7. **Côté admin :**
   - Aller sur `/admin/orders/{id}`
   - Voir la commande marquée **payée**
   - Voir le paiement attaché dans `payments` (channel `card`, provider `stripe`)

---

## 8. Checklist de vérification du tunnel de paiement

### ✅ A. Migrations & modèles

- Table `orders` contient :
  - `payment_status` (`pending`, `paid`, `failed`…)
  - `total_amount`
  - `qr_token`
- Table `payments` contient :
  - `order_id`, `amount`, `currency`
  - `channel` (`card`)
  - `provider` (`stripe`)
  - `external_reference` (id session Stripe)
  - `status` (`initiated`, `paid`, `failed`)
  - `paid_at`, `payload`, `metadata`

### ✅ B. Relations Eloquent

Dans `Order` :

```php
public function payments()
{
    return $this->hasMany(Payment::class);
}
```

Dans `Payment` :

```php
public function order()
{
    return $this->belongsTo(Order::class);
}
```

### ✅ C. Routes

* `/checkout/card/pay` → `CardPaymentController@pay` (POST)
* `/checkout/card/{order}/success` → `CardPaymentController@success` (GET)
* `/checkout/card/{order}/cancel` → `CardPaymentController@cancel` (GET)
* `/payment/card/webhook` → `CardPaymentController@webhook` (POST, sans middleware auth)

### ✅ D. Sécurité

* `.env` : `APP_DEBUG=false` en prod
* Webhook : en prod, **obligatoire** d'activer `STRIPE_WEBHOOK_SECRET` + vérification de signature
* Aucun numéro de carte **jamais stocké**
* Site servi en **HTTPS**

---

## 9. Passage en production

1. Passer Stripe en mode **Live**.
2. Récupérer **pk_live_…** et **sk_live_…**.
3. Mettre à jour `.env` :

```env
APP_ENV=production
APP_DEBUG=false

STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

4. Mettre à jour URL du webhook (prod) dans Stripe :
   * `https://votre-domaine.com/payment/card/webhook`

5. Re-déployer et tester avec une vraie carte bancaire.

---

## 10. Configuration Webhook (pour production)

### Avec ngrok (tests locaux) :

1. Installez ngrok : https://ngrok.com/download
2. Lancez votre serveur Laravel : `php artisan serve`
3. Dans un autre terminal : `ngrok http 8000`
4. Copiez l'URL HTTPS fournie (ex: `https://abc123.ngrok.io`)

5. Dans Stripe Dashboard → **Developers** → **Webhooks** :
   - Cliquez "Add endpoint"
   - URL : `https://abc123.ngrok.io/payment/card/webhook`
   - Événements à sélectionner :
     - `checkout.session.completed`
     - `payment_intent.succeeded`
     - `payment_intent.payment_failed`
   - Cliquez "Add endpoint"

6. Copiez le **Signing secret** (commence par `whsec_...`)
7. Ajoutez-le dans `.env` : `STRIPE_WEBHOOK_SECRET=whsec_...`

---

## 11. Problèmes courants

### Erreur "Stripe is not enabled"
→ Vérifiez que `STRIPE_ENABLED=true` dans `.env`

### Erreur "Invalid API key"
→ Vérifiez que vous avez bien copié les clés complètes

### Webhook ne fonctionne pas
→ Pour les tests locaux, utilisez ngrok
→ Vérifiez que l'URL du webhook est correcte

### Cache de configuration
Si les changements ne sont pas pris en compte :
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 📞 Support

- Documentation Stripe : https://stripe.com/docs
- Dashboard Stripe : https://dashboard.stripe.com
- Logs Stripe : Dashboard → Developers → Logs

---

**Votre module de paiement CB est prêt ! 🎉**
