#!/usr/bin/env python3
"""
Fusionne les clés réelles depuis .env vers .env.production.local
"""

import re
from pathlib import Path

# Mapping des clés à fusionner depuis .env
KEYS_TO_MERGE = {
    # App
    'APP_KEY': 'base64:lI/5CSOYJybR2aWsQMwRsyafk/JtT4rl5mi+H+RItJE=',

    # Database
    'DB_DATABASE': 'racine',
    'DB_USERNAME': 'laravel',
    'DB_PASSWORD': 'secret',

    # Mail
    'MAIL_MAILER': 'smtp',
    'MAIL_HOST': 'smtp.gmail.com',
    'MAIL_PORT': '587',
    'MAIL_USERNAME': 'TON_EMAIL@gmail.com',
    'MAIL_PASSWORD': 'TON_CODE_16_CARACTERES',

    # Stripe (clés LIVE déjà présentes)
    'STRIPE_PUBLIC_KEY': 'YOUR_STRIPE_PUBLIC_KEY',
    'STRIPE_SECRET_KEY': 'YOUR_STRIPE_SECRET_KEY',
    'STRIPE_WEBHOOK_SECRET': 'YOUR_STRIPE_WEBHOOK_SECRET',

    # OpenAI (placeholder - à remplacer manuellement)
    'OPENAI_API_KEY': 'REPLACE_WITH_OPENAI_KEY',

    # Monetbil
    'MONETBIL_SERVICE_KEY': 'YOUR_MONETBIL_SERVICE_KEY',
    'MONETBIL_SERVICE_SECRET': 'YOUR_MONETBIL_SERVICE_SECRET',

    # Google OAuth (à adapter pour production - callback URL différent)
    'GOOGLE_CLIENT_ID': 'YOUR_GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET': 'YOUR_GOOGLE_CLIENT_SECRET',

    # reCAPTCHA
    'RECAPTCHA_SITE_KEY': 'YOUR_RECAPTCHA_SITE_KEY',
    'RECAPTCHA_SECRET_KEY': 'YOUR_RECAPTCHA_SECRET_KEY',

    # Exchange Rate API
    'EXCHANGE_RATE_API_KEY': '77fedfd4a944b4e8825b0930',

    # Sentry
    'SENTRY_LARAVEL_DSN': 'https://921a7e6c6e7692325ed9462faf213f07@o4511275580588032.ingest.de.sentry.io/4511275583733840',

    # Reverb
    'REVERB_APP_ID': '617764',
    'REVERB_APP_KEY': 'otb4susbgvaoitke2h7a',
    'REVERB_APP_SECRET': 'uozhvlbwhlrdymqsoeye',
    'VITE_REVERB_APP_KEY': 'otb4susbgvaoitke2h7a',

    # Filesystem
    'FILESYSTEM_DISK': 'local',

    # Session domain (à ajuster manuellement)
    'SESSION_DOMAIN': '',

    # AI Alerts
    'AI_ALERT_CRITICAL_EMAILS': 'admin@racinebyganda.com',
    'AI_ALERT_WARNING_EMAILS': 'support@racinebyganda.com',
}

def merge_env():
    """Fusionne les clés depuis .env vers .env.production.local"""
    env_prod_path = Path('.env.production.local')

    if not env_prod_path.exists():
        print("❌ .env.production.local n'existe pas")
        return

    lines = env_prod_path.read_text().splitlines()
    output = []

    for line in lines:
        # Ignorer les commentaires et lignes vides
        if not line.strip() or line.strip().startswith('#'):
            output.append(line)
            continue

        # Parser la ligne KEY=VALUE
        match = re.match(r'^([A-Z_][A-Z0-9_]*)=(.*)$', line)
        if not match:
            output.append(line)
            continue

        key, old_value = match.groups()

        # Si la clé existe dans notre mapping, remplacer
        if key in KEYS_TO_MERGE:
            new_value = KEYS_TO_MERGE[key]
            # Préserver les commentaires TODO si présents
            comment_match = re.search(r'(\s*#.*)$', old_value)
            comment = comment_match.group(1) if comment_match else ''

            # Si la nouvelle valeur est vide, garder le TODO
            if not new_value:
                output.append(f"{key}={comment.strip()}")
            else:
                output.append(f"{key}={new_value}")
        else:
            output.append(line)

    # Écrire le fichier fusionné
    env_prod_path.write_text('\n'.join(output) + '\n')
    print("✅ .env.production.local fusionné avec succès")
    print(f"📝 {len(KEYS_TO_MERGE)} clés fusionnées")

    # Avertissements
    print("\n⚠️  ATTENTION - À vérifier manuellement :")
    print("   - MAIL_USERNAME et MAIL_PASSWORD sont des placeholders")
    print("   - OPENAI_API_KEY est un placeholder")
    print("   - DB_PASSWORD='secret' est le mot de passe de dev")
    print("   - APP_URL et SESSION_DOMAIN doivent être configurés")
    print("   - MONETBIL_NOTIFY_URL et MONETBIL_RETURN_URL à ajouter")

if __name__ == '__main__':
    merge_env()
