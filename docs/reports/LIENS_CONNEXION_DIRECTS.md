# 🔗 LIENS DE CONNEXION DIRECTS - RACINE BACKEND

**Date :** 28 novembre 2025  
**Base URL :** `http://localhost:8000` (ou votre URL de développement)

---

## 🔐 LIENS D'AUTHENTIFICATION

### 1. Hub d'Authentification (Choix Public/ERP)
```
http://localhost:8000/auth
```
**Route :** `auth.hub`  
**Description :** Page de choix entre Espace Boutique et Espace Équipe

---

### 2. Connexion Publique (Clients & Créateurs)
```
http://localhost:8000/login
```
**Route :** `login`  
**Description :** Formulaire de connexion pour clients et créateurs  
**Variantes de style :**
- `http://localhost:8000/login?style=neutral` (par défaut)
- `http://localhost:8000/login?style=female`
- `http://localhost:8000/login?style=male`

---

### 3. Inscription Publique (Clients & Créateurs)
```
http://localhost:8000/register
```
**Route :** `register`  
**Description :** Formulaire d'inscription avec choix de profil (Client ou Créateur)

---

### 4. Connexion ERP (Admin & Staff)
```
http://localhost:8000/erp/login
```
**Route :** `erp.login`  
**Description :** Formulaire de connexion pour l'équipe ERP (admin, staff)

---

### 5. Connexion Admin (Administrateurs E-commerce)
```
http://localhost:8000/admin/login
```
**Route :** `admin.login`  
**Description :** Formulaire de connexion pour les administrateurs

---

## 📊 DASHBOARDS (Après Connexion)

### Dashboards Clients & Créateurs
```
http://localhost:8000/compte
```
**Route :** `account.dashboard`  
**Pour :** Clients

```
http://localhost:8000/atelier-creator
```
**Route :** `creator.dashboard`  
**Pour :** Créateurs

### Dashboards Admin & ERP
```
http://localhost:8000/admin/dashboard
```
**Route :** `admin.dashboard`  
**Pour :** Administrateurs

```
http://localhost:8000/erp
```
**Route :** `erp.dashboard`  
**Pour :** Staff ERP

---

## 🧪 TEST RECOMMANDÉ

### Scénario 1 : Inscription Client
1. Aller sur : `http://localhost:8000/register`
2. Remplir le formulaire
3. Choisir "Client" comme type de compte
4. Soumettre
5. **Vérifier la redirection :** Devrait aller vers `/compte`

### Scénario 2 : Inscription Créateur
1. Aller sur : `http://localhost:8000/register`
2. Remplir le formulaire
3. Choisir "Créateur" comme type de compte
4. Soumettre
5. **Vérifier la redirection :** Devrait aller vers `/atelier-creator`

### Scénario 3 : Connexion Client
1. Aller sur : `http://localhost:8000/login`
2. Se connecter avec un compte client
3. **Vérifier la redirection :** Devrait aller vers `/compte`

### Scénario 4 : Connexion Créateur
1. Aller sur : `http://localhost:8000/login`
2. Se connecter avec un compte créateur
3. **Vérifier la redirection :** Devrait aller vers `/atelier-creator`

---

## ⚠️ PROBLÈME SIGNALÉ

**Symptôme :** Le choix de profil renvoie sur l'accueil (`/`) au lieu du dashboard approprié.

**À tester :**
- Inscription avec choix "Client" → Devrait aller vers `/compte`
- Inscription avec choix "Créateur" → Devrait aller vers `/atelier-creator`
- Connexion client → Devrait aller vers `/compte`
- Connexion créateur → Devrait aller vers `/atelier-creator`

---

**Note :** Si vous utilisez une autre URL (production, staging), remplacez `localhost:8000` par votre URL.


