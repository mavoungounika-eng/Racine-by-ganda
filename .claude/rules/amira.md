---
paths:
  - "app/Services/Amira*"
  - "app/Http/Controllers/Amira*"
  - "app/Http/Controllers/AI*"
  - "tests/Feature/Amira*"
  - "tests/Unit/Amira*"
---

# Règles — Service Amira (IA)

## Package utilisé

- openai-php/laravel v0.18

## Présentation

Amira est le service d'intelligence artificielle intégré à Racine by Ganda.
Il utilise l'API OpenAI via le package openai-php/laravel.

## Variable d'environnement requise

OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-... (optionnel)

## Conventions Amira

- Toute la logique IA est encapsulée dans App\Services\AmiraService
- Ne JAMAIS appeler OpenAI::client() directement dans un contrôleur
- Toujours définir un max_tokens pour éviter les coûts inattendus
- Gérer les exceptions : \OpenAI\Exceptions\ErrorException et timeouts
- Les prompts système sont dans resources/prompts/ (fichiers Markdown ou PHP)
- Logger les appels IA dans Sentry pour le monitoring des erreurs

## Tests Amira

- Mocker le client OpenAI avec Mockery — ne JAMAIS appeler l'API réelle en test
- Pattern de mock :
  $this->mock(\OpenAI\Client::class, function ($mock) {
      $mock->shouldReceive('chat->create')->andReturn(/* fixture */);
  });
- Tester les cas : réponse valide, timeout, erreur API, contenu refusé (moderation)

## Problèmes connus

- Les tests Amira échouent probablement sur le mock du client OpenAI
- Vérifier que OPENAI_API_KEY est bien défini dans .env.testing
