# 🚀 GUIDE RAPIDE - Démarrage Docker

## ⚡ Démarrage en 2 Minutes

### Étape 1: Fixer DNS + Préparer .env (1 script)

```powershell
.\fix-dns-and-env.ps1
```

**Ce script fait:**
- ✅ Configure DNS Docker (8.8.8.8)
- ✅ Génère APP_KEY
- ✅ Génère mots de passe forts
- ✅ Crée .env.production.local
- ✅ Test Docker

### Étape 2: Démarrer Services

```powershell
.\setup-docker.ps1
```

**Ou version rapide:**
```powershell
.\quick-start.ps1
```

---

## 📋 Scripts Disponibles

| Script | Description | Temps |
|--------|-------------|-------|
| `fix-dns-and-env.ps1` | Fix DNS + Prépare .env | 2 min |
| `setup-docker.ps1` | Setup complet avec checks | 3 min |
| `quick-start.ps1` | Démarrage rapide | 1 min |

---

## ✅ Checklist

- [ ] Exécuter `fix-dns-and-env.ps1`
- [ ] Redémarrer Docker Desktop
- [ ] Exécuter `setup-docker.ps1`
- [ ] Vérifier services: `docker-compose -f docker-compose.production.yml ps`
- [ ] Tester: `curl http://localhost/health`

---

## 🎯 Commande Unique

```powershell
# Tout en un
.\fix-dns-and-env.ps1
```

Puis suivre les instructions à l'écran.

---

**Sécurité:** l'`APP_KEY` est générée localement par script et n'est jamais stockée dans ce document.

---

**Prêt !** 🚀
