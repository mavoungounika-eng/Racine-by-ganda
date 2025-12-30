# ✅ CHECKLIST DE TESTS MANUELS — MODULE CRÉATEUR V2

**Projet :** RACINE BY GANDA — Espace Créateur  
**Module :** Gestion Produits / Commandes / Finances  
**Version :** v2.0  
**Date :** 29 novembre 2025

---

## 🎯 OBJECTIF

Vérifier que le créateur dispose bien d'un **mini back-office fonctionnel** :

* Gestion de ses produits
* Vue sur les commandes qui concernent ses produits
* Vision simple de ses finances (ventes, commissions, net créateur)
* Le tout **sécurisé** (il ne voit que SES données).

**Environnement :** `http://localhost:8000`

---

## 1️⃣ GESTION PRODUITS — CRÉATEUR

### Test 1.1 : Accès à la liste des produits

- [ ] Se connecter avec un compte **créateur actif**
- [ ] Aller sur `/createur/produits` (ou via le menu "Mes produits")
- [ ] **Résultat attendu :**
  * La page s'affiche sans erreur
  * Un tableau liste les produits du créateur (ou message du type "Vous n'avez pas encore de produits.")

---

### Test 1.2 : Bouton "Ajouter un produit"

- [ ] Depuis la page `/createur/produits`
- [ ] Cliquer sur **"Ajouter un produit"** ou **"Nouveau produit"**
- [ ] **Résultat attendu :**
  * Redirection vers `/createur/produits/nouveau`
  * Affichage d'un formulaire de création produit

---

### Test 1.3 : Création d'un produit valide

- [ ] Sur `/createur/produits/nouveau` :
  * Remplir :
    * Nom du produit
    * Description
    * Prix
    * Stock (ou cocher "sur commande" si prévu)
    * Statut (selon formulaire : brouillon ou soumettre à validation)
  * Cliquer sur **"Enregistrer"**
- [ ] **Résultat attendu :**
  * Redirection vers `/createur/produits`
  * Message de succès : "Produit créé avec succès"
  * Le produit apparaît dans la liste

**En base de données :**

- [ ] `products.user_id` = ID du créateur connecté
- [ ] `products.status` = `draft` ou `pending_review` selon la logique choisie

---

### Test 1.4 : Validation des erreurs du formulaire produit

- [ ] Sur `/createur/produits/nouveau` :
  * Laisser des champs obligatoires vides (ex : nom, prix)
  * Soumettre
- [ ] **Résultat attendu :**
  * Rester sur la même page
  * Messages d'erreur affichés sous les champs concernés
  * Aucune ligne créée en base

---

### Test 1.5 : Édition d'un produit

**Pré-requis :** Avoir au moins un produit appartenant au créateur.

- [ ] Sur `/createur/produits`
- [ ] Cliquer sur **"Modifier"** sur un produit
- [ ] Vérifier la redirection vers `/createur/produits/{id}/edit`
- [ ] Modifier par exemple :
  * Nom
  * Prix
- [ ] Enregistrer
- [ ] **Résultat attendu :**
  * Message de succès
  * Modifications visibles dans la liste
  * En base : champs mis à jour

---

### Test 1.6 : Changement de statut (publish / archive)

- [ ] Sur `/createur/produits`
  * Si un bouton ou action "Publier" existe, cliquer dessus
- [ ] **Résultat attendu :**
  * Le statut passe à `published` ou `pending_review` (selon logique)
- [ ] Tester aussi une action de type **"Archiver"** ou **"Supprimer"**
  * Le produit ne doit plus apparaître dans la liste principale
  * En base :
    * soit `status = 'archived'`
    * soit `deleted_at` rempli (soft delete)

---

### Test 1.7 : Sécurité — Accès produit d'un autre créateur

**Pré-requis :**

* Créateur A avec un produit
* Créateur B avec un autre compte

- [ ] Connecté en tant que **Créateur B**
- [ ] Tenter d'accéder directement à :
  `/createur/produits/{id_du_produit_de_A}/edit`
- [ ] **Résultat attendu :**
  * Erreur 403 ou redirection
  * Aucune info sur le produit de A n'est visible

---

## 2️⃣ GESTION COMMANDES — CRÉATEUR

### Test 2.1 : Liste des commandes liées au créateur

**Pré-requis :**

* Au moins une commande contenant un produit du créateur test.

- [ ] Se connecter comme créateur
- [ ] Aller sur `/createur/commandes`
- [ ] **Résultat attendu :**
  * Tableau affichant les commandes qui contiennent au moins un de ses produits
  * Colonnes : n° commande, date, statut, total, actions

---

### Test 2.2 : Filtrage des commandes

- [ ] Si un filtre par statut existe (ex : `new`, `in_production`, `ready_to_ship`, etc.)
  * Appliquer un filtre
- [ ] **Résultat attendu :**
  * La liste se met à jour
  * Seules les commandes avec le statut sélectionné apparaissent

---

### Test 2.3 : Détail d'une commande

- [ ] Depuis `/createur/commandes`
- [ ] Cliquer sur "Voir" / "Détails" sur une commande
- [ ] **Résultat attendu :**
  * Redirection vers `/createur/commandes/{order_id}`
  * Affichage :
    * Infos client (nom/prénom, email)
    * Adresse de livraison (si gérée)
    * Liste des articles **du créateur uniquement**
    * Statut de la commande

---

### Test 2.4 : Mise à jour du statut de la commande

- [ ] Sur la page de détail d'une commande
- [ ] Si un sélecteur ou des boutons de statut sont présents :
  * Passer de `new` → `in_production`
  * Puis `in_production` → `ready_to_ship`
