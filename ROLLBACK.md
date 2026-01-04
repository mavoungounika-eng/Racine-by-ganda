# PROCÉDURE ROLLBACK — RACINE BY GANDA

## Principe

En cas de problème critique en production, cette procédure permet de revenir à un état stable antérieur.

---

## 1️⃣ ROLLBACK GIT (Code)

### Identifier la version stable

```bash
# Lister les derniers commits
git log --oneline -10

# Identifier le commit stable (ex: abc1234)
```

### Option A : Rollback Hard (Destructif)

```bash
# ⚠️ ATTENTION : Perte définitive des modifications non commitées
git reset --hard <commit-stable>
git push --force origin main
```

### Option B : Rollback Soft (Conserve les modifications)

```bash
# Revenir au commit stable en conservant les changements
git reset --soft <commit-stable>
git stash
git push --force origin main
```

### Option C : Revert (Recommandé en production)

```bash
# Créer un nouveau commit qui annule les changements
git revert <commit-problematique>
git push origin main
```

### Redéployer

```bash
# Sur le serveur de production
cd /var/www/racine-backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## 2️⃣ ROLLBACK BASE DE DONNÉES

### Avant toute migration en production

```bash
# Créer un backup manuel
php artisan backup:run --only-db

# Ou via mysqldump
mysqldump -u root -p racine_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurer un backup

```bash
# Arrêter l'application
php artisan down

# Restaurer la base de données
mysql -u root -p racine_db < backup_20260104_153000.sql

# Vérifier l'état des migrations
php artisan migrate:status

# Redémarrer l'application
php artisan up
```

### Rollback de migrations spécifiques

```bash
# Rollback de la dernière migration
php artisan migrate:rollback

# Rollback des N dernières migrations
php artisan migrate:rollback --step=3

# Rollback d'une migration spécifique
php artisan migrate:rollback --path=database/migrations/2026_01_04_000002_add_logistics_columns_to_orders_table.php
```

---

## 3️⃣ ROLLBACK CONFIGURATION

### Fichiers critiques à sauvegarder

Avant tout déploiement, sauvegarder :

```bash
# .env
cp .env .env.backup_$(date +%Y%m%d)

# config/dashboard.php
cp config/dashboard.php config/dashboard.php.backup

# config/database.php
cp config/database.php config/database.php.backup
```

### Restaurer une configuration

```bash
# Restaurer .env
cp .env.backup_20260104 .env

# Recharger la configuration
php artisan config:clear
php artisan config:cache
```

---

## 4️⃣ ROLLBACK CACHE

### Vider tous les caches

```bash
# Cache application
php artisan cache:clear

# Cache configuration
php artisan config:clear

# Cache routes
php artisan route:clear

# Cache views
php artisan view:clear

# Cache dashboard (custom)
php artisan cache:forget dashboard.global_state
php artisan cache:forget dashboard.alerts
php artisan cache:forget dashboard.commercial
php artisan cache:forget dashboard.marketplace
php artisan cache:forget dashboard.operations
php artisan cache:forget dashboard.trends
```

---

## 5️⃣ ROLLBACK COMPLET (Procédure d'urgence)

### Étapes

1. **Mettre l'application en maintenance**
   ```bash
   php artisan down --message="Maintenance en cours" --retry=60
   ```

2. **Rollback Git**
   ```bash
   git reset --hard <commit-stable>
   git push --force origin main
   ```

3. **Restaurer la base de données**
   ```bash
   mysql -u root -p racine_db < backup_latest.sql
   ```

4. **Restaurer la configuration**
   ```bash
   cp .env.backup_latest .env
   ```

5. **Redéployer**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate:status
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

6. **Vider les caches**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

7. **Redémarrer les services**
   ```bash
   php artisan queue:restart
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart nginx
   ```

8. **Remettre en ligne**
   ```bash
   php artisan up
   ```

---

## 6️⃣ VÉRIFICATIONS POST-ROLLBACK

### Checklist

- [ ] Application accessible
- [ ] Dashboard admin fonctionne
- [ ] Connexion utilisateurs OK
- [ ] Commandes visibles
- [ ] Paiements fonctionnels
- [ ] Emails envoyés
- [ ] Queue active
- [ ] Logs propres (pas d'erreurs critiques)

### Commandes de vérification

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier la queue
php artisan queue:work --once

# Vérifier les migrations
php artisan migrate:status

# Tester une route critique
curl -I https://racine-by-ganda.com/admin/dashboard
```

---

## 7️⃣ CONTACTS D'URGENCE

| Rôle | Contact | Disponibilité |
|------|---------|---------------|
| **Développeur Principal** | [email/phone] | 24/7 |
| **Hébergeur** | [support] | 24/7 |
| **DBA** | [email/phone] | Sur appel |

---

## 8️⃣ POST-MORTEM

Après chaque rollback, documenter :

1. **Cause du problème**
2. **Commit problématique**
3. **Actions prises**
4. **Durée d'indisponibilité**
5. **Leçons apprises**
6. **Mesures préventives**

**Fichier** : `docs/incidents/incident_YYYYMMDD.md`

---

## Notes Importantes

- ⚠️ **Toujours tester en staging avant production**
- ⚠️ **Backups automatiques quotidiens obligatoires**
- ⚠️ **Ne jamais rollback sans backup récent**
- ⚠️ **Communiquer avec l'équipe avant tout rollback**
