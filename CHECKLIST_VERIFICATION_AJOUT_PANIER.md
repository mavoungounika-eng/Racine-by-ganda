# ✅ CHECKLIST SPÉCIFIQUE - AJOUT AU PANIER

**Date** : 2025-01-27  
**Version** : 1.0  
**Focus** : Processus d'ajout au panier uniquement

---

## 🎯 OBJECTIF

Vérifier spécifiquement le processus d'ajout d'article au panier de A à Z.

---

## 📋 CHECKLIST DÉTAILLÉE

### 1. PRÉPARATION (2 min)

#### Données de Test
- [ ] Produit actif avec stock > 0 (ID: ___, Stock: ___)
- [ ] Produit inactif (ID: ___, Stock: ___)
- [ ] Produit stock = 0 (ID: ___, Stock: 0)
- [ ] Produit stock faible (ID: ___, Stock: 1)

#### Environnement
- [ ] Application démarrée
- [ ] Base de données connectée
- [ ] Cache vidé (si nécessaire)
- [ ] Navigateur ouvert (Chrome/Firefox)

---

### 2. PAGE PRODUIT - AFFICHAGE (1 min)

- [ ] Page produit se charge
- [ ] Informations produit affichées
- [ ] Stock affiché correctement
- [ ] Input quantité visible (valeur = 1)
- [ ] Boutons +/- visibles
- [ ] Bouton "Ajouter au panier" visible

---

### 3. SÉLECTION QUANTITÉ (2 min)

#### Test Quantité Minimum
- [ ] Quantité = 1 → Valide
- [ ] Bouton "-" → Quantité reste à 1 (ne peut pas aller en dessous)
- [ ] Input manuel = 0 → Corrigé à 1

#### Test Quantité Maximum
- [ ] Stock = 10, Quantité = 10 → Valide
- [ ] Stock = 10, Quantité = 11 → Limité à 10
- [ ] Bouton "+" au maximum → Ne dépasse pas le stock
- [ ] Input manuel > stock → Limité au stock

#### Test Synchronisation
- [ ] Modification input visible → Input hidden mis à jour
- [ ] Modification input hidden → Input visible mis à jour
- [ ] Les deux inputs toujours synchronisés

---

### 4. AJOUT AU PANIER - CAS NORMAL (3 min)

#### Scénario : Produit Actif, Stock Suffisant
1. [ ] Aller sur produit actif (stock > 0)
2. [ ] Sélectionner quantité = 1
3. [ ] Ouvrir DevTools → Network
4. [ ] Cliquer "Ajouter au panier"
5. [ ] Vérifier requête POST `/cart/add`
6. [ ] Vérifier requête contient :
    - [ ] `product_id`
    - [ ] `quantity`
    - [ ] `_token` (CSRF)
7. [ ] Vérifier réponse JSON :
    - [ ] `success: true`
    - [ ] `message: "Produit ajouté au panier."`
    - [ ] `count: X` (nombre articles)
8. [ ] Vérifier interface :
    - [ ] Bouton affiche "Ajouté !" (vert)
    - [ ] Compteur panier mis à jour
    - [ ] Pas de rechargement page
9. [ ] Attendre 2 secondes
10. [ ] Vérifier bouton réinitialisé

---

### 5. AJOUT AU PANIER - CAS ERREUR (5 min)

#### Test 1 : Produit Inactif
1. [ ] Aller sur produit inactif
2. [ ] Tenter ajout au panier
3. [ ] Vérifier message erreur : "Ce produit n'est plus disponible"
4. [ ] Vérifier produit NON ajouté
5. [ ] Vérifier compteur panier inchangé

#### Test 2 : Stock Épuisé
1. [ ] Aller sur produit (stock = 0)
2. [ ] Tenter ajout au panier
3. [ ] Vérifier message erreur : "Stock épuisé"
4. [ ] Vérifier produit NON ajouté

