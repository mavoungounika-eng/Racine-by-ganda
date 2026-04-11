# RÔLES & PERMISSIONS — RACINE BY GANDA

## Vue d'ensemble

RACINE BY GANDA utilise un système de rôles strict avec 5 profils utilisateurs distincts. Chaque rôle a des permissions et des accès spécifiques.

---

## 1. Super Admin

**Description** : Accès total au système, y compris les paramètres critiques.

### Droits principaux
- ✅ Accès complet à tous les menus
- ✅ Modification des paramètres système (commission, paiements, maintenance)
- ✅ Accès au CMS (pages, événements, portfolio)
- ✅ Gestion des utilisateurs (création, modification, suppression)
- ✅ Accès au scanner QR
- ✅ Accès aux outils de performance et monitoring

### Menus accessibles
- **Tous les menus Admin**
- **Système** : Paramètres, CMS, Scanner QR, Performance
- **Outils Internes** : Business Intelligence, Reporting Décisionnel

### Interdictions
- ❌ Aucune restriction

---

## 2. Admin

**Description** : Gestion opérationnelle et pilotage business, sans accès aux paramètres système.

### Droits principaux
- ✅ Gestion des commandes, produits, utilisateurs
- ✅ Accès au dashboard admin
- ✅ Gestion des vendeurs partenaires (créateurs)
- ✅ Accès aux statistiques et exports
- ✅ Gestion des paiements (visualisation)

### Menus accessibles
- **Dashboard Admin**
- **Catalogue** : Produits, Catégories, Collections
- **Ventes** : Commandes, Clients
- **Vendeurs Partenaires** : Créateurs, KYC, Plans
- **Outils** : Statistiques, Exports, Notifications
- **Paiements** : Hub Paiements, Transactions

### Interdictions
- ❌ **Paramètres système** (commission, maintenance)
- ❌ **CMS** (pages, événements, portfolio)
- ❌ **Scanner QR**
- ❌ **Outils de performance**

---

## 3. Staff

**Description** : Accès opérationnel strict (logistique, préparation commandes).

### Droits principaux
- ✅ Visualisation des commandes
- ✅ Mise à jour du statut des commandes
- ✅ Accès au scanner QR (préparation)
- ✅ Accès POS (point de vente)

### Menus accessibles
- **Commandes** : Liste, détails, mise à jour statut
- **Scanner QR** : Validation commandes
- **POS** : Vente en magasin

### Interdictions
- ❌ **Création/modification de produits**
- ❌ **Gestion des utilisateurs**
- ❌ **Accès aux paramètres**
- ❌ **Accès au CMS**
- ❌ **Statistiques business**

---

## 4. Créateur (Vendeur Partenaire)

**Description** : Vendeur indépendant sur la marketplace, gestion de ses propres produits.

### Droits principaux
- ✅ Gestion de ses produits uniquement
- ✅ Visualisation de ses commandes
- ✅ Accès à ses statistiques de vente
- ✅ Gestion de son profil et paiements

### Menus accessibles
- **Mon Profil** : Informations, KYC, Abonnement
- **Mes Produits** : Création, modification, stock
- **Gestion Commandes** : Ses commandes uniquement
- **Finances** : Revenus, paiements, préférences
- **Performance** : Statistiques de vente

### Interdictions
- ❌ **Accès aux produits d'autres créateurs**
- ❌ **Accès aux commandes globales**
- ❌ **Accès aux menus admin**
- ❌ **Modification des paramètres système**

---

## 5. Client

**Description** : Utilisateur final, acheteur sur la boutique.

### Droits principaux
- ✅ Navigation sur la boutique
- ✅ Ajout au panier et commande
- ✅ Gestion de son profil
- ✅ Visualisation de ses commandes
- ✅ Wishlist (favoris)

### Menus accessibles
- **Boutique** : Produits, catégories, recherche
- **Mon Compte** : Profil, commandes, adresses, wishlist
- **Panier** : Gestion du panier, checkout

### Interdictions
- ❌ **Accès à tout menu admin**
- ❌ **Accès au back-office**
- ❌ **Visualisation des commandes d'autres clients**

---

## Matrice de Permissions

| Permission | Super Admin | Admin | Staff | Créateur | Client |
|------------|-------------|-------|-------|----------|--------|
| Paramètres Système | ✅ | ❌ | ❌ | ❌ | ❌ |
| CMS | ✅ | ❌ | ❌ | ❌ | ❌ |
| Scanner QR | ✅ | ❌ | ✅ | ❌ | ❌ |
| Gestion Produits (tous) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Gestion Produits (siens) | ✅ | ✅ | ❌ | ✅ | ❌ |
| Gestion Commandes (toutes) | ✅ | ✅ | ✅ | ❌ | ❌ |
| Gestion Commandes (siennes) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Dashboard Admin | ✅ | ✅ | ❌ | ❌ | ❌ |
| Statistiques Business | ✅ | ✅ | ❌ | ❌ | ❌ |
| Statistiques Personnelles | ✅ | ✅ | ❌ | ✅ | ❌ |
| POS | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## Gates Laravel

Les permissions sont implémentées via Laravel Gates dans `AuthServiceProvider.php` :

- `access-system-config` : Super Admin uniquement
- `access-staff-tools` : Super Admin, Admin, Staff
- `access-admin` : Super Admin, Admin
- `access-erp` : Super Admin, Admin, Staff
- `access-crm` : Super Admin, Admin
- `payments.view` : Super Admin, Admin
- `payments.config` : Super Admin uniquement

---

## Sécurité

### Règles strictes
1. **Masquer un menu ≠ sécuriser une route** : Toutes les routes sensibles doivent avoir une vérification `$this->authorize()` côté controller.
2. **Aucun rôle hybride** : Un utilisateur ne peut avoir qu'un seul rôle.
3. **Accès par URL directe bloqué** : Même si un menu est masqué, l'accès par URL doit retourner 403 Forbidden.

### Tests de sécurité
- Tester l'accès direct par URL pour chaque rôle
- Vérifier que les menus masqués ne sont pas accessibles
- Confirmer que les actions critiques (modification paramètres, suppression) sont protégées
