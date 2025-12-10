# 💰 CONFIGURATION MOBILE MONEY — RACINE BY GANDA

**Date :** 1 Décembre 2025  
**Statut :** ✅ **Intégration API complète — Prêt pour production**

---

## 📋 VARIABLES D'ENVIRONNEMENT À AJOUTER

Ajoutez ces variables dans votre fichier `.env` :

### MTN Mobile Money

```env
# MTN Mobile Money
MTN_MOMO_ENABLED=true
MTN_MOMO_API_KEY=votre_api_key
MTN_MOMO_API_SECRET=votre_api_secret
MTN_MOMO_SUBSCRIPTION_KEY=votre_subscription_key
MTN_MOMO_ENVIRONMENT=sandbox
MTN_MOMO_COLLECTION_ID=votre_collection_id
MTN_MOMO_WEBHOOK_SECRET=votre_webhook_secret
MTN_MOMO_CURRENCY=XAF
```

### Airtel Money

```env
# Airtel Money
AIRTEL_MONEY_ENABLED=true
AIRTEL_MONEY_CLIENT_ID=votre_client_id
AIRTEL_MONEY_CLIENT_SECRET=votre_client_secret
AIRTEL_MONEY_ENVIRONMENT=sandbox
AIRTEL_MONEY_WEBHOOK_SECRET=votre_webhook_secret
AIRTEL_MONEY_CURRENCY=XAF
```

---

## 🔧 COMMENT OBTENIR LES CLÉS API

### MTN MoMo

1. **Créer un compte développeur**
   - Aller sur https://momodeveloper.mtn.com/
   - Créer un compte développeur
   - S'inscrire au programme Collection API

2. **Obtenir les clés**
   - API Key et API Secret : Dans votre profil développeur
   - Subscription Key : Dans la section API Products
   - Collection ID : Généré automatiquement lors de la création du compte

3. **Configurer les webhooks**
   - URL de callback : `https://votre-domaine.com/webhooks/mobile-money/mtn_momo`
   - Webhook Secret : Généré par MTN

### Airtel Money

1. **Créer un compte développeur**
   - Aller sur https://developer.airtel.africa/
   - Créer un compte développeur
   - S'inscrire au programme Merchant Payments

2. **Obtenir les clés**
   - Client ID et Client Secret : Dans votre profil développeur
   - Webhook Secret : Configuré dans les paramètres de l'application

3. **Configurer les webhooks**
   - URL de callback : `https://votre-domaine.com/webhooks/mobile-money/airtel_money`

---

## 🧪 MODE SANDBOX vs PRODUCTION

### Mode Sandbox (Développement)

```env
MTN_MOMO_ENVIRONMENT=sandbox
AIRTEL_MONEY_ENVIRONMENT=sandbox
```

- Utilise les APIs de test
- Pas de vrais paiements
- Parfait pour tester

### Mode Production

```env
MTN_MOMO_ENVIRONMENT=production
AIRTEL_MONEY_ENVIRONMENT=production
```

- Utilise les APIs réelles
- Vrais paiements
- Nécessite un compte approuvé

---

## 🔒 SÉCURITÉ DES WEBHOOKS

### Vérification de signature

Le système vérifie automatiquement la signature des webhooks en production :

1. **MTN MoMo** : Signature dans le header `X-Callback-Signature`
2. **Airtel Money** : Signature dans le header `X-Signature`

En mode développement (`APP_ENV=local`), la vérification est désactivée pour faciliter les tests.

---

## 🧪 TESTER L'INTÉGRATION

### 1. Mode Simulation (Développement)

Si `MTN_MOMO_ENABLED=false` ou `AIRTEL_MONEY_ENABLED=false`, le système fonctionne en mode simulation :

- Crée un paiement avec statut `pending`
- Affiche les instructions USSD
- Permet de tester le flux sans appeler les APIs

### 2. Mode Sandbox

1. Configurer les clés sandbox dans `.env`
2. Mettre `MTN_MOMO_ENABLED=true` ou `AIRTEL_MONEY_ENABLED=true`
3. Tester avec un numéro de test fourni par le provider
4. Vérifier les callbacks

### 3. Mode Production

1. Obtenir les clés production
2. Configurer les webhooks chez les providers
3. Tester avec un petit montant
4. Vérifier que tout fonctionne

---

## 📊 MONITORING

### Logs

Tous les événements sont loggés dans `storage/logs/laravel.log` :

- Initiation de paiement
- Appels API
- Callbacks reçus
- Erreurs

### Vérifier un paiement

```bash
php artisan tinker

$payment = \App\Models\Payment::where('channel', 'mobile_money')->latest()->first();
$payment->status;
$payment->metadata;
```

---

## ⚠️ POINTS IMPORTANTS

1. **HTTPS obligatoire** : Les webhooks nécessitent HTTPS en production
2. **Rate limiting** : Les providers limitent le nombre d'appels API
3. **Timeout** : Les paiements expirent après un certain temps
4. **Retry logic** : En cas d'échec, le système peut réessayer automatiquement

---

## 🚀 DÉPLOIEMENT

### Checklist avant production

- [ ] Clés API production obtenues
- [ ] Variables d'environnement configurées
- [ ] Webhooks configurés chez les providers
- [ ] HTTPS activé
- [ ] Tests effectués en sandbox
- [ ] Monitoring configuré
- [ ] Documentation équipe créée

---

## 📞 SUPPORT

- **MTN MoMo** : https://momodeveloper.mtn.com/support
- **Airtel Money** : https://developer.airtel.africa/support
- **Logs** : `storage/logs/laravel.log`

---

**Dernière mise à jour :** 1 Décembre 2025