#### Test 3 : Stock Insuffisant
1. [ ] Aller sur produit (stock = 3)
2. [ ] Sélectionner quantité = 5
3. [ ] Tenter ajout au panier
4. [ ] Vérifier message erreur : "Stock insuffisant. Il ne reste que 3 exemplaire(s)"
5. [ ] Vérifier quantité ajustée à 3
6. [ ] Réessayer avec quantité = 3
7. [ ] Vérifier succès

#### Test 4 : Produit Inexistant
1. [ ] Tenter ajout produit ID = 99999
2. [ ] Vérifier erreur 404 ou message approprié

---

### 6. VÉRIFICATIONS CÔTÉ SERVEUR (3 min)

#### Validation
- [ ] Product ID validé (required, exists)
- [ ] Quantity validée (required, integer, min:1)
- [ ] Produit actif vérifié
- [ ] Stock vérifié
- [ ] Quantité limitée au stock

#### Service Panier
- [ ] Utilisateur connecté → DatabaseCartService utilisé
- [ ] Utilisateur non connecté → SessionCartService utilisé
- [ ] Produit existant dans panier → Quantité incrémentée
- [ ] Produit nouveau → Item créé
- [ ] Vérification stock lors incrément
- [ ] Limitation automatique si nécessaire

---

### 7. MISE À JOUR COMPTEUR PANIER (2 min)

#### Badge Header
- [ ] Badge présent dans header
- [ ] ID = `cart-count-badge`
- [ ] Compteur mis à jour après ajout
- [ ] Animation visible (scale)
- [ ] Badge masqué si count = 0
- [ ] Badge affiché si count > 0

#### Test Multiples Ajouts
1. [ ] Ajouter produit 1 → Compteur = 1
2. [ ] Ajouter produit 2 → Compteur = 2
3. [ ] Ajouter produit 1 (déjà présent) → Compteur = 3
4. [ ] Vérifier compteur correct à chaque étape

---

### 8. PAGE PANIER - VÉRIFICATION (3 min)

#### Après Ajout
1. [ ] Aller sur `/cart`
2. [ ] Vérifier produit présent
3. [ ] Vérifier quantité correcte
4. [ ] Vérifier prix unitaire correct
5. [ ] Vérifier sous-total correct (prix × quantité)
6. [ ] Vérifier total général correct

#### Actions Panier
- [ ] Modification quantité fonctionne
- [ ] Suppression fonctionne
- [ ] Total mis à jour automatiquement

---

### 9. CAS LIMITES (5 min)

#### Test Incrément Quantité
1. [ ] Produit dans panier (quantité = 2, stock = 10)
2. [ ] Ajouter 5 autres
3. [ ] Vérifier quantité totale = 7 (2 + 5)
4. [ ] Vérifier pas d'erreur

#### Test Limitation Stock
1. [ ] Produit dans panier (quantité = 8, stock = 10)
2. [ ] Ajouter 5 autres
3. [ ] Vérifier quantité limitée à 10
4. [ ] Vérifier message ou limitation silencieuse

#### Test Session vs Database
1. [ ] Ajouter produit (non connecté) → Session
2. [ ] Se connecter
3. [ ] Vérifier panier migré (si fonctionnalité présente)
4. [ ] Ou vérifier panier session conservé

---

### 10. PERFORMANCE (2 min)

#### Temps de Réponse
- [ ] Requête AJAX < 500ms
- [ ] Mise à jour interface < 100ms
- [ ] Pas de lag visible

#### Requêtes
- [ ] Une seule requête `/cart/add`
- [ ] Une requête `/api/cart/count` (si nécessaire)
- [ ] Pas de requêtes multiples inutiles

---

## ✅ RÉSUMÉ

### Tests Réussis
**Total** : ___ / 50

### Problèmes Détectés
**Critiques** : ___
**Importants** : ___
**Mineurs** : ___

### Statut
- [ ] ✅ Tous les tests passent
- [ ] ⚠️ Quelques problèmes mineurs
- [ ] ❌ Problèmes critiques détectés

---

## 📝 NOTES

**Problèmes identifiés** :
_________________________________________________
_________________________________________________

**Actions correctives** :
_________________________________________________
_________________________________________________

---

**Checklist complétée le** : ______________  
**Par** : ______________  
**Temps total** : ___ minutes