- [ ] **Résultat attendu :**
  * Message de succès
  * Nouveau statut affiché
  * En base : `orders.status` mis à jour

> ⚠️ Vérifier que la logique ne permet pas de modifier `payment_status` depuis le créateur.

---

### Test 2.5 : Sécurité — Accès à la commande d'un autre créateur

- [ ] Connecté comme créateur B
- [ ] Tenter d'ouvrir `/createur/commandes/{order_id}` d'une commande qui ne contient **aucun** article lui appartenant
- [ ] **Résultat attendu :**
  * Erreur 403 ou redirection
  * Aucune donnée de la commande n'est visible

---

## 3️⃣ VUE FINANCES — CRÉATEUR

### Test 3.1 : Accès à la page finances

- [ ] Se connecter comme créateur
- [ ] Aller sur `/createur/finances`
- [ ] **Résultat attendu :**
  * La page s'affiche sans erreur
  * 3 cards / blocs :
    * Total brut
    * Commissions RACINE
    * Net créateur

---

### Test 3.2 : Cohérence des montants

**Pré-requis :**

* Une ou plusieurs commandes **livrées** avec des produits du créateur
* Commission définie (par ex. 20%)

- [ ] Sur `/createur/finances` :
  * Vérifier que :
    * **Total brut** = Somme des `OrderItem.total_price` pour les commandes livrées
    * **Commission** = Total brut × taux de commission
    * **Net créateur** = Total brut – commission
- [ ] Vérifier l'historique / tableau des dernières commandes payées :
  * Les montants par commande sont cohérents
  * Les commandes listées appartiennent bien au créateur (via leurs produits)

---

### Test 3.3 : Filtrage par période (si implémenté)

- [ ] Si la page propose un filtre par période (mois courant, dates, etc.)
- [ ] Changer de période
- [ ] **Résultat attendu :**
  * Les montants se mettent à jour correctement
  * Les commandes listées correspondent à la période choisie

---

## 4️⃣ SÉCURITÉ & CLOISONNEMENT (V2)

### Test 4.1 : Filtrage global par `user_id`

**Vérification conceptuelle dans le code (ou via tests pratiques) :**

- [ ] Les requêtes produits utilisent bien :
  `Product::where('user_id', auth()->id())`
- [ ] Les requêtes commandes utilisent :
  `Order::whereHas('items.product', fn($q) => $q->where('user_id', auth()->id()))`
- [ ] La page finances calcule les montants uniquement à partir des `OrderItem` liés aux produits du créateur connecté

---

### Test 4.2 : Route Model Binding sécurisé

- [ ] Tenter manuellement de changer l'ID d'un produit/commande dans l'URL
- [ ] **Résultat attendu :**
  * Si ce n'est pas un élément du créateur → 403 / redirection
  * En aucun cas tu ne dois voir les données d'un autre créateur

---

## 5️⃣ UX / UI MINI BACK-OFFICE

### Test 5.1 : Navigation cohérente

- [ ] Depuis le dashboard créateur :
  * Lien vers "Mes produits"
  * Lien vers "Commandes"
  * Lien vers "Finances"
- [ ] **Résultat attendu :**
  * Navigation fluide, sans erreurs 404
  * Les pages gardent le layout `layouts/creator.blade.php`

---

### Test 5.2 : Style & charte

- [ ] Vérifier que :
  * Les boutons, cartes, tableaux respectent l'univers RACINE (premium, propre)
  * Pas de styles "bruts" Bootstrap ou non-maîtrisés
  * Les labels sont clairs :
    * "Mes produits", "Commandes", "Finances"
    * Pas de labels techniques (ex. "index", "store", "update")

---

## 6️⃣ COMMANDES UTILES (RAPPEL)

En cas de bug ou changement de migrations :

```bash
php artisan migrate
php artisan migrate:status

php artisan route:list | grep createur

php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

tail -f storage/logs/laravel.log
```

---

## 📊 RÉSULTAT FINAL

### ✅ Si tous les tests passent :

→ Le **module Créateur v2** est validé comme **mini back-office opérationnel**.

### ⚠️ Si certains tests échouent :

→ Note le numéro du test + le comportement observé, et on peut corriger ensemble point par point.

---

## 📝 NOTES IMPORTANTES

### Temps estimé pour tous les tests : 45-60 minutes

### Prérequis pour les tests :

1. **Base de données avec données de test :**
   - Au moins 2 créateurs (A et B)
   - Créateur A avec au moins 2-3 produits
   - Créateur B avec au moins 1 produit
   - Au moins 2-3 commandes contenant des produits du créateur A
   - Au moins 1 commande livrée pour tester les finances

2. **Configuration :**
   - Taux de commission défini (ex. 20% dans config ou constante)
   - Statuts de commande définis (new, in_production, ready_to_ship, shipped, delivered)

3. **Comptes de test :**
   - Créateur A : `creator_a@test.com` / `password`
   - Créateur B : `creator_b@test.com` / `password`
   - Les deux avec `creator_profiles.status = 'active'`

---

## 🔄 PROCHAINES ÉTAPES

Après validation du V2 :

1. **Audit express du code** généré par Antigravity/Cursor
2. **Préparation du Prompt Master V3** :
   * Statistiques avancées
   * Graphiques interactifs
   * Notifications en temps réel
   * Filtres par période avancés
   * Export de données
   * Analyses de performance

---

**Date de création :** 29 novembre 2025  
**Généré par :** Cursor AI Assistant


