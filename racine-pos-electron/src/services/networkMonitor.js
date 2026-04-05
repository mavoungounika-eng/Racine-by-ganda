import { ref } from 'vue'

class NetworkMonitor {
  constructor(apiClient) {
    this.isOnline = ref(true)
    this.lastChecked = ref(null)
    this.apiClient = apiClient
    this.listeners = { online: [], offline: [], reconnected: [] }
    this._pingInterval = null
    this._wasOffline = false
    this._handleBrowserOnlineBound = this._handleBrowserOnline.bind(this)
    this._handleBrowserOfflineBound = this._setOffline.bind(this, 'browser')
  }

  start() {
    window.addEventListener('online', this._handleBrowserOnlineBound)
    window.addEventListener('offline', this._handleBrowserOfflineBound)
    this._pingInterval = setInterval(() => this._ping(), 30000)
    this._ping() // initial check
  }

  stop() {
    clearInterval(this._pingInterval)
    window.removeEventListener('online', this._handleBrowserOnlineBound)
    window.removeEventListener('offline', this._handleBrowserOfflineBound)
  }

  on(event, callback) {
    this.listeners[event]?.push(callback)
  }

  async _ping() {
    try {
      await this.apiClient.get('/api/pos/offline/status')
      this._setOnline()
    } catch {
      this._setOffline('ping')
    }
  }

  _setOnline() {
    this.lastChecked.value = new Date()
    if (!this.isOnline.value) {
      this.isOnline.value = true
      if (this._wasOffline) {
        this.listeners.reconnected.forEach(cb => cb())
      }
      this.listeners.online.forEach(cb => cb())
    }
    this._wasOffline = false
    this.isOnline.value = true
  }

  _setOffline(reason) {
    this.lastChecked.value = new Date()
    if (this.isOnline.value) {
      this._wasOffline = true
      this.isOnline.value = false
      this.listeners.offline.forEach(cb => cb(reason))
    }
  }

  _handleBrowserOnline() {
    setTimeout(() => this._ping(), 3000) // debounce
  }
}

export default NetworkMonitor
