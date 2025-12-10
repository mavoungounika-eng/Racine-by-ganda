# 📦 INSTRUCTIONS D'INSTALLATION - LARAVEL SOCIALITE

## Installation du Package

### 1. Installer Socialite

```bash
composer require laravel/socialite
```

**Note :** Le package a déjà été ajouté à `composer.json`. Exécutez simplement :

```bash
composer install
```

---

## Configuration Google OAuth

### 1. Créer un Projet Google Cloud Console

1. Aller sur [Google Cloud Console](https://console.cloud.google.com)
2. Créer un nouveau projet (ou utiliser un existant)
3. Activer l'API **Google+ API** (ou **Google Identity API**)
4. Aller dans **Identifiants** → **Créer des identifiants** → **ID client OAuth 2.0**

### 2. Configurer l'ID Client OAuth

1. **Type d'application :** Application Web
2. **Nom :** RACINE BY GANDA (ou votre choix)
3. **URI de redirection autorisés :**
   - Développement : `http://localhost/auth/google/callback`
   - Production : `https://votre-domaine.com/auth/google/callback`

### 3. Récupérer les Identifiants

Après création, vous obtiendrez :
- **ID client** (Client ID)
- **Secret client** (Client Secret)

### 4. Configurer les Variables d'Environnement

Ajouter dans votre fichier `.env` :

```env
GOOGLE_CLIENT_ID=votre_client_id_ici
GOOGLE_CLIENT_SECRET=votre_client_secret_ici
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

**Note :** `GOOGLE_REDIRECT_URI` est optionnel, il utilise par défaut `${APP_URL}/auth/google/callback`.

---

## Vérification

### Tester la Configuration

1. Vérifier que Socialite est installé :
   ```bash
   composer show laravel/socialite
   ```

2. Vérifier la configuration :
   ```bash
   php artisan tinker
   >>> config('services.google')
   ```

3. Tester la redirection :
   - Aller sur `/login`
   - Cliquer sur "Continuer avec Google"
   - Vérifier la redirection vers Google

---

## Dépannage

### Erreur : "Class 'Laravel\Socialite\Facades\Socialite' not found"

**Solution :**
```bash
composer require laravel/socialite
composer dump-autoload
```

### Erreur : "Invalid client credentials"

**Solution :**
- Vérifier que `GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` sont corrects dans `.env`
- Vérifier que l'URI de redirection dans Google Console correspond à celui dans `.env`
- Exécuter `php artisan config:clear`

### Erreur : "Redirect URI mismatch"

**Solution :**
- Vérifier que l'URI dans Google Console correspond exactement à :
  - `http://localhost/auth/google/callback` (dev)
  - `https://votre-domaine.com/auth/google/callback` (prod)
- Les URIs doivent correspondre **exactement** (pas de slash final, pas d'espace)

---

## Sécurité

### Bonnes Pratiques

1. **Ne jamais commiter les credentials :**
   - Les variables `GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` doivent rester dans `.env`
   - Ajouter `.env` au `.gitignore` (déjà fait normalement)

2. **Utiliser des identifiants différents :**
   - Un jeu pour le développement
   - Un jeu pour la production

3. **Restreindre les domaines autorisés :**
   - Dans Google Console, restreindre les domaines autorisés si possible

---

**Fin des Instructions**


