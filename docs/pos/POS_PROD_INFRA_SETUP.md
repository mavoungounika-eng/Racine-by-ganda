# POS Production Infrastructure Setup

Date: 18 mars 2026  
Scope: scheduler + queue workers pour RACINE POS

## Objectif
Garantir l'execution continue des jobs planifies POS en production.

## 1) Cron Laravel Scheduler

Ajouter la ligne suivante dans le crontab de l'utilisateur qui execute l'application:

```cron
* * * * * cd /home/nika/projects/racine-backend && php artisan schedule:run >> /dev/null 2>&1
```

Commandes:

```bash
crontab -e
crontab -l
```

## 2) Installer Supervisor (si absent)

```bash
sudo apt-get update
sudo apt-get install -y supervisor
sudo systemctl enable --now supervisor
sudo systemctl status supervisor
```

## 3) Supervisor Queue Workers

Template fourni:
- [racine-pos-worker.conf.example](/home/nika/projects/racine-backend/deploy/supervisor/racine-pos-worker.conf.example)

Configuration directe (chemin actuel du projet):

```bash
sudo tee /etc/supervisor/conf.d/racine-pos-worker.conf >/dev/null <<'EOF'
[program:racine-pos-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/home/nika/projects/racine-backend
command=/usr/bin/php /home/nika/projects/racine-backend/artisan queue:work --queue=default,pos --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
startsecs=5
startretries=10
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/home/nika/projects/racine-backend/storage/logs/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stopwaitsecs=3600
killasgroup=true
stopasgroup=true
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start racine-pos-worker:*
sudo supervisorctl status
```

## 4) Permissions minimales

```bash
sudo chown -R www-data:www-data /home/nika/projects/racine-backend/storage /home/nika/projects/racine-backend/bootstrap/cache
sudo chmod -R ug+rwX /home/nika/projects/racine-backend/storage /home/nika/projects/racine-backend/bootstrap/cache
```

## 4.1) Precheck acces `www-data` au projet

```bash
sudo -u www-data test -r /home/nika/projects/racine-backend/artisan && echo "OK access artisan" || echo "KO access artisan"
sudo -u www-data test -w /home/nika/projects/racine-backend/storage/logs && echo "OK write logs" || echo "KO write logs"
```

Si acces KO sur `/home/nika/...`, deplacer le projet vers `/var/www/racine-backend` et ajuster:
- cron (`cd /var/www/racine-backend`)
- Supervisor (`directory` + `command` + `stdout_logfile`)
- permissions (`/var/www/racine-backend/storage` et `bootstrap/cache`)

## 5) Validation post-deploiement

Script automatise disponible:
- [validate_pos_runtime.sh](/home/nika/projects/racine-backend/scripts/ops/validate_pos_runtime.sh)

Execution:

```bash
./scripts/ops/validate_pos_runtime.sh /home/nika/projects/racine-backend
```

## 6) Verification manuelle

```bash
crontab -l
sudo supervisorctl status
php artisan schedule:list
php artisan schedule:run
php artisan queue:restart
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
```

## 7) Points critiques

1. `schedule:run` seul ne suffit pas pour les jobs `ShouldQueue`.
2. `queue:work` doit tourner en continu via Supervisor/systemd.
3. Verifier les permissions ecriture sur `storage/logs` et `bootstrap/cache`.
4. En staging, tester les workflows POS avant prod (login, vente, cleanup timeout).
