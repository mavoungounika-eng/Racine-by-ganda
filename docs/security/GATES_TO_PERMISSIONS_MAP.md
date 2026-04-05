# Gates to Permissions Mapping

**Date de création** : 2026-01-20  
**Statut** : ✅ VERROUILLÉ  
**Version** : 1.0 (FINAL)

---

## 📋 Vue d'Ensemble

Ce document formalise le mapping entre les **Gates** et les **Permissions** dans le système RBAC de RACINE BY GANDA.

### Statistiques

| Métrique | Valeur |
|----------|--------|
| **Total Gates** | 33 |
| **Mappings directs** | 23 (70%) |
| **Mappings indirects** | 10 (30%) |
| **Gates hors RBAC** | 3 (super_admin, créateur, client) |

---

## ✅ Mappings Directs (23 Gates)

Ces Gates utilisent une permission avec le **même nom** :

### Products (4)
- `view-products` → `view-products`
- `create-products` → `create-products`
- `edit-products` → `edit-products`
- `delete-products` → `delete-products`

### Orders (4)
- `view-orders` → `view-orders`
- `view-all-orders` → `view-all-orders`
- `edit-orders` → `edit-orders`
- `delete-orders` → `delete-orders`

### Users (4)
- `view-users` → `view-users`
- `create-users` → `create-users`
- `edit-users` → `edit-users`
- `delete-users` → `delete-users`

### Categories (4)
- `view-categories` → `view-categories`
- `create-categories` → `create-categories`
- `edit-categories` → `edit-categories`
- `delete-categories` → `delete-categories`

### Analytics (3)
- `view-analytics` → `view-sales-analytics`
- `view-sales-analytics` → `view-sales-analytics`
- `view-stock-analytics` → `view-stock-analytics`

### Stock (2)
- `view-stock` → `view-stock`
- `edit-stock` → `edit-stock`

### System (2)
- `manage-settings` → `manage-settings`
- `access-system-config` → `access-system-config`

### Payments (1)
- `process-payments` → `process-payments`

---

## ⚠️ Mappings Indirects (10 Gates)

Ces Gates utilisent une permission **différente** pour des raisons métier justifiées.

---

### 1. `access-admin`

**Permission mappée** : `view-users`

**Justification métier** :
L'accès à l'espace administrateur implique nécessairement la capacité de consulter ou gérer les utilisateurs. Une personne sans visibilité sur les utilisateurs n'a pas de raison légitime d'accéder au dashboard admin, car la gestion des utilisateurs est une fonction centrale de l'administration.

**Rôles concernés** : `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:95`

---

### 2. `access-staff`

**Permission mappée** : `view-all-orders`

**Justification métier** :
L'accès à l'espace staff est conditionné par la capacité de voir toutes les commandes, car le rôle principal du staff est de traiter et suivre les commandes clients. Un membre du staff sans cette permission ne pourrait pas remplir ses fonctions opérationnelles.

**Rôles concernés** : `staff`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:96`

---

### 3. `access-staff-tools`

**Permission mappée** : `view-all-orders`

**Justification métier** :
Les outils staff (rapports, exports, statistiques) sont directement liés au traitement des commandes. Cette permission garantit que seuls les membres ayant accès aux commandes peuvent utiliser les outils associés.

**Rôles concernés** : `staff`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:97`

**Note** : Ce gate est un alias de `access-staff` pour des raisons de clarté sémantique dans le code.

---

### 4. `access-erp`

**Permission mappée** : `view-stock`

**Justification métier** :
Le module ERP est principalement centré sur la gestion des stocks et des inventaires. L'accès au module ERP nécessite donc la permission de consulter le stock. Un utilisateur sans cette permission n'aurait aucune utilité à accéder au module ERP.

