# ✅ CHECKLIST DÉPLOIEMENT PRODUCTION
## Module : Authentification Google (Client & Créateur)

**Date :** 2025-12-19  
**Projet :** RACINE BY GANDA  
**Environnement :** Production  
**Statut :** ⚠️ **À VALIDER AVANT DÉPLOIEMENT**

---

## 🔴 CRITIQUE : VARIABLES D'ENVIRONNEMENT GOOGLE OAUTH

### Variables Requises dans `.env`

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=votre_client_id_production
GOOGLE_CLIENT_SECRET=votre_client_secret_production
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### ✅ Checklist Variables

- [ ] `GOOGLE_CLIENT_ID` configuré avec l'ID client **PRODUCTION**
- [ ] `GOOGLE_CLIENT_SECRET` configuré avec le secret **PRODUCTION**
- [ ] `GOOGLE_REDIRECT_URI` correspond exactement à l'URL de callback
- [ ] `APP_URL` correspond au domaine de production
- [ ] **AUCUN** identifiant de développement/staging dans `.env` production

### ⚠️ Vérification Google Cloud Console

- [ ] Projet Google Cloud Console créé/configuré
- [ ] API Google Identity activée
- [ ] ID client OAuth 2.0 créé pour **PRODUCTION**
- [ ] URI de redirection autorisée : `https://votre-domaine.com/auth/google/callback`
- [ ] **EXACTEMENT** la même URL (pas de slash final, pas d'espace)
- [ ] Domaine autorisé configuré si nécessaire

---

## 🔴 CRITIQUE : URL CALLBACK EXACTE

### URL Callback Production

```
https://votre-domaine.com/auth/google/callback
```

### ✅ Checklist URL

- [ ] URL dans `.env` (`GOOGLE_REDIRECT_URI`) = URL dans Google Console
- [ ] URL dans Google Console = URL réelle de production
- [ ] Pas de slash final
- [ ] Pas d'espace
- [ ] Protocole HTTPS (obligatoire en production)
- [ ] Domaine correct (pas de localhost, pas de staging)

### Test de Validation

```bash
# Vérifier la configuration
php artisan tinker
>>> config('services.google')
```

**Attendu :**
```php
[
    "client_id" => "votre_client_id_production",
    "client_secret" => "votre_client_secret_production",
    "redirect" => "https://votre-domaine.com/auth/google/callback"
]
```

---

## 🔴 CRITIQUE : MIGRATION BASE DE DONNÉES

### Migration Requise

**Fichier :** `database/migrations/2025_12_19_143528_add_google_id_to_users_table.php`

### ✅ Checklist Migration

- [ ] Migration testée en staging
- [ ] Migration exécutée en production : `php artisan migrate`
- [ ] Colonne `google_id` créée dans table `users`
- [ ] Contrainte `unique` appliquée
- [ ] Index créé
- [ ] Aucune erreur lors de la migration
- [ ] Rollback testé : `php artisan migrate:rollback` (si nécessaire)

### Vérification Post-Migration

```sql
-- Vérifier la structure
DESCRIBE users;

-- Vérifier que google_id existe
SHOW COLUMNS FROM users LIKE 'google_id';

-- Vérifier l'index
SHOW INDEX FROM users WHERE Column_name = 'google_id';
```

**Attendu :**
- Colonne `google_id` : `varchar(255)`, `NULL`, `UNIQUE`
- Index sur `google_id` présent

---

## 🔴 CRITIQUE : CACHE & CONFIG CLEAR

### Commandes Obligatoires

```bash
# Nettoyer le cache de configuration
php artisan config:clear

# Nettoyer le cache de routes
php artisan route:clear

# Nettoyer le cache d'application
php artisan cache:clear

# Optimiser pour production (optionnel mais recommandé)
php artisan config:cache
php artisan route:cache
```

### ✅ Checklist Cache

- [ ] `php artisan config:clear` exécuté
- [ ] `php artisan route:clear` exécuté
- [ ] `php artisan cache:clear` exécuté
- [ ] Cache optimisé pour production (`config:cache`, `route:cache`)
- [ ] Redémarrage du serveur web si nécessaire

---

## 🔴 CRITIQUE : LOGS AUTHENTIFICATION

### Configuration Logs

**Fichier :** `config/logging.php`

### ✅ Checklist Logs

- [ ] Logs d'authentification activés
- [ ] Canal de logs configuré (fichier, syslog, etc.)
- [ ] Niveau de log approprié (production : `error` minimum)
- [ ] Rotation des logs configurée
- [ ] Accès aux logs sécurisé

### Logs à Surveiller

- [ ] Tentatives de connexion Google OAuth
- [ ] Erreurs de callback OAuth
- [ ] Échecs de validation state
- [ ] Tentatives account takeover (google_id conflict)
- [ ] Conflits de rôle détectés

### Exemple de Monitoring

```bash
# Surveiller les logs en temps réel
tail -f storage/logs/laravel.log | grep -i "google\|oauth\|auth"
```

---

## 🔴 CRITIQUE : PLAN ROLLBACK

### Scénario de Rollback

Si un problème critique survient après déploiement :

### ✅ Checklist Rollback

- [ ] Migration rollback testée : `php artisan migrate:rollback --step=1`
- [ ] Code de rollback préparé (version précédente)
- [ ] Variables `.env` de secours documentées
- [ ] Procédure de rollback documentée
- [ ] Temps estimé de rollback : **< 5 minutes**

### Procédure de Rollback

1. **Désactiver Google OAuth temporairement :**
   ```env
   # Commenter les variables Google
   # GOOGLE_CLIENT_ID=...
   # GOOGLE_CLIENT_SECRET=...
   ```

2. **Rollback migration (si nécessaire) :**
   ```bash
   php artisan migrate:rollback --step=1
   ```

3. **Rétablir version précédente du code**

4. **Nettoyer les caches :**
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

5. **Redémarrer le serveur web**

---

## 🟠 IMPORTANT : TESTS POST-DÉPLOIEMENT

### Tests à Effectuer Immédiatement Après Déploiement

- [ ] Test connexion Google Client (nouveau compte)
- [ ] Test connexion Google Client (compte existant)
- [ ] Test connexion Google Créateur (nouveau compte)
- [ ] Test connexion Google Créateur (compte existant)
- [ ] Test protection state OAuth (tentative avec state invalide)
- [ ] Test protection account takeover (tentative avec google_id différent)
- [ ] Test conflit de rôle (tentative cross-rôle)
- [ ] Vérification logs d'erreur (aucune erreur critique)

### URLs de Test

```
https://votre-domaine.com/auth/google/redirect/client
https://votre-domaine.com/auth/google/redirect/creator
https://votre-domaine.com/auth/google/callback?state=...
```

---

## 🟠 IMPORTANT : SÉCURITÉ PRODUCTION

### ✅ Checklist Sécurité

- [ ] HTTPS obligatoire (pas de HTTP)
- [ ] Cookies sécurisés (`SESSION_SECURE_COOKIE=true`)
- [ ] Rate limiting activé sur routes OAuth
- [ ] Logs d'audit activés
- [ ] Monitoring des tentatives d'attaque
- [ ] Alertes configurées pour erreurs critiques

### Variables de Sécurité

```env
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

---

## 🟡 RECOMMANDÉ : MONITORING & ALERTES

### Métriques à Surveiller

- [ ] Taux de succès connexion Google OAuth
- [ ] Nombre d'erreurs OAuth par heure
- [ ] Temps de réponse callback OAuth
- [ ] Tentatives account takeover détectées
- [ ] Conflits de rôle détectés

### Alertes à Configurer

- [ ] Alerte si taux d'erreur OAuth > 5%
- [ ] Alerte si tentative account takeover détectée
- [ ] Alerte si callback OAuth échoue > 10 fois/heure

---

## ✅ VALIDATION FINALE

### Avant de Marquer "DÉPLOYÉ"

- [ ] Toutes les cases critiques (🔴) cochées
- [ ] Toutes les cases importantes (🟠) cochées
- [ ] Tests post-déploiement réussis
- [ ] Aucune erreur dans les logs
- [ ] Monitoring configuré
- [ ] Plan de rollback validé

### Signature

**Validé par :** _________________  
**Date :** _________________  
**Heure :** _________________  

---

## 📝 NOTES POST-DÉPLOIEMENT

### Observations

```
[À remplir après déploiement]
```

### Problèmes Rencontrés

```
[À remplir si problèmes]
```

### Actions Correctives

```
[À remplir si actions correctives]
```

---

**Fin de la Checklist**



