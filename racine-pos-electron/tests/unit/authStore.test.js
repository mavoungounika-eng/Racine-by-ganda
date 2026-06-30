import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
  api: null,
  createApiService: vi.fn(),
}));

vi.mock('../../src/services/apiService.js', () => ({
  createApiService: mocks.createApiService,
}));

vi.mock('../../src/plugins/echo.js', () => ({
  refreshEchoAuth: vi.fn(),
}));

const { useAuthStore } = await import('../../src/stores/auth.js');

function installLocalStorage() {
  const values = new Map();

  globalThis.localStorage = {
    getItem: vi.fn((key) => values.get(key) ?? null),
    setItem: vi.fn((key, value) => values.set(key, String(value))),
    removeItem: vi.fn((key) => values.delete(key)),
    clear: vi.fn(() => values.clear()),
  };
}

describe('auth store — device token verification', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    installLocalStorage();
    mocks.api = {
      verifyDeviceToken: vi.fn(),
    };
    mocks.createApiService.mockReset();
    mocks.createApiService.mockImplementation(() => mocks.api);
  });

  it('returns true and refreshes device details when the backend accepts the token', async () => {
    const auth = useAuthStore();
    auth.token = 'valid-device-token';
    auth.device = { machine_id: 'old-machine', name: 'Old POS' };
    mocks.api.verifyDeviceToken.mockResolvedValue({
      success: true,
      data: {
        device: {
          id: 10,
          machine_id: 'current-machine',
          name: 'Current POS',
          status: 'active',
        },
      },
    });

    await expect(auth.refreshToken()).resolves.toBe(true);

    expect(mocks.api.verifyDeviceToken).toHaveBeenCalledOnce();
    expect(auth.device).toMatchObject({
      machine_id: 'current-machine',
      status: 'active',
    });
    expect(auth.isAuthenticated).toBe(true);
  });

  it('returns false and clears the device token when verification returns 401', async () => {
    const auth = useAuthStore();
    auth.token = 'foreign-or-expired-token';
    auth.device = { machine_id: 'machine-1', name: 'POS' };
    mocks.api.verifyDeviceToken.mockRejectedValue({ response: { status: 401 } });

    await expect(auth.refreshToken()).resolves.toBe(false);

    expect(auth.token).toBeNull();
    expect(auth.isAuthenticated).toBe(false);
    expect(localStorage.removeItem).toHaveBeenCalledWith('pos_device_jwt');
  });

  it('keeps the existing token on network errors to preserve offline mode', async () => {
    const auth = useAuthStore();
    auth.token = 'possibly-valid-token';
    auth.device = { machine_id: 'machine-1', name: 'POS' };
    mocks.api.verifyDeviceToken.mockRejectedValue(new Error('Network Error'));

    await expect(auth.refreshToken()).resolves.toBe(true);

    expect(auth.token).toBe('possibly-valid-token');
    expect(auth.isAuthenticated).toBe(false);
  });

  it('does not verify the token when terminal registration is explicitly offline', async () => {
    const auth = useAuthStore();
    auth.token = 'stored-token';
    auth.device = { machine_id: 'machine-1', name: 'POS' };

    await expect(auth.ensureTerminalRegistered({ isOffline: true })).resolves.toEqual(auth.device);

    expect(mocks.createApiService).not.toHaveBeenCalled();
    expect(mocks.api.verifyDeviceToken).not.toHaveBeenCalled();
    expect(auth.offline).toBe(true);
  });
});