**Rôles concernés** : `gestionnaire_stock`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:98`

---

### 5. `access-crm`

**Permission mappée** : `view-users`

**Justification métier** :
Le module CRM (Customer Relationship Management) est dédié à la gestion de la relation client, ce qui implique nécessairement la consultation des utilisateurs/clients. Sans cette permission, l'accès au CRM n'a pas de sens fonctionnel.

**Rôles concernés** : `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:100`

---

### 6. `manage-erp`

**Permission mappée** : `edit-stock`

**Justification métier** :
La gestion du module ERP (ajout de produits, modification d'inventaire, mouvements de stock) nécessite la permission de modifier le stock. Ce mapping est logique et cohérent avec la fonction principale du module.

**Rôles concernés** : `gestionnaire_stock`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:99`

---

### 7. `manage-crm`

**Permission mappée** : `edit-users`

**Justification métier** :
La gestion du CRM (modification de profils clients, ajout de notes, gestion de segments) nécessite la permission de modifier les utilisateurs. Ce mapping est cohérent avec les fonctions de gestion client.

**Rôles concernés** : `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:101`

---

### 8. `view-dashboard`

**Permission mappée** : `view-all-orders`

**Justification métier** :
Le dashboard principal affiche des métriques et statistiques centrées sur les commandes (CA, nombre de commandes, statuts). L'accès au dashboard nécessite donc la permission de voir toutes les commandes pour afficher des données pertinentes.

**Rôles concernés** : `staff`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:70`

---

### 9. `payments.view`

**Permission mappée** : `view-orders`

**Justification métier** :
La consultation des paiements est intrinsèquement liée à la consultation des commandes, car chaque paiement est associé à une commande. Cette permission garantit que seuls les utilisateurs pouvant voir les commandes peuvent voir les paiements associés.

**Rôles concernés** : `vendeur`, `caissier`, `staff`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:84`

---

### 10. `payments.config`

**Permission mappée** : `manage-settings`

**Justification métier** :
La configuration des moyens de paiement (Stripe, Mobile Money, etc.) est une fonction de paramétrage système. Elle nécessite donc la permission de gérer les paramètres globaux de l'application.

**Rôles concernés** : `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:85`

---

### 11. `payments.reprocess`

**Permission mappée** : `process-payments`

**Justification métier** :
Le retraitement d'un paiement échoué nécessite les mêmes privilèges que le traitement initial d'un paiement. Ce mapping garantit la cohérence des permissions liées aux opérations de paiement.

