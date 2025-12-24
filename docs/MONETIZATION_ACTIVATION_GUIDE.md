# 💰 GUIDE D'ACTIVATION MONÉTISATION — RACINE BY GANDA

**Date :** 2025-12-XX  
**Statut :** ✅ PRÊT POUR ACTIVATION

---

## 🎯 OBJECTIF

Activer la monétisation en production de manière **safe** et **réversible**.

---

## 📋 PRÉ-REQUIS

- [ ] Checklist production complétée (`PRODUCTION_CHECKLIST.md`)
- [ ] Tests fonctionnels réussis
- [ ] Monitoring configuré
- [ ] Équipe formée et alertée

---

## 🔴 STRIPE — ACTIVATION LIVE

### 1. Vérifier Compte Stripe

- [ ] Compte Stripe activé en mode **Live**
- [ ] Informations bancaires configurées
- [ ] Informations légales complètes

### 2. Configurer Clés Production

```env
# ⚠️ OBLIGATOIRE : Clés PRODUCTION (pas de test)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Vérifications :**
- [ ] `STRIPE_KEY` commence par `pk_live_` (pas `pk_test_`)
- [ ] `STRIPE_SECRET` commence par `sk_live_` (pas `sk_test_`)
- [ ] `STRIPE_WEBHOOK_SECRET` commence par `whsec_`

### 3. Enregistrer Webhook Production

1. Aller sur https://dashboard.stripe.com/webhooks
2. Cliquer sur "Add endpoint"
3. URL : `https://votre-domaine.com/api/webhooks/stripe`
4. Sélectionner événements :
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
   - `checkout.session.async_payment_failed`
5. Copier le **Signing secret** (`whsec_...`)
6. Ajouter dans `.env` : `STRIPE_WEBHOOK_SECRET=whsec_...`

**Vérifications :**
- [ ] Webhook enregistré avec URL production (HTTPS)
- [ ] Signing secret configuré dans `.env`
- [ ] Test webhook réussi (depuis dashboard Stripe)

### 4. Test Transaction Réelle

1. Créer une commande test (montant minimal : 100 XAF)
2. Payer avec carte de test Stripe :
   - Carte réussie : `4242 4242 4242 4242`
   - Carte refusée : `4000 0000 0000 0002`
3. Vérifier :
   - [ ] Paiement traité
   - [ ] Webhook reçu et traité
   - [ ] Commande mise à jour
   - [ ] Email envoyé

---

## 🟢 MONETBIL — ACTIVATION PRODUCTION

### 1. Vérifier Compte Monetbil

- [ ] Compte Monetbil activé en mode **Production**
- [ ] Service Key et Secret production obtenus
- [ ] Compte bancaire configuré

### 2. Configurer Clés Production

```env
# ⚠️ OBLIGATOIRE : Clés PRODUCTION (pas de test)
MONETBIL_SERVICE_KEY=pk_live_...
MONETBIL_SERVICE_SECRET=sk_live_...
MONETBIL_NOTIFY_URL=https://votre-domaine.com/api/webhooks/monetbil
MONETBIL_RETURN_URL=https://votre-domaine.com/checkout/success
```

**Vérifications :**
- [ ] Clés production obtenues depuis dashboard Monetbil
- [ ] URLs production configurées (HTTPS obligatoire)
- [ ] IP whitelist configurée (recommandé)

### 3. Test Transaction Réelle

1. Créer une commande test (montant minimal)
2. Payer avec Mobile Money
3. Vérifier :
   - [ ] Callback reçu et traité
   - [ ] Commande mise à jour
   - [ ] Email envoyé

---

## 🔄 SWITCH TEST → LIVE

### Checklist Avant Switch

- [ ] Toutes les clés production configurées
- [ ] Webhooks enregistrés et testés
- [ ] Tests transactionnels réussis
- [ ] Monitoring activé
- [ ] Équipe alertée
- [ ] Plan de rollback préparé

### Activation

1. **Vérifier `.env`** : Toutes les clés sont production
2. **Vider cache** : `php artisan config:cache`
3. **Redémarrer workers** : `php artisan queue:restart`
4. **Tester** : Transaction réelle (montant minimal)
5. **Surveiller** : Logs et monitoring pendant 24h

### Rollback Possible

Si problème détecté :

1. **Revenir aux clés test** dans `.env`
2. **Vider cache** : `php artisan config:cache`
3. **Redémarrer workers** : `php artisan queue:restart`
4. **Vérifier** : Transactions test fonctionnent

---

## 📊 VÉRIFICATIONS POST-ACTIVATION

### 24h Après Activation

- [ ] Aucune erreur 5xx
- [ ] Tous les webhooks traités
- [ ] Aucun job en échec critique
- [ ] Transactions réussies
- [ ] Emails envoyés

### 7 Jours Après Activation

- [ ] Revenus cohérents (MRR/ARR)
- [ ] Taux de conversion normal
- [ ] Aucun problème de paiement
- [ ] Monitoring stable

---

## 🚨 EN CAS DE PROBLÈME

### Webhooks Non Reçus

1. Vérifier logs : `storage/logs/webhooks.log`
2. Vérifier URL webhook dans dashboard Stripe/Monetbil
3. Vérifier HTTPS et certificat SSL
4. Tester webhook manuellement depuis dashboard

### Transactions Échouées

1. Vérifier logs : `storage/logs/payments.log`
2. Vérifier clés production correctes
3. Vérifier webhooks traités
4. Vérifier jobs queue : `php artisan queue:failed`

### Rollback Urgent

1. Revenir aux clés test dans `.env`
2. Vider cache : `php artisan config:cache`
3. Redémarrer workers : `php artisan queue:restart`
4. Contacter support Stripe/Monetbil si nécessaire

---

## ✅ VALIDATION FINALE

- [ ] Stripe Live activé et testé
- [ ] Monetbil Production activé et testé
- [ ] Webhooks fonctionnels
- [ ] Transactions réussies
- [ ] Monitoring actif
- [ ] Équipe formée

---

**💰 MONÉTISATION ACTIVÉE ET OPÉRATIONNELLE**

