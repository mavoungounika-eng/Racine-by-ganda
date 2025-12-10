# 🔒 SÉCURITÉ DES WEBHOOKS — RACINE BY GANDA

**Date :** 1 Décembre 2025  
**Statut :** ✅ **WEBHOOKS SÉCURISÉS**

---

## 📊 RÉSUMÉ

Tous les webhooks de paiement sont maintenant **sécurisés avec vérification de signature** :

- ✅ **Stripe** — Vérification de signature implémentée
- ✅ **MTN MoMo** — Vérification de signature implémentée
- ✅ **Airtel Money** — Vérification de signature implémentée

---

## 🔐 STRIPE WEBHOOKS

### Vérification de signature

**Fichier :** `app/Services/Payments/CardPaymentService.php`

La vérification utilise la méthode officielle Stripe `Webhook::constructEvent()` :

```php
$event = Webhook::constructEvent(
    $payload,        // Contenu brut (JSON string)
    $signature,      // Header Stripe-Signature
    $webhookSecret   // Secret configuré dans .env
);
```

### Configuration requise

**Variable d'environnement :**
```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Comment obtenir le secret :**
1. Aller sur https://dashboard.stripe.com/webhooks
2. Créer ou sélectionner un endpoint webhook
3. Copier le "Signing secret" (commence par `whsec_`)

### Routes webhooks

- **Route principale :** `/payment/card/webhook`
- **Route alternative :** `/webhooks/stripe` (legacy)

**Important :** Ces routes sont **exclues du middleware CSRF** et **auth** car elles sont appelées directement par Stripe.

### Comportement

- **Production :** Vérification de signature **obligatoire**
- **Développement :** Vérification désactivée si secret non configuré (pour faciliter les tests)
- **Erreur :** Si signature invalide, retourne `401 Unauthorized` et log l'erreur

---

## 📱 MOBILE MONEY WEBHOOKS

### MTN MoMo

**Fichier :** `app/Http/Controllers/Front/MobileMoneyPaymentController.php`

**Méthode de vérification :**
```php
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
return hash_equals($expectedSignature, $signature);
```

**Headers supportés :**
- `X-Callback-Signature` (standard MTN)
- `X-Signature` (alternatif)
- `Authorization: Bearer {signature}` (alternatif)

**Configuration :**
```env
MTN_MOMO_WEBHOOK_SECRET=votre_secret
```

### Airtel Money

**Même méthode que MTN MoMo**

**Configuration :**
```env
AIRTEL_MONEY_WEBHOOK_SECRET=votre_secret
```

### Routes webhooks

- **MTN MoMo :** `/payment/mobile-money/mtn_momo/callback`
- **Airtel Money :** `/payment/mobile-money/airtel_money/callback`

---

## 🛡️ MESURES DE SÉCURITÉ

### 1. Vérification de signature

Tous les webhooks vérifient la signature avant traitement :

- ✅ Empêche les requêtes forgées
- ✅ Garantit l'authenticité du provider
- ✅ Utilise des méthodes cryptographiques sécurisées

### 2. Logging complet

Tous les événements sont loggés :

- ✅ Webhooks reçus
- ✅ Vérifications de signature (succès/échec)
- ✅ Erreurs de traitement
- ✅ Tentatives invalides

**Fichier de logs :** `storage/logs/laravel.log`

### 3. Gestion des erreurs

- ✅ Retourne des codes HTTP appropriés (401, 400, 500)
- ✅ Ne révèle pas d'informations sensibles
- ✅ Log les erreurs pour debugging

### 4. Exclusion CSRF/Auth

Les routes webhooks sont exclues des middlewares :

- ✅ Pas de vérification CSRF (webhooks externes)
- ✅ Pas d'authentification (appelés par les providers)
- ✅ Protection par signature uniquement

---

## 🧪 TESTER LES WEBHOOKS

### Stripe (Mode développement)

1. **Installer Stripe CLI :**
   ```bash
   stripe listen --forward-to localhost:8000/payment/card/webhook
   ```

2. **Tester un événement :**
   ```bash
   stripe trigger checkout.session.completed
   ```

3. **Vérifier les logs :**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Mobile Money (Mode développement)

En mode développement, la vérification est désactivée pour faciliter les tests.

**Tester manuellement :**
```bash
curl -X POST http://localhost:8000/payment/mobile-money/mtn_momo/callback \
  -H "Content-Type: application/json" \
  -d '{"transaction_id":"TEST-123","status":"success"}'
