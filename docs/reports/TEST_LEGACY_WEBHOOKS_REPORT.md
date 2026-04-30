# Test Legacy Webhooks Report Command

## Validation du fichier de logs utilisé

Le mode `--debug=1` affiche maintenant :
- **File actually used** : Le chemin absolu du fichier réellement lu
- **Total log entries (buffers) processed** : Nombre total d'entrées log (buffers) traitées
- **Entries matched (used/blocked)** : Nombre d'entrées qui matchent "Legacy webhook used/blocked"

## Commandes de test Windows (PowerShell)

### 1. Générer un hit legacy webhook sur /webhooks/stripe

```powershell
# Forcer un hit legacy (si LEGACY_WEBHOOKS_ENABLED=true)
curl.exe -X POST http://127.0.0.1:8000/webhooks/stripe -H "Content-Type: application/json" -d "{}"
```

### 2. Exécuter le report sur le fichier daily (laravel-YYYY-MM-DD.log)

```powershell
# Obtenir la date du jour au format YYYY-MM-DD
$today = Get-Date -Format "yyyy-MM-dd"

# Exécuter le report avec debug sur le fichier daily
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --file="storage/logs/laravel-$today.log"
```

**Ou en une seule ligne :**
```powershell
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --file="storage/logs/laravel-$(Get-Date -Format 'yyyy-MM-dd').log"
```

### 3. Exécuter le report sur le fichier single (laravel.log)

```powershell
# Exécuter le report avec debug sur le fichier single
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --file="storage/logs/laravel.log"
```

### 4. Test avec auto-fallback (recommandé en prod)

```powershell
# Si laravel.log n'existe pas, fallback automatique vers laravel-YYYY-MM-DD.log
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --auto-file=1
```

## Validation attendue

Avec `--debug=1`, vous devriez voir :

```
=== Debug Statistics ===
  File actually used: C:\laravel_projects\racine-backend\storage\logs\laravel-2024-01-15.log
  Total log entries (buffers) processed: 1234
  Entries matched (used/blocked): 5
```

**Points à vérifier :**
- ✅ Le fichier affiché correspond bien au fichier spécifié (ou au fallback)
- ✅ Le nombre de buffers traités est cohérent avec la taille du fichier
- ✅ Les entrées matchées correspondent aux hits legacy webhook
- ✅ Le contexte JSON extrait contient provider, path, route_name, ip

## Scénarios de test

### Test en local (fichier single)
```powershell
# 1. Générer un hit
curl.exe -X POST http://127.0.0.1:8000/webhooks/stripe -H "Content-Type: application/json" -d "{}"

# 2. Vérifier le report
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --file="storage/logs/laravel.log"
```

### Test en prod (fichier daily)
```powershell
# 1. Générer un hit
curl.exe -X POST https://votre-domaine.com/webhooks/stripe -H "Content-Type: application/json" -d "{}"

# 2. Vérifier le report avec auto-fallback
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --auto-file=1
```

### Test explicite daily
```powershell
# 1. Générer un hit
curl.exe -X POST http://127.0.0.1:8000/webhooks/stripe -H "Content-Type: application/json" -d "{}"

# 2. Vérifier le report sur le fichier daily d'aujourd'hui
php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --file="storage/logs/laravel-$(Get-Date -Format 'yyyy-MM-dd').log"
```

