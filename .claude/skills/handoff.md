| name | handoff |
| description | Compact the current conversation into a handoff document for another agent to pick up. Use when context window is nearly full, session is ending, or user wants to continue in a fresh session. |
| argument-hint | What will the next session be used for? |

# Handoff

Write a handoff document summarising the current conversation so a fresh agent can continue the work.
Save it to: handoff-$(date +%Y%m%d-%H%M).md

## Structure du document de handoff

### Etat du projet
- Branche git actuelle + dernier commit
- Stats PHPUnit actuelles (tests / failures / skipped)
- Ce qui a ete accompli dans cette session

### Contexte actif
- Le probleme en cours de resolution
- Les fichiers touches dans cette session
- Les hypotheses testees et resultats

### Prochaine action immediate
- La commande exacte a lancer en premier
- Le fichier a ouvrir en premier
- La decision en attente

### Skills recommandes pour la prochaine session
- Lister les skills .claude/skills/ pertinents

### Regles a rappeler
- Les regles CLAUDE.md les plus importantes pour la prochaine tache

Do not duplicate content already in CLAUDE.md or .claude/rules/.
Reference them by path instead.
