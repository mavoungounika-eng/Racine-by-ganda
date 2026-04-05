const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electron', {
  ping: () => 'pong',
});

contextBridge.exposeInMainWorld('electronAPI', {
  openSession: (data) => ipcRenderer.send('pos:open-session', data),
  onSessionOpened: (callback) => ipcRenderer.on('pos:session-opened', (event, data) => callback(data)),

  createSale: (data) => ipcRenderer.send('pos:create-sale', data),
  onSaleCreated: (callback) => ipcRenderer.on('pos:sale-created', (event, data) => callback(data)),

  closeSession: (data) => ipcRenderer.send('pos:close-session', data),
  onSessionClosed: (callback) => ipcRenderer.on('pos:session-closed', (event, data) => callback(data)),
});
