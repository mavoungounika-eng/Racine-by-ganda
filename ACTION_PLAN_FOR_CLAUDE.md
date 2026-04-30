# 🎯 ACTION PLAN - POUR CLAUDE

## 📌 CONTEXTE ACTUEL

- **État:** Commit réussi (dd2b0103) - tous les changements sont sauvegardés
- **Migrations:** 219 migrations toutes exécutées ✅
- **Problème:** APP_KEY corrompu en production

---

## ⚡ ACTIONS PRIORITAIRES

### 1. **URGENT - Régénérer APP_KEY** 🔴

**Commande:**
```bash
php artisan key:generate
```

**Vérifier après:**
```bash
grep "APP_KEY=" .env
# Devrait afficher: APP_KEY=base64:quelque_chose_de_valide
```

**Note:** Cela va invaliderer les sessions existantes (normal en dev)

---

### 2. **Initialiser la base de données** 🟡

**Option A - Development (recommandé):**
```bash
php artisan migrate:fresh --seed
```

**Option B - Production (keep data):**
```bash
php artisan migrate
php artisan db:seed --class=TestUsersSeeder
```

---

### 3. **Valider le statut** 🟢

```bash
php artisan migrate:status | tail -20  # Voir les dernières migrations
php artisan db:show                    # Infos BD
php artisan tinker                     # Console interactive
```

**Dans tinker:**
```php
User::count()  # Doit afficher le nombre d'utilisateurs
DB::table('users')->first()  # Vérifier données
```

---

## 📋 CHECKLIST DES PROBLÈMES À INVESTIGUER

- [ ] **APP_KEY:** Régénéré et valide
- [ ] **Redis Connection:** Vérifier `REDIS_HOST`, `REDIS_PORT`
- [ ] **Database Connection:** Test de connexion MySQL
- [ ] **File Permissions:** `storage/` et `bootstrap/cache/` writable
- [ ] **Seeders:** Exécutés sans erreurs
- [ ] **Tests:** PHPUnit pass

---

## 🔍 DIAGNOSTIC DÉTAILLÉ

### Tester la connexion BD
```bash
php artisan tinker
> DB::connection()->getPdo()  # Doit retourner PDO object
```

### Tester les seeders
```bash
php artisan db:seed --class=RolesTableSeeder
php artisan db:seed --class=TestUsersSeeder
```

### Vérifier les migrations manquantes
```bash
php artisan migrate:status | grep "Pending"
```

---

## 📊 DATA INTEGRITY CHECK

```bash
# Vérifier les données critiques
php artisan tinker

# Check roles
> Role::pluck('name')
> User::where('role', 'super_admin')->count()

# Check migrations recorded
> DB::table('migrations')->count()  # Doit être ~219
```

---

## 🛠️ EN CAS DE BLOCAGE

### Si migrations bloquées:
```bash
php artisan migrate:reset       # Annuler tout
php artisan migrate:fresh      # Recommencer from scratch
```

### Si DB "corrupted":
```bash
# Backup first
mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > backup.sql

# Reset
php artisan migrate:reset
php artisan migrate:fresh --seed
```

### Si Redis cause des problèmes:
```bash
# Vérifier
redis-cli ping  # Doit retourner "PONG"

# Si KO, utiliser les drivers file/array
# Dans .env:
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

---

## 📝 FICHIERS À SURVEILLER

Après les changements, vérifier:

```
✅ .env                             (APP_KEY updated)
✅ storage/logs/laravel.log        (Pas d'erreurs)
✅ database/database.sqlite        (Créé et valide)
✅ database/migrations/             (Toutes exécutées)
```

---

## 🎯 PROCHAINES ÉTAPES

1. Exécuter: `php artisan key:generate`
2. Exécuter: `php artisan migrate:fresh --seed`
3. Tester: `php artisan tinker` + queries
4. Committer: Les changements si tout OK
5. Pousser: `git push` si en production

---

## 📞 EN CAS DE QUESTION

Consulter:
- `DATABASE_MIGRATION_REPORT.md` - Rapport complet
- `storage/logs/laravel.log` - Logs d'erreurs
- `storage/logs/laravel-2026-*.log` - Archives logs

---

**Status:** 🟡 EN ATTENTE D'ACTIONS  
**Responsable:** Claude (next turn)  
**Urgence:** 🔴 CRITIQUE (APP_KEY)
