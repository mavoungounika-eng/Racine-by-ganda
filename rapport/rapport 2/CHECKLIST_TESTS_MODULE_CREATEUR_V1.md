# ✅ CHECKLIST DE TESTS MANUELS — MODULE CRÉATEUR V1

**Date :** 29 novembre 2025  
**Projet :** RACINE BY GANDA  
**Module :** Créateur/Vendeur v1.0  
**Statut :** Tests à effectuer avant validation finale

---

## 🎯 OBJECTIF

Vérifier que le module Créateur/Vendeur v1 fonctionne correctement dans tous les scénarios.

**Environnement de test :** `http://localhost:8000` (ou votre URL locale)

---

## 📋 CHECKLIST DE TESTS

### 1️⃣ CRÉATION DE COMPTE CRÉATEUR

#### Test 1.1 : Accès au formulaire d'inscription créateur

- [ ] Aller sur `/register` (page d'inscription client)
- [ ] Scroller en bas de la page
- [ ] Vérifier la présence du bouton **"Devenir créateur partenaire"**
- [ ] Cliquer sur le bouton
- [ ] **Résultat attendu :** Redirection vers `/createur/register`

#### Test 1.2 : Remplissage du formulaire d'inscription créateur

- [ ] Remplir tous les champs obligatoires :
  - Nom complet
  - Email (unique)
  - Mot de passe (min 8 caractères)
  - Confirmation mot de passe
  - Nom de la marque/atelier
- [ ] Optionnellement remplir les champs facultatifs :
  - Téléphone
  - Bio
  - Localisation
  - Type de créations
  - Réseaux sociaux
  - Informations légales
- [ ] Cocher la case "J'accepte les CGU"
- [ ] Cliquer sur **"Envoyer ma Demande"**

#### Test 1.3 : Vérification en base de données

**Ouvrir la base de données et vérifier :**

- [ ] Table `users` :
  - Un nouvel utilisateur a été créé
  - `users.email` = l'email saisi
  - `users.role` = `'createur'` (ou `'creator'` selon votre convention)
  - `users.name` = le nom saisi

- [ ] Table `creator_profiles` :
  - Un nouveau profil a été créé
  - `creator_profiles.user_id` = l'ID du user créé
  - `creator_profiles.brand_name` = le nom de marque saisi
  - `creator_profiles.status` = `'pending'` ✅ **IMPORTANT**
  - Les autres champs remplis sont bien enregistrés

#### Test 1.4 : Message de confirmation

- [ ] Après soumission, vérifier la redirection vers `/createur/login`
- [ ] Vérifier le message de succès :
  - "Votre demande de compte créateur a bien été envoyée. Votre compte est en cours de validation par l'équipe RACINE. Vous recevrez un email une fois votre compte validé."

---

### 2️⃣ CONNEXION CRÉATEUR (STATUT PENDING)

#### Test 2.1 : Tentative de connexion avec compte pending

- [ ] Aller sur `/createur/login`
- [ ] Saisir l'email et le mot de passe du créateur créé (statut `pending`)
- [ ] Cliquer sur **"Se Connecter"**

#### Test 2.2 : Redirection vers page "En attente"

- [ ] **Résultat attendu :** Redirection vers `/createur/pending`
- [ ] Vérifier le message :
  - "Votre compte créateur est en attente de validation par l'équipe RACINE."
- [ ] Vérifier que l'utilisateur est **déconnecté** (pas de session active)

---

### 3️⃣ VALIDATION MANUELLE DU COMPTE

#### Test 3.1 : Activation du compte en base de données

**Dans la base de données :**

- [ ] Trouver le `creator_profiles` du créateur test
- [ ] Modifier `creator_profiles.status` de `'pending'` à `'active'`
- [ ] Sauvegarder

#### Test 3.2 : Reconnexion avec compte actif

- [ ] Aller sur `/createur/login`
- [ ] Saisir l'email et le mot de passe
- [ ] Cliquer sur **"Se Connecter"**

#### Test 3.3 : Accès au dashboard

- [ ] **Résultat attendu :** Redirection vers `/createur/dashboard`
- [ ] Vérifier que le dashboard s'affiche correctement
- [ ] Vérifier les éléments du dashboard :
  - Hero section avec avatar et nom de marque
  - Badge de statut "Compte Actif"
  - 4 cartes statistiques (produits, ventes, revenus, commandes)
  - Section "Commandes Récentes"
  - Section "Produits Récents"
  - Actions rapides

---

### 4️⃣ SÉCURITÉ & CLOISONNEMENT

#### Test 4.1 : Client ne peut pas accéder au dashboard créateur

- [ ] Se connecter avec un compte **client** (rôle `client`)
- [ ] Tenter d'accéder directement à `/createur/dashboard`
- [ ] **Résultat attendu :** 
  - Soit redirection vers `/login`
  - Soit erreur 403 "Accès réservé aux créateurs"

#### Test 4.2 : Créateur ne peut pas accéder aux routes admin

- [ ] Se connecter avec un compte **créateur** (rôle `createur`)
- [ ] Tenter d'accéder à `/admin/dashboard`
- [ ] **Résultat attendu :** 
  - Soit redirection vers `/login`
  - Soit erreur 403

#### Test 4.3 : Créateur ne peut pas accéder aux routes ERP

- [ ] Toujours connecté en tant que créateur
- [ ] Tenter d'accéder à une route ERP (si elle existe)
- [ ] **Résultat attendu :** Accès refusé

#### Test 4.4 : Vérification du filtrage des données

**Prérequis :** Avoir au moins 2 créateurs avec des produits différents en base

- [ ] Se connecter avec le créateur A
- [ ] Aller sur `/createur/dashboard`
- [ ] Vérifier que les statistiques affichées correspondent **uniquement** aux données du créateur A
- [ ] Vérifier que les produits récents affichés appartiennent **uniquement** au créateur A
- [ ] Vérifier que les commandes récentes affichées concernent **uniquement** les produits du créateur A

---

### 5️⃣ DISTINCTION AUTH CLIENT / CRÉATEUR

#### Test 5.1 : Page login client → Bouton espace créateur

- [ ] Aller sur `/login` (page de connexion client)
- [ ] Scroller en bas de la page
- [ ] Vérifier la présence de la section :
  - "Vous êtes créateur, styliste ou artisan partenaire ?"
  - Bouton **"Accéder à l'espace créateur"**
- [ ] Cliquer sur le bouton
- [ ] **Résultat attendu :** Redirection vers `/createur/login`

#### Test 5.2 : Page register client → Bouton devenir créateur

- [ ] Aller sur `/register` (page d'inscription client)
- [ ] Scroller en bas de la page
- [ ] Vérifier la présence de la section :
  - "Vous souhaitez vendre vos créations avec RACINE BY GANDA ?"
  - Bouton **"Devenir créateur partenaire"**
- [ ] Cliquer sur le bouton
- [ ] **Résultat attendu :** Redirection vers `/createur/register`

#### Test 5.3 : Page login créateur → Lien espace client

- [ ] Aller sur `/createur/login`
- [ ] Scroller en bas de la page
- [ ] Vérifier la présence de la section :
  - "Vous êtes client ?"
  - Bouton **"Accéder à l'espace client"**
- [ ] Cliquer sur le bouton
- [ ] **Résultat attendu :** Redirection vers `/login?context=boutique` (ou `/login`)

#### Test 5.4 : Page register créateur → Lien compte client

- [ ] Aller sur `/createur/register`
- [ ] Scroller en bas de la page
- [ ] Vérifier la présence de la section :
  - "Vous souhaitez simplement acheter ?"
  - Bouton **"Créer un compte client"**
- [ ] Cliquer sur le bouton
- [ ] **Résultat attendu :** Redirection vers `/register?context=boutique` (ou `/register`)

---

### 6️⃣ GESTION DES STATUTS

#### Test 6.1 : Compte suspendu

**Dans la base de données :**

- [ ] Modifier `creator_profiles.status` d'un créateur à `'suspended'`
- [ ] Tenter de se connecter avec ce créateur
- [ ] **Résultat attendu :** 
  - Redirection vers `/createur/suspended`
  - Message : "Votre compte créateur a été suspendu. Veuillez contacter le support."
  - L'utilisateur est déconnecté

#### Test 6.2 : Compte sans profil créateur

**Scénario :** Un utilisateur avec `role = 'createur'` mais sans `creator_profile`

- [ ] Créer un user avec `role = 'createur'` mais sans `creator_profile`
- [ ] Tenter de se connecter
- [ ] **Résultat attendu :** 
  - Redirection vers `/createur/register`
  - Message : "Veuillez compléter votre profil créateur."

---

### 7️⃣ NAVIGATION & UX

#### Test 7.1 : Navigation dans le dashboard créateur

- [ ] Se connecter en tant que créateur actif
- [ ] Vérifier la sidebar :
  - Logo/avatar avec nom de marque
  - Section "Tableau de bord" (pas "Atelier")
  - Section "Créations" avec :
    - Mes produits
    - Nouveau produit
    - Galerie
  - Section "Ventes" avec :
    - Commandes
    - Statistiques
    - Revenus
  - Section "Compte" avec :
    - Mon profil
    - Paramètres

#### Test 7.2 : Libellés corrects

- [ ] Vérifier que **nulle part** dans l'espace créateur on ne voit :
  - ❌ "Mon Atelier"
  - ❌ "Atelier Demo RACINE"
  - ❌ "Atelier" (dans un contexte marque)
- [ ] Vérifier que partout on voit :
  - ✅ "Espace Créateur"
  - ✅ "Ma Boutique"
  - ✅ "Tableau de bord créateur"

#### Test 7.3 : Responsive

- [ ] Tester sur mobile (largeur < 768px)
- [ ] Vérifier que la sidebar se réduit correctement
- [ ] Vérifier que les formulaires sont utilisables
- [ ] Vérifier que les boutons sont accessibles

---

### 8️⃣ DÉCONNEXION

#### Test 8.1 : Déconnexion depuis le dashboard

- [ ] Se connecter en tant que créateur
- [ ] Aller sur `/createur/dashboard`
- [ ] Cliquer sur "Se déconnecter" (dans la sidebar ou header)
- [ ] **Résultat attendu :** 
  - Redirection vers `/createur/login`
  - Message : "Vous avez été déconnecté."
  - Session supprimée

---

## 📊 RÉSULTATS ATTENDUS

### ✅ TOUS LES TESTS PASSENT

Si tous les tests passent, le module v1 est **solide et prêt pour la production**.

### ⚠️ TESTS EN ÉCHEC

Si certains tests échouent :

1. **Noter le numéro du test** qui échoue
2. **Noter le comportement observé** vs comportement attendu
3. **Vérifier les logs Laravel** (`storage/logs/laravel.log`)
4. **Vérifier la console navigateur** (F12) pour les erreurs JS
5. **Vérifier les middlewares** dans `bootstrap/app.php`
6. **Vérifier les routes** dans `routes/web.php`

---

## 🔧 COMMANDES UTILES POUR LE DEBUG

```bash
# Voir les routes créateur
php artisan route:list | grep creator

# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Nettoyer les caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Vérifier les middlewares
php artisan route:list --path=createur
```

---

## 📝 NOTES

- **Temps estimé pour tous les tests :** 30-45 minutes
- **Prérequis :** Base de données avec au moins 2 créateurs de test (pending, active)
- **Recommandation :** Faire les tests dans un environnement de développement, pas en production

---

**Date de création :** 29 novembre 2025  
**Généré par :** Cursor AI Assistant

---

## 📚 SUITE : CHECKLIST V2

Pour tester le module **Gestion Produits / Commandes / Finances** (v2.0), voir le fichier :

**`CHECKLIST_TESTS_MODULE_CREATEUR_V2.md`**

