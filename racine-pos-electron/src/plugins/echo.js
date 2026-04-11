/**
 * Laravel Echo — Configuration Reverb pour POS Electron
 *
 * Utilise pusher-js comme transport WebSocket sous le hood.
 * Retry avec backoff si Reverb est temporairement indisponible.
 */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

/**
 * Crée une instance Echo connectée à Reverb.
 * Retourne null si les variables d'env sont manquantes (dev sans Reverb).
 */
function createEcho() {
  const key = import.meta.env.VITE_REVERB_APP_KEY
  const host = import.meta.env.VITE_REVERB_HOST ?? 'localhost'
  const port = parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080')
  const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http'
  const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

  if (!key) {
    console.warn('[Echo] VITE_REVERB_APP_KEY non défini — WebSocket désactivé')
    return null
  }

  try {
    return new Echo({
      broadcaster: 'reverb',
      key,
      wsHost: host,
      wsPort: port,
      wssPort: port,
      forceTLS: scheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${apiUrl}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('pos_token') ?? ''}`,
          'X-Device-Id': localStorage.getItem('device_id') ?? 'unknown',
          Accept: 'application/json',
        },
      },
    })
  } catch (err) {
    console.warn('[Echo] Impossible de créer Echo:', err)
    return null
  }
}

export const echo = createEcho()

/**
 * Met à jour le token d'auth (après login POS).
 * Nécessite de recréer l'instance Echo car les headers sont fixés à l'init.
 */
export function refreshEchoAuth() {
  if (!echo) return
  const token = localStorage.getItem('pos_token') ?? ''
  const device = localStorage.getItem('device_id') ?? 'unknown'
  echo.options.auth.headers.Authorization = `Bearer ${token}`
  echo.options.auth.headers['X-Device-Id'] = device
  // Reconnecter si déconnecté
  if (!echo.connector?.pusher?.connection?.state?.includes('connected')) {
    echo.connector?.pusher?.connect()
  }
}
