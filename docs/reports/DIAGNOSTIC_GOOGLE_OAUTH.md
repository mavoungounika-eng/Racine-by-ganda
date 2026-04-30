# 🔍 DIAGNOSTIC ET CORRECTION - GOOGLE OAUTH (SOCIALITE)

## ✅ DIAGNOSTIC CONFIRMÉ

**Cause principale identifiée :** Les variables d'environnement `GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` ne sont **pas définies** dans le fichier `.env`.

**Preuve du diagnostic :**
```php
config('services.google') = [
    "client_id" => null,
    "client_secret" => null,
    "redirect" => "http://localhost:8000/auth/google/callback"
]
```

**Explication :** Laravel Socialite charge automatiquement la configuration depuis `config('services.google')`, qui lit les variables d'environnement via `env('GOOGLE_CLIENT_ID')` et `env('GOOGLE_CLIENT_SECRET')`. Si ces variables sont absentes du `.env`, elles retournent `null`, et Google reçoit une requête OAuth sans `client_id`, d'où l'erreur **400 - invalid_request: Missing required parameter: client_id**.

---

## 🔧 CORRECTION EXACTE

### Étape 1 : Ajouter les variables dans `.env`

Ouvrir le fichier `.env` à la racine du projet et ajouter :

```env
GOOGLE_CLIENT_ID=votre_client_id_google_ici
GOOGLE_CLIENT_SECRET=votre_client_secret_google_ici
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

**Note :** `GOOGLE_REDIRECT_URI` est optionnel (valeur par défaut utilisée si absent), mais recommandé pour la clarté.

### Étape 2 : Obtenir les credentials Google OAuth

Si vous n'avez pas encore les credentials :

1. Aller sur [Google Cloud Console](https://console.cloud.google.com)
2. Créer un projet (ou utiliser un existant)
3. Activer l'API **Google Identity API**
4. Aller dans **Identifiants** → **Créer des identifiants** → **ID client OAuth 2.0**
5. Type : **Application Web**
6. **URI de redirection autorisés :**
   - Développement : `http://127.0.0.1:8000/auth/google/callback`
   - Production : `https://votre-domaine.com/auth/google/callback`
7. Copier le **ID client** et le **Secret client**

### Étape 3 : Nettoyer les caches Laravel

Exécuter ces commandes dans le terminal :

```bash
php artisan config:clear
php artisan cache:clear
```

**Important :** Ces commandes sont nécessaires pour que Laravel recharge les nouvelles variables d'environnement.

---

## ✅ CHECKLIST DE VALIDATION

### 1. Vérifier les variables dans `.env`
- [ ] `GOOGLE_CLIENT_ID` est défini et non vide
- [ ] `GOOGLE_CLIENT_SECRET` est défini et non vide
- [ ] `GOOGLE_REDIRECT_URI` est défini (optionnel mais recommandé)

### 2. Vérifier la configuration Laravel
```bash
php artisan tinker
>>> config('services.google')
```

**Résultat attendu :**
```php
[
    "client_id" => "votre_client_id_ici",
    "client_secret" => "votre_client_secret_ici",
    "redirect" => "http://127.0.0.1:8000/auth/google/callback"
]
```

**Si `client_id` ou `client_secret` est `null` :**
- Vérifier que les variables sont bien dans `.env` (sans espaces autour du `=`)
- Vérifier qu'il n'y a pas de guillemets autour des valeurs
- Exécuter `php artisan config:clear` à nouveau

### 3. Tester la redirection OAuth
1. Aller sur `/login?context=boutique`
2. Cliquer sur "Continuer avec Google"
3. **Résultat attendu :** Redirection vers Google OAuth (page de connexion Google)
4. **Si erreur 400 persiste :** Vérifier que les credentials Google sont corrects dans la Google Cloud Console

---

## 📝 MODIFICATIONS APPORTÉES

### Fichier modifié : `app/Http/Controllers/Auth/GoogleAuthController.php`

**Ajout d'une vérification de configuration** (lignes 74-82) :

```php
// Vérifier que la configuration Google OAuth est complète
$googleConfig = config('services.google');
if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
    \Log::warning('Google OAuth: Configuration incomplète', [
        'client_id_set' => !empty($googleConfig['client_id']),
        'client_secret_set' => !empty($googleConfig['client_secret']),
    ]);
    return redirect()->route('login', ['context' => 'boutique'])
        ->with('error', 'La connexion Google n\'est pas configurée. Contactez l\'administrateur.');
}
```

**Bénéfice :** Message d'erreur clair pour l'utilisateur si la configuration est manquante, au lieu d'une erreur 400 cryptique de Google.

---

## 🎯 RÉSULTAT ATTENDU

Une fois les variables ajoutées dans `.env` et les caches nettoyés :

✅ La route `/auth/google/redirect` redirige correctement vers Google OAuth  
✅ Google affiche la page de connexion (pas d'erreur 400)  
✅ Après connexion Google, l'utilisateur est redirigé vers `/auth/google/callback`  
✅ Le callback crée/connecte l'utilisateur et redirige selon le rôle

---

## ⚠️ NOTES IMPORTANTES

1. **Ne pas commiter le `.env`** : Le fichier `.env` contient des secrets et ne doit jamais être versionné.

2. **Variables d'environnement en production :** En production, configurer les variables directement sur le serveur (via `.env` ou variables d'environnement système selon votre hébergement).

3. **URI de redirection :** L'URI de redirection dans Google Cloud Console doit **exactement** correspondre à celui utilisé par l'application (y compris le protocole `http://` vs `https://`).

---

## 🔄 COMMANDES TERMINALES FINALES

```bash
# 1. Vérifier que les variables sont bien dans .env
# (ouvrir .env et vérifier manuellement)

# 2. Nettoyer les caches
php artisan config:clear
php artisan cache:clear

# 3. Vérifier la configuration (optionnel)
php artisan tinker
>>> config('services.google')
>>> exit

# 4. Tester la route
# Aller sur http://127.0.0.1:8000/auth/google/redirect
```

---

**Date de correction :** $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")  
**Statut :** ✅ Diagnostic complet - Correction prête à appliquer

