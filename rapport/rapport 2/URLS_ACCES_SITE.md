# 🌐 URLS D'ACCÈS AU SITE RACINE BACKEND

## 📍 URL PRINCIPALE DU SITE

### Frontend (Site Public)
```
http://localhost:8000/
```
ou
```
http://localhost:8000/home
```

---

## 🔐 PANEL ADMIN

### URL de Connexion Admin
```
http://localhost:8000/admin/login
```

### Dashboard Admin (après connexion)
```
http://localhost:8000/admin/dashboard
```

**Compte développeur :**
- Email : `dev@racine.com`
- Password : `dev123`

---

## ⚠️ AUTRES POINTS DE LOGIN (Explications)

Il existe **3 systèmes de login différents** dans le projet :

### 1. Login Public (`/login`)
- **URL :** `http://localhost:8000/login`
- **Pour :** Clients et Créateurs
- **Controller :** `PublicAuthController`
- **Usage :** Boutique en ligne, espace client

### 2. Login ERP (`/erp/login`)
- **URL :** `http://localhost:8000/erp/login`
- **Pour :** Personnel ERP (Staff)
- **Controller :** `ErpAuthController`
- **Usage :** Module ERP interne

### 3. Login Admin (`/admin/login`) ⭐ **PRINCIPAL**
- **URL :** `http://localhost:8000/admin/login`
- **Pour :** Administrateurs et Super Admins
- **Controller :** `AdminAuthController`
- **Usage :** Panel d'administration principal

### 4. Hub d'Authentification (`/auth`)
- **URL :** `http://localhost:8000/auth`
- **Pour :** Page de choix du type de connexion
- **Usage :** Point d'entrée centralisé

---

## ✅ RÉSUMÉ SIMPLE

**Pour accéder au site public :**
```
http://localhost:8000
```

**Pour accéder au panel admin :**
```
http://localhost:8000/admin/login
```

**Identifiants admin :**
- Email : `dev@racine.com`
- Password : `dev123`

---

## 🚀 DÉMARRAGE DU SERVEUR

Si le serveur n'est pas lancé :

```bash
php artisan serve
```

Le site sera accessible sur `http://localhost:8000`

