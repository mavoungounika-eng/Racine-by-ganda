const { app, BrowserWindow, ipcMain, net } = require('electron');
const { autoUpdater } = require('electron-updater');
const fs = require('fs');
const path = require('path');
const offlineDb = require('./offlineDb');

const isDev = !app.isPackaged;

require('./paymentWindows'); // Fenêtre paiement Monetbil (handlers IPC auto-enregistrés)

let splashWindow = null;
let mainWindow = null;

function generateMachineId() {
  if (globalThis.crypto?.randomUUID) {
    return globalThis.crypto.randomUUID();
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = Math.floor(Math.random() * 16);
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

function machineIdPath() {
  return path.join(app.getPath('userData'), 'machine_id.txt');
}

ipcMain.handle('machine-id:get', async () => {
  const filePath = machineIdPath();

  try {
    const existing = fs.readFileSync(filePath, 'utf8').trim();
    if (existing) return existing;
  } catch (error) {
    if (error.code !== 'ENOENT') throw error;
  }

  const machineId = generateMachineId();
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
  fs.writeFileSync(filePath, machineId, 'utf8');
  return machineId;
});

// ─────────────────────────────────────────────────────────────────────────────
// Network — OS-level online check via Electron's net module
// ─────────────────────────────────────────────────────────────────────────────

ipcMain.handle('network:is-online', () => {
  return net.isOnline();
});

function createSplash() {
  splashWindow = new BrowserWindow({
    width: 420,
    height: 320,
    frame: false,
    transparent: false,
    resizable: false,
    center: true,
    skipTaskbar: true,
    alwaysOnTop: true,
    backgroundColor: '#160D0C',
    webPreferences: { contextIsolation: true },
  });
  splashWindow.loadFile(path.join(__dirname, 'splash.html'));
}

function createWindow() {
  mainWindow = new BrowserWindow({
    icon: path.join(__dirname, '../build/icon.png'),
    width: 1280,
    height: 800,
    // Keep the POS layout inside its tested responsive range.
    minWidth: 1024,
    minHeight: 700,
    resizable: true,
    show: false,
    backgroundColor: '#160D0C',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: false,
    },
  });

  mainWindow.maximize();

  if (isDev) {
    const devUrl = process.env.VITE_DEV_SERVER_URL || 'http://localhost:8080';
    mainWindow.loadURL(devUrl);
    mainWindow.webContents.openDevTools({ mode: 'detach' });
  } else {
    mainWindow.loadFile(path.join(__dirname, '..', 'dist', 'index.html'));
  }

  mainWindow.webContents.once('did-finish-load', () => {
    setTimeout(() => {
      if (splashWindow && !splashWindow.isDestroyed()) {
        splashWindow.close();
        splashWindow = null;
      }
      mainWindow.show();
    }, 3200);
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// Offline SQLite Store — IPC handlers
// ─────────────────────────────────────────────────────────────────────────────

ipcMain.handle('offlineDb:save', (_event, payload) => {
  return offlineDb.savePendingOrder(payload);
});

ipcMain.handle('offlineDb:getPending', () => {
  return offlineDb.getPendingOrders();
});

ipcMain.handle('offlineDb:markSynced', (_event, offlineIds) => {
  return offlineDb.markSynced(offlineIds);
});

ipcMain.handle('offlineDb:stats', () => {
  return offlineDb.getStats();
});

ipcMain.handle('offlineDb:cleanup', (_event, olderThanDays) => {
  return offlineDb.cleanup(olderThanDays);
});

// ─────────────────────────────────────────────────────────────────────────────
// Printing — silent print via hidden BrowserWindow
// ─────────────────────────────────────────────────────────────────────────────

ipcMain.handle('printer:get-printers', async () => {
  if (!mainWindow) return [];
  return mainWindow.webContents.getPrintersAsync();
});

ipcMain.handle('printer:print-receipt', async (_event, { html, printerName, silent }) => {
  return new Promise((resolve) => {
    const printWin = new BrowserWindow({
      show: false,
      width: 302,
      height: 900,
      webPreferences: { contextIsolation: true },
    });

    printWin.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(html)}`);

    printWin.webContents.on('did-finish-load', () => {
      const options = {
        silent: silent !== false,
        printBackground: true,
        margins: { marginType: 'none' },
      };
      if (printerName) options.deviceName = printerName;

      printWin.webContents.print(options, (success, failureReason) => {
        printWin.close();
        resolve({ success, failureReason: failureReason || null });
      });
    });
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// Auto-Update (electron-updater)
// ─────────────────────────────────────────────────────────────────────────────

ipcMain.handle('update:download', () => autoUpdater.downloadUpdate());
ipcMain.handle('update:install', () => autoUpdater.quitAndInstall());

// ─────────────────────────────────────────────────────────────────────────────
// App Lifecycle
// ─────────────────────────────────────────────────────────────────────────────

app.whenReady().then(() => {
  // Initialise SQLite offline store
  offlineDb.init(app.getPath('userData'));

  // Run cleanup of old synced orders on startup (> 7 days)
  try {
    offlineDb.cleanup(7);
  } catch (err) {
    // Non-fatal — log and continue
    // eslint-disable-next-line no-console
    console.warn('[offlineDb] Cleanup on startup failed:', err.message);
  }

  createSplash();
  createWindow();
  require('./syncBootstrap').init(() => mainWindow); // sync offline : notifie le renderer (network:online) au boot + reconnexion

  // Auto-update check (production only)
  if (!isDev) {
    autoUpdater.autoDownload = false;
    autoUpdater.logger = null; // suppress default logging

    autoUpdater.on('update-available', (info) => {
      mainWindow?.webContents.send('update:available', {
        version: info.version,
        releaseDate: info.releaseDate,
      });
    });

    autoUpdater.on('download-progress', (progress) => {
      mainWindow?.webContents.send('update:progress', {
        percent: Math.round(progress.percent),
      });
    });

    autoUpdater.on('update-downloaded', () => {
      mainWindow?.webContents.send('update:downloaded');
    });

    // Check after a short delay so the window is fully loaded
    setTimeout(() => {
      autoUpdater.checkForUpdates().catch(() => {});
    }, 5000);
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});

app.on('before-quit', () => {
  // Close SQLite connection gracefully
  offlineDb.close();
});