```

---

## ⚠️ POINTS IMPORTANTS

### Production

1. **HTTPS obligatoire**
   - Les webhooks nécessitent HTTPS
   - Stripe refuse les endpoints HTTP en production

2. **Secrets sécurisés**
   - Ne jamais commiter les secrets dans Git
   - Utiliser des variables d'environnement
   - Rotater les secrets régulièrement

3. **Monitoring**
   - Surveiller les logs pour détecter les tentatives d'attaque
   - Alerter en cas de nombreuses signatures invalides

4. **Rate limiting**
   - Les providers limitent le nombre de webhooks
   - Implémenter un rate limiting si nécessaire

### Développement

1. **Stripe CLI**
   - Utiliser Stripe CLI pour tester localement
   - Permet de forwarder les webhooks vers localhost

2. **Mode simulation**
   - Mobile Money peut fonctionner en mode simulation
   - Permet de tester sans appeler les APIs réelles

---

## 📋 CHECKLIST DÉPLOIEMENT

### Avant production

- [ ] Secrets webhooks configurés dans `.env`
- [ ] HTTPS activé sur le serveur
- [ ] Routes webhooks accessibles publiquement
- [ ] Vérification de signature testée
- [ ] Logs configurés et monitorés
- [ ] Endpoints configurés chez les providers

### Configuration providers

**Stripe :**
- [ ] Endpoint webhook créé dans le dashboard
- [ ] URL : `https://votre-domaine.com/payment/card/webhook`
- [ ] Événements sélectionnés :
  - `checkout.session.completed`
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
- [ ] Signing secret copié dans `.env`

**MTN MoMo :**
- [ ] Webhook configuré dans le dashboard
- [ ] URL : `https://votre-domaine.com/payment/mobile-money/mtn_momo/callback`
- [ ] Secret webhook configuré

**Airtel Money :**
- [ ] Webhook configuré dans le dashboard
- [ ] URL : `https://votre-domaine.com/payment/mobile-money/airtel_money/callback`
- [ ] Secret webhook configuré

---

## 🔍 DÉBOGUAGE

### Problèmes courants

#### Signature invalide

**Symptômes :**
- Erreur 401 dans les logs
- Webhook non traité

**Solutions :**
1. Vérifier que le secret est correct dans `.env`
2. Vérifier que le secret correspond à l'endpoint
3. Vérifier que le payload n'est pas modifié (proxy, load balancer)

#### Webhook non reçu

**Symptômes :**
- Pas de log dans `laravel.log`
- Commande non mise à jour

**Solutions :**
1. Vérifier que l'URL est accessible publiquement
2. Vérifier que HTTPS est activé (production)
3. Vérifier les logs du provider
4. Tester avec Stripe CLI (Stripe)

#### Payload invalide

**Symptômes :**
- Erreur "Invalid payload" dans les logs
- Webhook non traité

**Solutions :**
1. Vérifier le format JSON
2. Vérifier que tous les champs requis sont présents
3. Vérifier la version de l'API du provider

---

## 📚 RESSOURCES

- **Stripe Webhooks :** https://stripe.com/docs/webhooks
- **MTN MoMo Webhooks :** https://momodeveloper.mtn.com/docs
- **Airtel Money Webhooks :** https://developer.airtel.africa/docs

---

## ✅ CONCLUSION

Tous les webhooks sont maintenant **sécurisés** avec vérification de signature. Le système est prêt pour la production après configuration des secrets.

---

**Dernière mise à jour :** 1 Décembre 2025


