# Variables d'environnement Monetbil

## Configuration requise

Ajoutez ces variables dans votre fichier `.env` :

```env
# Monetbil Configuration (Mobile Money)
MONETBIL_SERVICE_KEY=your_service_key
MONETBIL_SERVICE_SECRET=your_service_secret
MONETBIL_WIDGET_VERSION=v2.1
MONETBIL_COUNTRY=CG
MONETBIL_CURRENCY=XAF
MONETBIL_NOTIFY_URL=https://votre-domaine.com/payment/monetbil/notify
MONETBIL_RETURN_URL=https://votre-domaine.com/checkout/success
MONETBIL_ALLOWED_IPS= (optionnel, séparer par virgule)
```

---

## 📋 CONFIGURATION PRODUCTION

### Variables obligatoires

```env
# Production
APP_ENV=production
APP_DEBUG=false

# Monetbil Production Keys (depuis Dashboard Monetbil)
MONETBIL_SERVICE_KEY=pk_live_xxxxxxxxxxxxx
MONETBIL_SERVICE_SECRET=sk_live_xxxxxxxxxxxxx
MONETBIL_WIDGET_VERSION=v2.1
MONETBIL_COUNTRY=CG
MONETBIL_CURRENCY=XAF

# URLs Production (remplacer <DOMAIN> par votre domaine réel)
MONETBIL_NOTIFY_URL=https://racinebyganda.com/payment/monetbil/notify
MONETBIL_RETURN_URL=https://racinebyganda.com/checkout/success

# IP Whitelist (optionnel mais recommandé en production)
# Récupérer les IPs Monetbil depuis leur documentation ou support
MONETBIL_ALLOWED_IPS=41.202.xxx.xxx,41.203.xxx.xxx
```

### Checklist Production

- [ ] `APP_ENV=production` configuré
- [ ] `MONETBIL_SERVICE_KEY` et `MONETBIL_SERVICE_SECRET` sont les clés **PRODUCTION** (pas de test)
- [ ] `MONETBIL_NOTIFY_URL` pointe vers votre domaine de production (HTTPS obligatoire)
- [ ] `MONETBIL_RETURN_URL` pointe vers votre domaine de production (HTTPS obligatoire)
- [ ] `MONETBIL_ALLOWED_IPS` configuré avec les IPs Monetbil (recommandé)
- [ ] Signature webhook **obligatoire** en production (vérifiée automatiquement)
- [ ] Testé avec une transaction réelle en mode sandbox avant mise en production

---

## 🧪 CONFIGURATION DÉVELOPPEMENT/LOCAL

### Variables pour développement

```env
# Développement
APP_ENV=local
APP_DEBUG=true

# Monetbil Test Keys (depuis Dashboard Monetbil → Mode Test)
MONETBIL_SERVICE_KEY=pk_test_xxxxxxxxxxxxx
MONETBIL_SERVICE_SECRET=sk_test_xxxxxxxxxxxxx
MONETBIL_WIDGET_VERSION=v2.1
MONETBIL_COUNTRY=CG
MONETBIL_CURRENCY=XAF

# URLs Local (utiliser ngrok ou équivalent pour tester les webhooks)
MONETBIL_NOTIFY_URL=https://your-ngrok-url.ngrok.io/payment/monetbil/notify
MONETBIL_RETURN_URL=http://localhost:8000/checkout/success

# IP Whitelist désactivée en développement (laisser vide)
MONETBIL_ALLOWED_IPS=
```

### Notes développement

- **Signature** : Optionnelle en développement (mais recommandée pour tester)
- **ngrok** : Utiliser ngrok pour exposer votre serveur local et tester les webhooks
  ```bash
  ngrok http 8000
  # Utiliser l'URL HTTPS fournie par ngrok dans MONETBIL_NOTIFY_URL
  ```
- **Stripe CLI** : Non applicable (Monetbil n'a pas d'équivalent CLI)

---

## 🔑 Où récupérer les clés

### Dashboard Monetbil

1. **Se connecter** : https://dashboard.monetbil.com
2. **Service Key** : Dashboard → Paramètres → Service Key
   - Format : `pk_test_...` (test) ou `pk_live_...` (production)
3. **Service Secret** : Dashboard → Paramètres → Service Secret
   - Format : `sk_test_...` (test) ou `sk_live_...` (production)
   - ⚠️ **NE JAMAIS PARTAGER** ce secret

### IPs Monetbil (pour whitelist)

- Contacter le support Monetbil pour obtenir la liste des IPs autorisées
- Ou consulter la documentation : https://www.monetbil.com/documentation

---

## ⚙️ Configuration dans `config/services.php`

Les variables sont mappées automatiquement :

```php
'monetbil' => [
    'service_key' => env('MONETBIL_SERVICE_KEY'),
    'service_secret' => env('MONETBIL_SERVICE_SECRET'),
    'widget_version' => env('MONETBIL_WIDGET_VERSION', 'v2.1'),
    'country' => env('MONETBIL_COUNTRY', 'CG'),
    'currency' => env('MONETBIL_CURRENCY', 'XAF'),
    'notify_url' => env('MONETBIL_NOTIFY_URL'),
    'return_url' => env('MONETBIL_RETURN_URL'),
    'allowed_ips' => env('MONETBIL_ALLOWED_IPS'),
],
```

---

## 🔐 Sécurité

### Production

- ✅ **Signature** : Obligatoire (rejet 401 si absente/invalide)
- ✅ **IP Whitelist** : Recommandée (rejet 403 si IP non autorisée)
- ✅ **HTTPS** : Obligatoire pour `MONETBIL_NOTIFY_URL` et `MONETBIL_RETURN_URL`
- ✅ **CSRF** : Exempté pour `/payment/monetbil/notify` (webhook externe)

### Développement

- ⚠️ **Signature** : Optionnelle (warning dans les logs si absente)
- ⚠️ **IP Whitelist** : Désactivée si `MONETBIL_ALLOWED_IPS` vide
- ⚠️ **HTTPS** : Recommandé (utiliser ngrok pour webhooks)

### Codes HTTP (alignés avec Stripe)

- **401** : Signature absente/invalide (production)
- **400** : Payload invalide (missing payment_ref/status)
- **403** : IP non autorisée (si whitelist active)
- **404** : Transaction introuvable
- **422** : Erreur API Monetbil (création paiement)
- **500** : Erreur serveur inattendue (uniquement)

---

## 📚 Documentation

- **Documentation Monetbil** : https://www.monetbil.com/documentation
- **Dashboard Monetbil** : https://dashboard.monetbil.com
- **Support** : Contacter le support Monetbil pour les IPs autorisées

---

## ✅ Vérification après configuration

```bash
# Vider le cache de configuration
php artisan config:clear
php artisan cache:clear

# Vérifier que les variables sont chargées
php artisan tinker
>>> config('services.monetbil.service_key')
>>> config('services.monetbil.service_secret')
```

---

## 🧪 Tests

```bash
# Tests Monetbil uniquement
php artisan test --filter MonetbilPaymentTest

# Tous les tests
php artisan test
```

