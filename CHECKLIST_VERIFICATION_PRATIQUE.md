# ✅ CHECKLIST DE VÉRIFICATION PRATIQUE

**Date** : 2025-01-27  
**Version** : 1.0  
**Format** : Checklist exécutable

---

## 🎯 MODE D'EMPLOI

1. Ouvrir l'application : http://127.0.0.1:8000
2. Cocher chaque case au fur et à mesure
3. Noter les problèmes dans la section "Notes"
4. Prioriser les corrections

---

## 📋 SECTION 1 : PAGE D'ACCUEIL

### Affichage
- [ ] Page se charge sans erreur 500
- [ ] Hero section visible
- [ ] Section catégories affiche au moins 3 catégories
- [ ] Section produits affiche au moins 4 produits
- [ ] Images produits s'affichent (pas d'images cassées)
- [ ] Noms produits affichés (pas de "undefined")
- [ ] Prix affichés en FCFA (pas en €)
- [ ] Badge "Nouveau" sur produits récents

### Liens et Navigation
- [ ] Bouton "Explorer la boutique" → Redirige vers `/boutique`
- [ ] Bouton "Nos créateurs" → Redirige vers `/createurs`
- [ ] Clic sur catégorie → Redirige vers `/boutique?category=X`
- [ ] Clic sur produit → Redirige vers `/produit/{id}`
- [ ] Produits fallback (si aucun produit) → Liens vers `/boutique`

### Fonctionnalités
- [ ] Bouton wishlist (cœur) présent sur chaque produit
- [ ] Clic wishlist (connecté) → Icône change (vide/pleine)
- [ ] Clic wishlist (non connecté) → Redirige vers `/login`

**Notes** : _________________________________________________

---

## 📋 SECTION 2 : PAGE PRODUIT

### URL Test
**Produit test** : ID = 1 (ou premier produit actif)

### Affichage
- [ ] Page se charge sans erreur
- [ ] Image principale affichée
- [ ] Galerie miniatures fonctionnelle
- [ ] Clic miniature → Change image principale
- [ ] Zoom image fonctionnel
- [ ] Titre produit affiché
- [ ] Prix affiché en FCFA
- [ ] Stock affiché ("X disponible(s)")
- [ ] Description complète
- [ ] Caractéristiques affichées
- [ ] Breadcrumb : Accueil / Boutique / Produit

### Sélection Quantité
- [ ] Input quantité visible (valeur = 1)
- [ ] Bouton "-" diminue la quantité
- [ ] Bouton "+" augmente la quantité
- [ ] Quantité minimum = 1 (ne peut pas aller en dessous)
- [ ] Quantité maximum = stock (ne peut pas dépasser)
- [ ] Modification manuelle input → Synchronisé avec formulaire
- [ ] Stock = 0 → Input désactivé

### Ajout au Panier
- [ ] Bouton "Ajouter au panier" visible
- [ ] Clic bouton → Requête AJAX envoyée
- [ ] Pendant requête → Bouton affiche "Ajout..."
- [ ] Succès → Bouton affiche "Ajouté !" (vert)
- [ ] Succès → Compteur panier mis à jour
- [ ] Succès → Pas de rechargement page
- [ ] Après 2s → Bouton réinitialisé

### Cas d'Erreur
- [ ] Produit inactif → Message "Ce produit n'est plus disponible"
- [ ] Stock = 0 → Message "Stock épuisé"
- [ ] Quantité > stock → Message "Stock insuffisant" + quantité ajustée
- [ ] Produit inexistant → Erreur 404

**Notes** : _________________________________________________

---

## 📋 SECTION 3 : AJOUT AU PANIER (DÉTAILLÉ)

### Test 1 : Produit Actif avec Stock
1. [ ] Aller sur page produit (ID = 1, stock > 0)
2. [ ] Sélectionner quantité = 1
3. [ ] Cliquer "Ajouter au panier"
4. [ ] Vérifier message succès
5. [ ] Vérifier compteur panier = 1
6. [ ] Aller sur page panier
7. [ ] Vérifier produit présent
8. [ ] Vérifier quantité = 1
9. [ ] Vérifier total correct

### Test 2 : Produit Inactif
1. [ ] Aller sur page produit inactif
2. [ ] Tenter ajout au panier
3. [ ] Vérifier message erreur
4. [ ] Vérifier produit NON ajouté

### Test 3 : Stock Insuffisant
1. [ ] Aller sur produit (stock = 3)
2. [ ] Sélectionner quantité = 5
3. [ ] Cliquer "Ajouter au panier"
4. [ ] Vérifier message erreur
5. [ ] Vérifier quantité ajustée à 3
6. [ ] Réessayer → Succès

### Test 4 : Incrément Quantité
1. [ ] Produit déjà dans panier (quantité = 2)
2. [ ] Ajouter 3 autres
3. [ ] Vérifier quantité totale = 5 (si stock >= 5)
4. [ ] Ou vérifier limitation au stock

### Test 5 : AJAX Fonctionnel
1. [ ] Ouvrir DevTools → Network
2. [ ] Ajouter produit au panier
3. [ ] Vérifier requête POST `/cart/add`
4. [ ] Vérifier réponse JSON `{success: true, count: X}`
5. [ ] Vérifier pas de rechargement page

**Notes** : _________________________________________________

---

## 📋 SECTION 4 : PAGE PANIER

