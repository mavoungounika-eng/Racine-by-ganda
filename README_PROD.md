# 🚀 README PRODUCTION — RACINE BY GANDA

**Guide minimal pour survivre à 3h du matin**

---

## 📋 DÉPLOIEMENT

### 1. Préparer l'environnement

```bash
# Cloner le projet
git clone <repo>
cd racine-backend

# Installer dépendances
composer install --optimize-autoloader --no-dev

# Copier .env
cp .env.example .env

# Générer clé application
php artisan key:generate
```

### 2. Configurer `.env`

Voir `PRODUCTION_CHECKLIST.md` section 1.1 pour les variables critiques.

**Minimum requis :**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` (généré)
- `DB_*` (base de données)
- `STRIPE_*` (clés production)
- `MONETBIL_*` (clés production)

### 3. Déployer

```bash
# Migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chmod -R 755 storage bootstrap/cache

# Démarrer queue worker
php artisan queue:work --daemon
```

---

## 🔄 ROLLBACK

### Rollback Rapide

```bash
# 1. Restaurer backup DB
mysql -u user -p database < backup.sql

# 2. Revenir à version précédente
git checkout <previous-commit>
composer install --optimize-autoloader --no-dev

# 3. Vider cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Redémarrer workers
php artisan queue:restart
```

### Rollback Partiel (Code uniquement)

```bash
git checkout <previous-commit>
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## 🔍 DIAGNOSTIC

### Erreurs 5xx

```bash
# Vérifier logs erreurs
tail -f storage/logs/errors.log

# Vérifier logs Laravel
tail -f storage/logs/laravel.log

# Vérifier jobs échoués
php artisan queue:failed
```

### Webhooks Non Traités

```bash
# Vérifier logs webhooks
tail -f storage/logs/webhooks.log

# Vérifier événements Stripe non traités
php artisan tinker
>>> \App\Models\StripeWebhookEvent::where('status', 'pending')->count()

# Vérifier événements Monetbil non traités
>>> \App\Models\MonetbilCallbackEvent::where('status', 'pending')->count()
```

### Jobs en Échec

```bash
# Lister jobs échoués
php artisan queue:failed

# Retry un job spécifique
php artisan queue:retry <job-id>

# Retry tous les jobs
php artisan queue:retry all

# Supprimer un job échoué
php artisan queue:forget <job-id>
```

### Paiements Bloqués

```bash
# Vérifier logs paiements
tail -f storage/logs/payments.log

# Vérifier paiements en attente
php artisan tinker
>>> \App\Models\Payment::where('status', 'pending')->where('created_at', '>', now()->subHours(24))->count()
```

### Queue Worker Ne Fonctionne Pas

```bash
# Vérifier si worker tourne
ps aux | grep "queue:work"

# Redémarrer worker
php artisan queue:restart

# Démarrer worker manuellement
php artisan queue:work --verbose --tries=3 --timeout=60
```

---

## 🛠️ COMMANDES UTILES

### Cache

```bash
# Vider tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reconstruire caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue

```bash
# Redémarrer workers
php artisan queue:restart

# Lister jobs échoués
php artisan queue:failed

# Retry tous les jobs
php artisan queue:retry all
```

### Base de Données

```bash
# Migrations
php artisan migrate --force

# Rollback dernière migration
php artisan migrate:rollback

# Voir statut migrations
php artisan migrate:status
```

---

## 📞 CONTACTS URGENCE

### Support Technique

- **Logs :** `storage/logs/`
- **Jobs échoués :** `php artisan queue:failed`
- **Documentation complète :** `PRODUCTION_CHECKLIST.md`

### Services Externes

- **Stripe Dashboard :** https://dashboard.stripe.com
- **Monetbil Dashboard :** https://dashboard.monetbil.com
- **Logs serveur :** Vérifier avec votre hébergeur

---

## ⚠️ CHECKLIST RAPIDE

Avant de paniquer, vérifier :

1. ✅ Queue worker tourne : `ps aux | grep "queue:work"`
2. ✅ Logs accessibles : `ls -la storage/logs/`
3. ✅ Cache fonctionne : `php artisan cache:clear` (ne doit pas planter)
4. ✅ Base de données accessible : `php artisan tinker` → `DB::connection()->getPdo()`
5. ✅ `.env` correct : `APP_ENV=production`, `APP_DEBUG=false`

---

**💡 ASTUCE :** En cas de doute, consulter `PRODUCTION_CHECKLIST.md` pour la checklist complète.

