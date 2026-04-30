# 🔓 DÉSACTIVER LA 2FA POUR LE SUPER ADMIN

## ✅ SOLUTION IMPLÉMENTÉE

### 1. Bypass Automatique en Développement

**Environnement local :** La 2FA est automatiquement bypassée pour tous les comptes admin en développement.

**Fichiers modifiés :**
- `app/Services/TwoFactorService.php` - `isRequired()` retourne `false` en local
- `app/Http/Controllers/Admin/AdminAuthController.php` - Bypass automatique si 2FA activée

### 2. Commande pour Désactiver la 2FA

**Commande :**
```bash
php artisan 2fa:disable {email}
```

**Exemples :**
```bash
php artisan 2fa:disable admin@racine.com
php artisan 2fa:disable dev@racine.com
```

---

## 🚀 UTILISATION

### Option 1 : Bypass Automatique (Recommandé)

En environnement `local`, la 2FA est automatiquement bypassée. Aucune action nécessaire !

**Vérifier l'environnement :**
```bash
php artisan tinker
```
```php
echo app()->environment(); // Doit retourner "local"
```

### Option 2 : Désactiver Complètement

Si vous voulez désactiver la 2FA pour un compte spécifique :

```bash
php artisan 2fa:disable admin@racine.com
```

Cela va :
- ✅ Supprimer le secret 2FA
- ✅ Supprimer les codes de récupération
- ✅ Désactiver `two_factor_required`
- ✅ Nettoyer les tokens d'appareil de confiance

---

## 🔧 CONFIGURATION

### Vérifier l'Environnement

Dans `.env`, assurez-vous que :
```env
APP_ENV=local
```

### Vérifier que la 2FA est Désactivée

```bash
php artisan tinker
```

Puis :
```php
$user = \App\Models\User::where('email', 'admin@racine.com')->first();
echo "2FA activée : " . ($user->two_factor_secret ? "OUI" : "NON") . PHP_EOL;
echo "2FA requise : " . ($user->two_factor_required ? "OUI" : "NON") . PHP_EOL;
```

---

## 📋 COMPTES MIS À JOUR

Les comptes suivants ont été mis à jour :
- ✅ `admin@racine.com` - 2FA désactivée
- ✅ `dev@racine.com` - 2FA désactivée

---

## ⚠️ IMPORTANT

### Développement
- ✅ Bypass automatique en `local`
- ✅ Commande disponible pour désactiver

### Production
- ⚠️ La 2FA reste obligatoire pour les admins
- ⚠️ Le bypass ne fonctionne PAS en production
- ⚠️ Changez `APP_ENV=production` en production

---

## 🎯 ACCÈS IMMÉDIAT

1. **Vérifier l'environnement :**
   ```bash
   # Dans .env
   APP_ENV=local
   ```

2. **Se connecter :**
   ```
   http://localhost:8000/admin/login
   ```
   - Email : `admin@racine.com`
   - Password : `admin123`

3. **Accès direct au dashboard** (pas de code 2FA requis en local)

---

## 🔄 RÉACTIVER LA 2FA

Si vous voulez réactiver la 2FA plus tard :

1. Aller dans le panel admin
2. Section Sécurité / 2FA
3. Configurer la 2FA normalement

---

**✅ La 2FA est maintenant bypassée automatiquement en développement !**

*Mis à jour le : 28 novembre 2025*