**Rôles concernés** : `caissier`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:86`

---

### 12. `payments.refund`

**Permission mappée** : `process-payments`

**Justification métier** :
Le remboursement d'un paiement est une opération sensible qui nécessite les mêmes privilèges que le traitement de paiement. Ce mapping garantit que seuls les utilisateurs autorisés à traiter les paiements peuvent effectuer des remboursements.

**Rôles concernés** : `caissier`, `admin`, `super_admin`

**Fichier** : `AuthServiceProvider.php:87`

---

## 🚫 Gates Hors RBAC (3 Gates)

Ces Gates utilisent une **logique de rôle** et ne font **PAS** partie du système RBAC staff :

### 1. `access-super-admin`

**Logique** : `$user->getRoleSlug() === 'super_admin'`

**Raison** : Contrôle d'accès super administrateur, bypass total du RBAC

**Fichier** : `AuthServiceProvider.php:106-108`

---

### 2. `access-createur`

**Logique** : `in_array($roleSlug, ['super_admin', 'admin', 'createur', 'creator'])`

**Raison** : Logique métier spécifique au module créateur, indépendante du RBAC staff

**Fichier** : `AuthServiceProvider.php:110-113`

---

### 3. `access-client`

**Logique** : `in_array($roleSlug, ['super_admin', 'admin', 'staff', 'createur', 'creator', 'client'])`

**Raison** : Logique métier spécifique au module client, indépendante du RBAC staff

**Fichier** : `AuthServiceProvider.php:115-118`

---

## 🔒 Règles de Gouvernance

### ❌ Interdictions Absolues

1. **Créer des permissions `access-*`**  
   Les permissions de navigation ne sont pas des permissions métier atomiques.

2. **Modifier les mappings indirects sans justification**  
   Toute modification doit être justifiée par un besoin métier documenté et validé via PR.

3. **Créer de nouvelles permissions sans analyse d'impact**  
   Chaque nouvelle permission doit être justifiée et ne doit pas dupliquer une permission existante.

### ✅ Principes Directeurs

1. **Les Gates de navigation mappent vers des permissions métier existantes**  
   Cela évite la prolifération de permissions non atomiques.

2. **Un mapping indirect doit avoir une justification métier claire**  
   Documentée dans ce fichier.

3. **Le RBAC est stable et verrouillé**  
   Toute évolution future constitue un nouveau chantier avec un scope défini.

---

## 📊 Matrice Complète de Mapping

| Gate | Permission | Type | Catégorie |
|------|------------|------|-----------|
| `view-products` | `view-products` | Direct | Products |
| `create-products` | `create-products` | Direct | Products |
| `edit-products` | `edit-products` | Direct | Products |
| `delete-products` | `delete-products` | Direct | Products |
| `view-orders` | `view-orders` | Direct | Orders |
| `view-all-orders` | `view-all-orders` | Direct | Orders |
| `edit-orders` | `edit-orders` | Direct | Orders |
| `delete-orders` | `delete-orders` | Direct | Orders |
| `view-users` | `view-users` | Direct | Users |
| `create-users` | `create-users` | Direct | Users |
| `edit-users` | `edit-users` | Direct | Users |
| `delete-users` | `delete-users` | Direct | Users |
| `view-categories` | `view-categories` | Direct | Categories |
| `create-categories` | `create-categories` | Direct | Categories |
| `edit-categories` | `edit-categories` | Direct | Categories |
| `delete-categories` | `delete-categories` | Direct | Categories |
| `view-analytics` | `view-sales-analytics` | Direct | Analytics |
| `view-sales-analytics` | `view-sales-analytics` | Direct | Analytics |
| `view-stock-analytics` | `view-stock-analytics` | Direct | Analytics |
| `view-stock` | `view-stock` | Direct | Stock |
| `edit-stock` | `edit-stock` | Direct | Stock |
| `manage-settings` | `manage-settings` | Direct | System |
| `access-system-config` | `access-system-config` | Direct | System |
| `process-payments` | `process-payments` | Direct | Payments |
| `view-dashboard` | `view-all-orders` | **Indirect** | Navigation |
| `access-admin` | `view-users` | **Indirect** | Navigation |
| `access-staff` | `view-all-orders` | **Indirect** | Navigation |
| `access-staff-tools` | `view-all-orders` | **Indirect** | Navigation |
| `access-erp` | `view-stock` | **Indirect** | Navigation |
| `manage-erp` | `edit-stock` | **Indirect** | Navigation |
| `access-crm` | `view-users` | **Indirect** | Navigation |
| `manage-crm` | `edit-users` | **Indirect** | Navigation |
| `payments.view` | `view-orders` | **Indirect** | Payments |
| `payments.config` | `manage-settings` | **Indirect** | Payments |
| `payments.reprocess` | `process-payments` | **Indirect** | Payments |
| `payments.refund` | `process-payments` | **Indirect** | Payments |

---

## 📝 Historique des Modifications

| Date | Version | Auteur | Modification |
|------|---------|--------|--------------|
| 2026-01-20 | 1.0 | Antigravity | Création initiale - Documentation des 33 Gates |

---

## ✅ Statut de Clôture

**Ce document est VERROUILLÉ.**

Toute modification future nécessite :
1. Une justification métier documentée
2. Une revue de code (PR)
3. Une validation par l'équipe technique
4. Une mise à jour de ce document avec historique

**Le système RBAC est considéré comme STABLE et CLOS.**
