# 🔐 COMPTES D'AUTHENTIFICATION - RACINE BY GANDA

**Date de mise à jour :** 10 décembre 2025  
**Fichier source :** `database/seeders/TestUsersSeeder.php`

---

## 📝 MOT DE PASSE UNIQUE

**Pour TOUS les comptes :** `password`

---

## 👑 ADMINISTRATEURS

### 1. Super Administrateur
- **Email :** `superadmin@racine.cm`
- **Nom :** Super Admin RACINE
- **Rôle :** `super_admin` (ID: 1)
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Accès complet à toutes les fonctionnalités
- **Route de connexion :** `/admin/login` ou `/auth`

---

### 2. Administrateur
- **Email :** `admin@racine.cm`
- **Nom :** Admin RACINE
- **Rôle :** `admin` (ID: 2)
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Administration standard (utilisateurs, produits, commandes)
- **Route de connexion :** `/admin/login` ou `/auth`

---

## 👥 STAFF (Personnel)

### 3. Staff Général
- **Email :** `staff@racine.cm`
- **Nom :** Staff RACINE
- **Rôle :** `staff` (ID: 3)
- **Sous-rôle :** Aucun (`staff_role: null`)
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Outils internes ERP
- **Route de connexion :** `/erp/login` ou `/auth`

---

### 4. Staff Vendeur
- **Email :** `vendeur@racine.cm`
- **Nom :** Vendeur RACINE
- **Rôle :** `staff` (ID: 3)
- **Sous-rôle :** `vendeur`
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Ventes, commandes
- **Route de connexion :** `/erp/login` ou `/auth`

---

### 5. Staff Caissier
- **Email :** `caissier@racine.cm`
- **Nom :** Caissier RACINE
- **Rôle :** `staff` (ID: 3)
- **Sous-rôle :** `caissier`
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Paiements, transactions
- **Route de connexion :** `/erp/login` ou `/auth`

---

### 6. Staff Gestionnaire Stock
- **Email :** `stock@racine.cm`
- **Nom :** Gestionnaire Stock RACINE
- **Rôle :** `staff` (ID: 3)
- **Sous-rôle :** `gestionnaire_stock`
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Gestion des stocks, ERP
- **Route de connexion :** `/erp/login` ou `/auth`

---

### 7. Staff Comptable
- **Email :** `comptable@racine.cm`
- **Nom :** Comptable RACINE
- **Rôle :** `staff` (ID: 3)
- **Sous-rôle :** `comptable`
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Finances, comptabilité
- **Route de connexion :** `/erp/login` ou `/auth`

---

## 🎨 CRÉATEURS (Marketplace)

### 8. Créateur Actif ✅
- **Email :** `createur@racine.cm`
- **Nom :** Créateur Test
- **Rôle :** `createur` (ID: 4)
- **Statut utilisateur :** ✅ Actif
- **Statut profil :** ✅ `active`
- **Vérifié :** ✅ Oui (`is_verified: true`)
- **Boutique :** Boutique Test Créateur
- **Slug boutique :** `boutique-test-createur`
- **2FA :** ❌ Désactivé
- **Accès :** Dashboard créateur complet
- **Route de connexion :** `/createur/login` ou `/login`

---

### 9. Créateur En Attente ⏳
- **Email :** `createur.pending@racine.cm`
- **Nom :** Créateur Pending
- **Rôle :** `createur` (ID: 4)
- **Statut utilisateur :** ✅ Actif
- **Statut profil :** ⏳ `pending`
- **Vérifié :** ❌ Non (`is_verified: false`)
- **Boutique :** Boutique Pending
- **Slug boutique :** `boutique-pending`
- **2FA :** ❌ Désactivé
- **Accès :** Page d'attente (`/createur/pending`)
- **Route de connexion :** `/createur/login` ou `/login`

---

### 10. Créateur Suspendu 🚫
- **Email :** `createur.suspended@racine.cm`
- **Nom :** Créateur Suspended
- **Rôle :** `createur` (ID: 4)
- **Statut utilisateur :** ✅ Actif
- **Statut profil :** 🚫 `suspended`
- **Vérifié :** ❌ Non (`is_verified: false`)
- **Boutique :** Boutique Suspended
- **Slug boutique :** `boutique-suspended`
- **2FA :** ❌ Désactivé
- **Accès :** Page de suspension (`/createur/suspended`)
- **Route de connexion :** `/createur/login` ou `/login`

---

## 🛒 CLIENTS

