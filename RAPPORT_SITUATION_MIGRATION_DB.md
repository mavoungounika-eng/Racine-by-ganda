# Rapport de Situation — Migration DB Racine (MariaDB XAMPP -> MySQL WSL)

Date: 2026-03-13

## 1) Contexte et objectif
Objectif: migrer la base **racine** depuis MariaDB (XAMPP Windows) vers MySQL 8 (WSL).

Contraintes:
- Pas de suppression de base.
- Sauvegarde complète avant migration.
- Validation à chaque étape.
- Commandes imprimées avant exécution.

## 2) Parcours complet (chronologie)

### 2.1 Diagnostic initial
- Le projet attend `DB_CONNECTION=mysql`, `DB_DATABASE=racine`, `DB_HOST=127.0.0.1`.
- La base **racine** existe bien côté XAMPP (Windows).
- Le port 3306 **n’était pas accessible** depuis WSL via `127.0.0.1` (WSL2).

### 2.2 Sauvegarde (dump) côté Windows
- Dump créé via `mysqldump.exe` sous Windows.
- Vérification du fichier: taille, en-tête, présence de `CREATE TABLE`.

### 2.3 Analyse du dump
- Le fichier est encodé en **UTF-16LE** lors d’une première création par redirection PowerShell.
- Un dump recréé **sans redirection** donne toujours une taille faible.
- Comptages:
  - `CREATE TABLE`: 143
  - `INSERT INTO`: 9

### 2.4 Statut MySQL WSL
- MySQL 8 installé côté WSL.
- Service actif.
- Accès `sudo` requis pour opérations serveur.

### 2.5 Décision en attente
- Import WSL non effectué tant que la qualité du dump n’est pas validée.

## 3) État actuel des environnements

### Windows (XAMPP MariaDB)
- Serveur MariaDB: **10.4.32**
- Base: **racine**
- Tables détectées: **143**
- Taille estimée DB (data + index): **11.36 MB**
- Dump actuel: `C:\xampp\mysql\backup\racine_backup.sql`

### WSL (Ubuntu)
- MySQL 8 installé et service actif (confirmé précédemment).
- Dump accessible via WSL: `/mnt/c/xampp/mysql/backup/racine_backup.sql`
- Application Laravel en **maintenance mode** (volontairement).

## 4) État du dump `racine_backup.sql`

### Taille
- 542,416 bytes puis 265,958 bytes (dernier dump au 2026-03-13 08:33)
- Taille finale: **~260 KB**

### Contenu
- `CREATE TABLE`: **143**
- `INSERT INTO`: **9**
- Encodage détecté (ancien dump): **UTF-16LE** (lié à redirection PowerShell `>`).

### Interprétation
Le dump **contient le schéma complet** (143 tables),
mais **très peu de données** (9 inserts).
Cela suggère une base **quasi vide** ou un export **incomplet** si des données
étaient attendues.

## 5) Ce qui a été validé
- Dump recréé via `mysqldump.exe` (sans redirection) — taille toujours très faible.
- Comptages `CREATE TABLE` et `INSERT INTO` confirmés.
- Taille réelle de la DB MariaDB confirmée: **11.36 MB**.

## 6) Risques identifiés
- Importer ce dump dans WSL donnera un schéma complet,
  mais **peu ou pas de données**.
- Si l’objectif est de migrer **toutes** les données,
  ce dump est **probablement insuffisant**.

## 7) Prochaine décision nécessaire
Deux options:
1. **Importer tel quel** (schema + données minimales).
2. **Valider les données réelles** dans MariaDB (counts sur tables clés)
   et, si besoin, **refaire un dump complet** avec options adaptées
   (`--single-transaction`, `--quick`, `--hex-blob`).

## 8) État de la migration
- **Phase dump**: terminée, mais qualité des données incertaine.
- **Import WSL**: **pas encore exécuté**.
- **Validation Laravel**: **en attente**.

## 9) Détails des commandes exécutées (résumé)

### Dump Windows (PowerShell)
- `C:\xampp\mysql\bin\mysqldump.exe -u root --routines --triggers --events racine --result-file=C:\xampp\mysql\backup\racine_backup.sql`
- `dir C:\xampp\mysql\backup\racine_backup.sql`

### Vérifications dump
- `findstr /C:"INSERT INTO" C:\xampp\mysql\backup\racine_backup.sql | measure-object`
- `findstr /C:"CREATE TABLE" C:\xampp\mysql\backup\racine_backup.sql | measure-object`

### Vérification taille DB source
- `C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT ROUND(SUM(data_length + index_length)/1024/1024,2) AS size_MB FROM information_schema.tables WHERE table_schema='racine';"`

## 10) Points de compréhension clés

- Un dump **très petit** avec **143 tables** et **9 inserts** = schéma complet, données quasi absentes.
- La taille totale (11.36 MB) peut venir surtout des **indexes** et **structures**, même avec peu de lignes.
- Le fait d’avoir 9 inserts confirme **faible volume de données** côté DB.

## 11) Prochaines étapes recommandées

Option A — Importer tel quel:
1. Import du dump dans MySQL WSL.
2. Vérifier le nombre de tables importées.
3. Valider Laravel (migrate:status).

Option B — Valider contenu réel avant import:
1. Faire des `COUNT(*)` sur tables clés (`users`, `orders`, `payments`, `products`).
2. Si beaucoup de lignes, refaire un dump avec:
   - `--single-transaction`
   - `--quick`
   - `--hex-blob`