### Affichage
- [ ] URL : `/cart`
- [ ] Articles affichés
- [ ] Image produit pour chaque article
- [ ] Nom produit pour chaque article
- [ ] Prix unitaire affiché
- [ ] Quantité affichée
- [ ] Sous-total calculé (prix × quantité)
- [ ] Total général calculé
- [ ] Panier vide → Message "Votre panier est vide"

### Actions
- [ ] Bouton "-" diminue quantité
- [ ] Bouton "+" augmente quantité
- [ ] Input quantité modifiable
- [ ] Modification quantité → Total mis à jour
- [ ] Bouton "Supprimer" → Article retiré
- [ ] Bouton "Vider panier" → Panier vidé
- [ ] Bouton "Continuer shopping" → `/boutique`
- [ ] Bouton "Passer commande" → `/checkout`

### Validations
- [ ] Quantité = 0 → Article supprimé
- [ ] Quantité > stock → Message erreur
- [ ] Produit supprimé → Retiré automatiquement

**Notes** : _________________________________________________

---

## 📋 SECTION 5 : CHECKOUT

### Accès
- [ ] Panier non vide → Accès autorisé
- [ ] Panier vide → Redirection ou message
- [ ] Non connecté → Redirection `/login`
- [ ] Connecté → Formulaire affiché

### Formulaire
- [ ] Adresses existantes listées (si connecté)
- [ ] Formulaire nouvelle adresse visible
- [ ] Champs obligatoires marqués
- [ ] Validation côté client
- [ ] Sélection méthode paiement
- [ ] Résumé commande affiché

### Validation
- [ ] Nom requis
- [ ] Email requis et valide
- [ ] Téléphone optionnel
- [ ] Adresse requise
- [ ] Méthode paiement requise
- [ ] Total affiché correct

### Création Commande
- [ ] Soumission formulaire → Commande créée
- [ ] Redirection vers paiement
- [ ] Email confirmation (si configuré)

**Notes** : _________________________________________________

---

## 📋 SECTION 6 : NAVIGATION GLOBALE

### Header
- [ ] Logo → `/` (accueil)
- [ ] Menu "Boutique" → `/boutique`
- [ ] Menu "Créateurs" → `/createurs`
- [ ] Compteur panier affiché (si articles)
- [ ] Clic compteur → `/cart`
- [ ] Lien "Mon compte" → `/profil` (si connecté)
- [ ] Lien "Connexion" → `/login` (si non connecté)

### Footer
- [ ] Liens fonctionnels
- [ ] Réseaux sociaux (si présents)
- [ ] Newsletter (si présent)
- [ ] Informations légales

### Breadcrumbs
- [ ] Présents sur page produit
- [ ] Présents sur page panier
- [ ] Liens fonctionnels
- [ ] Position correcte

**Notes** : _________________________________________________

---

## 📋 SECTION 7 : RESPONSIVE

### Mobile (< 768px)
- [ ] Menu hamburger fonctionnel
- [ ] Produits en colonne unique
- [ ] Formulaire adapté
- [ ] Boutons accessibles
- [ ] Texte lisible
- [ ] Images adaptées

### Tablet (768px - 991px)
- [ ] Layout adapté
- [ ] Navigation fonctionnelle
- [ ] Produits en grille 2 colonnes

### Desktop (> 992px)
- [ ] Layout complet
- [ ] Navigation complète
- [ ] Produits en grille 4 colonnes

**Notes** : _________________________________________________

---

## 📋 SECTION 8 : CAS LIMITES

### Stock
- [ ] Stock = 0 → Bouton désactivé
- [ ] Stock = 1 → Quantité max = 1
- [ ] Stock changé → Mise à jour interface

### Produits
- [ ] Produit supprimé → 404
- [ ] Produit inactif → Message erreur
- [ ] Produit sans image → Image par défaut

### Panier
- [ ] Panier vide → Message approprié
- [ ] Article supprimé → Retiré du panier
- [ ] Stock insuffisant → Message + ajustement

**Notes** : _________________________________________________

---

## 📋 SECTION 9 : PERFORMANCE

### Temps de Chargement
- [ ] Page accueil < 2 secondes
- [ ] Page produit < 1.5 secondes
- [ ] Page boutique < 2 secondes
- [ ] Page panier < 1 seconde

### Requêtes
- [ ] Pas de requêtes multiples inutiles
- [ ] Images optimisées
- [ ] CSS/JS chargés correctement

**Notes** : _________________________________________________

---

## 📋 SECTION 10 : SÉCURITÉ

### Validation
- [ ] CSRF tokens présents
- [ ] Validation côté serveur
- [ ] Pas d'injection SQL
- [ ] Pas de XSS

### Autorisations
- [ ] Routes protégées
- [ ] Rôles respectés
- [ ] Accès non autorisé → 403

**Notes** : _________________________________________________

---

## ✅ RÉSUMÉ FINAL

### Tests Réussis
**Total** : ___ / 100

### Problèmes Détectés
**Critiques** : ___
**Importants** : ___
**Mineurs** : ___

### Actions Requises
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

### Statut Global
- [ ] ✅ Tous les tests passent
- [ ] ⚠️ Quelques problèmes mineurs
- [ ] ❌ Problèmes critiques détectés

---

## 📝 NOTES GÉNÉRALES

_________________________________________________
_________________________________________________
_________________________________________________

---

**Checklist complétée le** : ______________  
**Par** : ______________  
**Version testée** : ______________

