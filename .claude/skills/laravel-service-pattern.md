---
description: Créer ou modifier un Service Laravel selon les conventions du projet
triggers:
  - créer un service
  - nouveau service
  - logique métier
  - service class
---
# Skill — Pattern Service Laravel (Racine)

## Conventions
- Pas de logique métier dans les Controllers
- Toujours valider via Form Requests
- Utiliser JsonResource pour les réponses JSON
- PSR-12 strict

## Structure
```php
<?php
namespace App\Services\[Domain];

class [Nom]Service
{
    public function __construct(
        private readonly [Dependency] $dependency,
    ) {}

    public function [action]([Type] $param): [ReturnType]
    {
        // logique ici
    }
}
```

## Chemins
- Services : `app/Services/{Domain}/{Nom}Service.php`
- Modules  : `modules/{Module}/Services/{Nom}Service.php`
- Tests    : `tests/Unit/{Domain}/{Nom}ServiceTest.php`

## Obligatoire après création
1. `php -l app/Services/...php`
2. Run ciblé PHPUnit
3. Run global PHPUnit
