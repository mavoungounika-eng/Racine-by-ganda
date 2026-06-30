# 🔧 Fix Docker DNS - Windows

## ❌ Problème Identifié

```
Error: lookup docker-images-prod...r2.cloudflarestorage.com: no such host
```

**Cause:** Docker Desktop ne peut pas résoudre les noms de domaine (problème DNS).

---

## ✅ Solution Rapide

### Option 1: Configurer DNS Docker Desktop (Recommandé)

1. **Ouvrir Docker Desktop**
2. **Settings** (⚙️) → **Resources** → **Network**
3. **DNS Server:** Changer pour `8.8.8.8` (Google DNS)
4. **Apply & Restart**

### Option 2: Fichier daemon.json

1. **Créer/Éditer:** `C:\Users\PC\.docker\daemon.json`

```json
{
  "dns": ["8.8.8.8", "8.8.4.4"],
  "registry-mirrors": []
}
```

2. **Redémarrer Docker Desktop**

### Option 3: PowerShell (Temporaire)

```powershell
# Redémarrer WSL avec DNS correct
wsl --shutdown

# Configurer DNS dans WSL
wsl -- sudo bash -c "echo 'nameserver 8.8.8.8' > /etc/resolv.conf"
wsl -- sudo bash -c "echo 'nameserver 8.8.4.4' >> /etc/resolv.conf"

# Tester
wsl -- ping -c 3 google.com
wsl -- nslookup docker.io
```

---

## 🧪 Tests Après Fix

```powershell
# 1. Test résolution DNS
nslookup docker.io

# 2. Test Docker
docker run hello-world

# 3. Test pull image
docker pull nginx:alpine

# 4. Vérifier images
docker images
```

---

## 🔍 Diagnostic Complet

```powershell
# Vérifier DNS Windows
ipconfig /all | findstr /C:"DNS Servers"

# Vérifier DNS WSL
wsl -- cat /etc/resolv.conf

# Vérifier connectivité
ping 8.8.8.8
ping google.com

# Vérifier Docker daemon
docker info
```

---

## 📋 Checklist

- [ ] Docker Desktop DNS configuré (8.8.8.8)
- [ ] daemon.json créé avec DNS
- [ ] Docker Desktop redémarré
- [ ] Test `docker run hello-world` réussi
- [ ] Test `docker pull nginx:alpine` réussi

---

**Après le fix, relancer:**
```powershell
docker run hello-world
```
