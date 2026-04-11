# Module Assistant - Amira IA v3.0

Assistant virtuel intelligent pour RACINE BY GANDA.

## 🚀 Fonctionnalités

- **Chat intelligent** : Réponses contextuelles basées sur l'intention
- **Mode Mock** : Fonctionne sans clé API (réponses préenregistrées)
- **Intégration IA** : Support OpenAI (GPT-4) et Anthropic (Claude)
- **Commandes spéciales** : `/aide`, `/stats`, `/stocks`, etc.
- **Widget premium** : Interface moderne et responsive
- **Données en temps réel** : Stats, commandes, contacts (équipe)

## ⚙️ Configuration .env

Ajoutez ces variables dans votre fichier `.env` :

```env
#------------------------------------------------------------
# AMIRA - Assistant IA
#------------------------------------------------------------

# Activation globale
AMIRA_ENABLED=true

# Provider IA : "mock" (défaut), "openai", ou "anthropic"
AMIRA_AI_PROVIDER=mock

#------------------------------------------------------------
# OpenAI (si AMIRA_AI_PROVIDER=openai)
#------------------------------------------------------------
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxx
AMIRA_OPENAI_MODEL=gpt-4o-mini

#------------------------------------------------------------
# Anthropic Claude (si AMIRA_AI_PROVIDER=anthropic)
#------------------------------------------------------------
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxxxxx
AMIRA_ANTHROPIC_MODEL=claude-3-haiku-20240307

#------------------------------------------------------------
# Paramètres IA
#------------------------------------------------------------
AMIRA_MAX_TOKENS=500
AMIRA_TEMPERATURE=0.7

#------------------------------------------------------------
# Limites
#------------------------------------------------------------
AMIRA_RATE_LIMIT=2
AMIRA_DAILY_GUEST=20
AMIRA_DAILY_CLIENT=50
AMIRA_DAILY_TEAM=200
AMIRA_MAX_CONTEXT=10

#------------------------------------------------------------
# Logs
#------------------------------------------------------------
AMIRA_LOGGING=true
```

## 📋 Commandes disponibles

### Publiques
| Commande | Description |
|----------|-------------|
| `/aide` | Affiche l'aide |
| `/clear` | Efface la conversation |

### Équipe uniquement
| Commande | Description |
|----------|-------------|
| `/stats` | Statistiques du jour (CA, commandes) |
| `/stocks` | Alertes stock faible/rupture |
| `/commandes` | Commandes en attente |
| `/contacts` | Derniers contacts CRM |

## 🎨 Widget Chat

Le widget est inclus automatiquement via `@include('assistant::chat')` dans le layout frontend.

### Personnalisation CSS

Variables CSS disponibles :
```css
#amira-widget {
    --amira-primary: #4B1DF2;
    --amira-gold: #D4AF37;
    --amira-black: #11001F;
    --amira-white: #FAFAFA;
}
```

## 📡 API

### Endpoint
```
POST /amira/message
```

### Request
```json
{
    "message": "Bonjour !"
}
```

### Response
```json
{
    "status": "success",
    "message": "Bonjour ! 👋 Je suis Amira...",
    "timestamp": "2025-11-26T12:00:00Z",
    "sender": "Amira",
    "user_role": "guest"
}
```

## 🔧 Architecture

```
modules/Assistant/
├── config/
│   └── amira.php          # Configuration
├── Http/
│   └── Controllers/
│       └── AmiraController.php
├── Resources/
│   └── views/
│       └── chat.blade.php # Widget UI
├── Services/
│   └── AmiraService.php   # Logique métier
├── routes/
│   └── web.php
└── README.md
```

## 🔄 Versions

| Version | Date | Changements |
|---------|------|-------------|
| 3.0.0 | 2025-11-26 | Widget premium, commandes /, intégration données |
| 2.0.0 | 2025-11-26 | Support OpenAI/Anthropic |
| 1.0.0 | 2025-11-26 | Version initiale |

