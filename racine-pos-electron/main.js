const { app, BrowserWindow, Menu, ipcMain } = require('electron');
const path = require('path');

let mainWindow;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 800,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      enableRemoteModule: false
    },
    icon: path.join(__dirname, 'assets/icon.png')
  });

  const startUrl = 'http://localhost:8000/pos-terminal';
  mainWindow.loadURL(startUrl);

  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  // Handle session open request
  ipcMain.on('pos:open-session', (event, data) => {
    console.log('📍 Session ouverte:', data);
    event.reply('pos:session-opened', { status: 'success', session: data });
  });

  // Handle sale creation
  ipcMain.on('pos:create-sale', (event, data) => {
    console.log('💰 Vente créée:', data);
    event.reply('pos:sale-created', { status: 'success', sale: data });
  });

  // Handle session close request
  ipcMain.on('pos:close-session', (event, data) => {
    console.log('✅ Session clôturée:', data);
    event.reply('pos:session-closed', { status: 'success', zreport: data });
  });
}

app.on('ready', () => {
  createWindow();
  createMenu();
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (mainWindow === null) {
    createWindow();
  }
});

function createMenu() {
  const template = [
    {
      label: 'File',
      submenu: [
        { label: 'Exit', accelerator: 'CmdOrCtrl+Q', click: () => app.quit() }
      ]
    },
    {
      label: 'View',
      submenu: [
        { label: 'Reload', accelerator: 'CmdOrCtrl+R', click: () => mainWindow.reload() },
        { label: 'DevTools', accelerator: 'CmdOrCtrl+Shift+I', click: () => mainWindow.webContents.toggleDevTools() }
      ]
    }
  ];

  Menu.setApplicationMenu(Menu.buildFromTemplate(template));
}

module.exports = { mainWindow };