### 11. Client Test 1
- **Email :** `client@racine.cm`
- **Nom :** Client Test 1
- **Rôle :** `client` (ID: 5)
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Boutique, commandes, profil
- **Route de connexion :** `/login` ou `/auth`

---

### 12. Client Test 2
- **Email :** `client2@racine.cm`
- **Nom :** Client Test 2
- **Rôle :** `client` (ID: 5)
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Boutique, commandes, profil
- **Route de connexion :** `/login` ou `/auth`

---

### 13. Client Test 3
- **Email :** `client3@racine.cm`
- **Nom :** Client Test 3
- **Rôle :** `client` (ID: 5)
- **Statut :** ✅ Actif
- **2FA :** ❌ Désactivé
- **Accès :** Boutique, commandes, profil
- **Route de connexion :** `/login` ou `/auth`

---

## 📊 RÉCAPITULATIF PAR RÔLE

| Rôle | Nombre | Emails |
|------|--------|--------|
| **Super Admin** | 1 | `superadmin@racine.cm` |
| **Admin** | 1 | `admin@racine.cm` |
| **Staff** | 5 | `staff@racine.cm`, `vendeur@racine.cm`, `caissier@racine.cm`, `stock@racine.cm`, `comptable@racine.cm` |
| **Créateur** | 3 | `createur@racine.cm`, `createur.pending@racine.cm`, `createur.suspended@racine.cm` |
| **Client** | 3 | `client@racine.cm`, `client2@racine.cm`, `client3@racine.cm` |
| **TOTAL** | **13 comptes** | |

---

## 🔄 CRÉATION DES COMPTES

### Commande pour créer tous les comptes :

```bash
php artisan db:seed --class=TestUsersSeeder
```

### Ou via DatabaseSeeder :

```bash
php artisan db:seed
```

**Note :** Le seeder `TestUsersSeeder` :
- ✅ Appelle automatiquement `RolesTableSeeder` pour créer les rôles
- ✅ Supprime les anciens comptes de test avant de créer les nouveaux
- ✅ Crée ou met à jour les comptes (idempotent)
- ✅ Crée les profils créateurs associés

---

## 🛡️ SÉCURITÉ

### Informations importantes :

1. **Mot de passe unique :** Tous les comptes utilisent `password` (à changer en production)
2. **2FA désactivé :** Tous les comptes ont `two_factor_required: false`
3. **Emails vérifiés :** Tous les comptes ont `email_verified_at` défini
4. **Statut actif :** Tous les utilisateurs ont `status: 'active'`

### ⚠️ PRODUCTION

**En production, il est recommandé de :**
- Changer tous les mots de passe
- Activer 2FA pour les comptes admin
- Désactiver ou supprimer les comptes de test
- Utiliser des emails réels pour les comptes admin

---

## 📍 ROUTES DE CONNEXION

### Routes disponibles :

| Type d'utilisateur | Routes |
|-------------------|--------|
| **Tous** | `/auth` (Hub de sélection) |
| **Clients & Créateurs** | `/login`, `/createur/login` |
| **Admin** | `/admin/login` |
| **Staff/ERP** | `/erp/login` |

---

## 🔍 VÉRIFICATION DES COMPTES

### Vérifier qu'un compte existe :

```bash
php artisan tinker
```

```php
User::where('email', 'superadmin@racine.cm')->first();
User::where('email', 'createur@racine.cm')->with('creatorProfile')->first();
```

### Lister tous les comptes de test :

```php
User::whereIn('email', [
    'superadmin@racine.cm',
    'admin@racine.cm',
    'staff@racine.cm',
    'vendeur@racine.cm',
    'caissier@racine.cm',
    'stock@racine.cm',
    'comptable@racine.cm',
    'createur@racine.cm',
    'createur.pending@racine.cm',
    'createur.suspended@racine.cm',
    'client@racine.cm',
    'client2@racine.cm',
    'client3@racine.cm',
])->get(['id', 'name', 'email', 'role', 'status']);
```

---

## 📝 NOTES

- **Téléphone :** Tous les comptes ont `phone: '+237 6XX XXX XXX'` (format Cameroun)
- **Domaine email :** Tous les emails utilisent `@racine.cm`
- **Profils créateurs :** Les 3 comptes créateurs ont des profils `CreatorProfile` associés
- **Idempotence :** Le seeder peut être exécuté plusieurs fois sans créer de doublons

---

**Dernière mise à jour :** 10 décembre 2025  
**Fichier source :** `database/seeders/TestUsersSeeder.php`

