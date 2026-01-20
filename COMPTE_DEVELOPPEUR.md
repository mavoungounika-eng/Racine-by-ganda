# 🔑 COMPTE DÉVELOPPEUR PASSE-PARTOUT

## ✅ COMPTE CRÉÉ/MIS À JOUR

### Identifiants de Connexion

**Email :** `dev@racine.com`  
**Password :** `dev123`  
**Nom :** `Developer`

### Accès

**URL Panel Admin :** `http://localhost:8000/admin/login`

---

## 🚀 UTILISATION

### Se connecter

1. Démarrer le serveur :
   ```bash
   php artisan serve
   ```

2. Ouvrir dans le navigateur :
   ```
   http://localhost:8000/admin/login
   ```

3. Se connecter avec :
   - Email : `dev@racine.com`
   - Password : `dev123`

---

## 🔧 MISE À JOUR DU COMPTE

### Commande Artisan

```bash
php artisan dev:account
```

### Options personnalisées

```bash
php artisan dev:account --email=votre@email.com --password=votrepassword --name="Votre Nom"
```

---

## ✅ PERMISSIONS

Le compte développeur a :
- ✅ Accès complet au panel admin
- ✅ Tous les droits administrateur
- ✅ Accès à tous les modules
- ✅ `is_admin = true`
- ✅ `role_id = 1` (ou rôle admin)

---

## 🔄 RÉINITIALISER LE COMPTE

Si vous voulez réinitialiser le mot de passe :

```bash
php artisan dev:account --password=nouveaupassword
```

---

## 📋 VÉRIFICATION

Pour vérifier que le compte existe :

```bash
php artisan tinker
```

Puis :
```php
$user = \App\Models\User::where('email', 'dev@racine.com')->first();
echo $user ? "Compte trouvé: {$user->name}" : "Compte non trouvé";
```

---

**✅ Compte développeur prêt à l'emploi !**

*Mis à jour le : 28 novembre 2025*

