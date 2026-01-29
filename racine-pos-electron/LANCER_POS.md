# 🚀 RACINE POS - GUIDE LANCEMENT RAPIDE

## ✅ Statut : PRÊT À LANCER

Les fichiers ont été créés et configurés :
- ✅ `main.js` - Application Electron
- ✅ `preload.js` - Bridge sécurisé
- ✅ `index.html` - Interface graphique
- ✅ `package.json` - Configuration minimale
- ✅ `run.bat` - Script de lancement
- ✅ `start-pos.bat` - Script de démarrage

---

## 🎯 LANCER LE POS ELECTRON

### **Méthode 1 : Double-clic sur le fichier (✅ Recommandée)**
```
Fichier: C:\laravel_projects\racine-backend\racine-pos-electron\run.bat
Action: Double-clic dans l'Explorateur Windows
```

### **Méthode 2 : Ligne de commande**
```powershell
C:\laravel_projects\racine-backend\racine-pos-electron\run.bat
```

### **Méthode 3 : Depuis PowerShell**
```powershell
cd C:\laravel_projects\racine-backend\racine-pos-electron
npm start
```

---

## 📋 PRÉREQUIS (Doit être actif)

✅ **Backend Laravel**
```powershell
cd C:\laravel_projects\racine-backend
php artisan serve
# Accessible à: http://localhost:8000
```

✅ **MySQL (XAMPP)**
- Démarrer Apache et MySQL depuis le panneau XAMPP
- Base de données: `by_ganda`

✅ **Redis (optionnel)**
- Nécessaire si async jobs activés
- `redis-server` ou XAMPP Redis

---

## 🖥️ APRÈS LANCEMENT

Une fenêtre Electron s'affichera avec:
- ✅ **Interface POS complète** (http://localhost:8000/admin/pos)
- 📊 Scan produits par code-barres
- 💰 Gestion de panier et paiements
- 🔧 Ctrl+Shift+I → Dev Tools
- ❌ Ctrl+Q ou Fermer → Quitter

---

## ⚠️ SI ERREUR

**Erreur: "Unable to find Electron app"**
- Solution: Double-clic sur `run.bat` (pas depuis VS Code)

**Erreur: "Cannot find module 'electron'"**
- Solution: 
  ```powershell
  cd C:\laravel_projects\racine-backend\racine-pos-electron
  npm install
  ```

**Erreur: "Backend not responding"**
- Vérifier: `php artisan serve` est actif sur port 8000
- Vérifier: MySQL est actif (XAMPP)

---

## 📞 SUPPORT

**Lead Dev**: NIKA DIGITAL HUB
- Email: nikadigitalhub1@gmail.com
- Phone: +242 06 832 52 86
- Disponible: 24/7

---

**Version**: 1.0.0
**Date**: 29 janvier 2026
**Status**: 🟢 PRODUCTION READY
