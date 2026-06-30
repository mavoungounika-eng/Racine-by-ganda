/**
 * Laravel Echo — Configuration Reverb pour POS Electron
 *
 * Utilise pusher-js comme transport WebSocket sous le hood.
 * Retry avec backoff si Reverb est temporairement indisponible.
 */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

function _getDeviceId() {
  try {
    return JSON.parse(localStorage.getItem('pos_device') || '{}').machine_id ?? 'unknown'
  } catch {
    return 'unknown'
  }
}

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
          Authorization: `Bearer ${localStorage.getItem('pos_operator_token') ?? ''}`,
          'X-Device-Id': _getDeviceId(),
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

export function refreshEchoAuth() {
  if (!echo) return
  const token = localStorage.getItem('pos_operator_token') ?? ''
  const device = _getDeviceId()
  const headers = { Authorization: `Bearer ${token}`, 'X-Device-Id': device }

  // Update both echo.options (snapshot) and the live pusher connector config
  if (echo.options?.auth?.headers) Object.assign(echo.options.auth.headers, headers)
  if (echo.connector?.pusher?.config?.auth?.headers) {
    Object.assign(echo.connector.pusher.config.auth.headers, headers)
  }

  if (!echo.connector?.pusher?.connection?.state?.includes('connected')) {
    echo.connector?.pusher?.connect()
  }
}
