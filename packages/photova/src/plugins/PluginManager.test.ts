import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PluginManager, Plugin, PluginFactory } from './PluginManager';

// Mock Editor type
const createMockEditor = () => ({} as any);

describe('PluginManager', () => {
  let pluginManager: PluginManager;

  beforeEach(() => {
    pluginManager = new PluginManager();
  });

  describe('setEditor', () => {
    it('should set the editor instance', () => {
      const editor = createMockEditor();
      pluginManager.setEditor(editor);
      // Editor is private, but we can test it indirectly through plugin initialization
      expect(() => pluginManager.setEditor(editor)).not.toThrow();
    });
  });

  describe('register', () => {
    it('should register a plugin object', async () => {
      const plugin: Plugin = {
        name: 'test-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
      };

      await pluginManager.register(plugin);

      expect(pluginManager.getPlugin('test-plugin')).toBe(plugin);
    });

    it('should register a plugin factory', async () => {
      const plugin: Plugin = {
        name: 'factory-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
      };
      const factory: PluginFactory = () => plugin;

      await pluginManager.register(factory);

      expect(pluginManager.getPlugin('factory-plugin')).toBe(plugin);
    });

    it('should initialize plugin when editor is set', async () => {
      const editor = createMockEditor();
      pluginManager.setEditor(editor);

      const initializeFn = vi.fn();
      const plugin: Plugin = {
        name: 'init-plugin',
        version: '1.0.0',
        initialize: initializeFn,
      };

      await pluginManager.register(plugin);

      expect(initializeFn).toHaveBeenCalledWith({ editor });
    });

    it('should not initialize plugin when editor is not set', async () => {
      const initializeFn = vi.fn();
      const plugin: Plugin = {
        name: 'no-init-plugin',
        version: '1.0.0',
        initialize: initializeFn,
      };

      await pluginManager.register(plugin);

      expect(initializeFn).not.toHaveBeenCalled();
    });

    it('should warn and skip duplicate plugin registration', async () => {
      const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      const plugin: Plugin = {
        name: 'duplicate-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
      };

      await pluginManager.register(plugin);
      await pluginManager.register(plugin);

      expect(warnSpy).toHaveBeenCalledWith('Plugin "duplicate-plugin" is already registered');
      expect(pluginManager.getPlugins()).toHaveLength(1);

      warnSpy.mockRestore();
    });

    it('should handle async initialize', async () => {
      const editor = createMockEditor();
      pluginManager.setEditor(editor);

      let initialized = false;
      const plugin: Plugin = {
        name: 'async-plugin',
        version: '1.0.0',
        initialize: async () => {
          await new Promise((resolve) => setTimeout(resolve, 10));
          initialized = true;
        },
      };

      await pluginManager.register(plugin);

      expect(initialized).toBe(true);
    });
  });

  describe('unregister', () => {
    it('should unregister a plugin', async () => {
      const plugin: Plugin = {
        name: 'removable-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
      };

      await pluginManager.register(plugin);
      expect(pluginManager.getPlugin('removable-plugin')).toBe(plugin);

      await pluginManager.unregister('removable-plugin');
      expect(pluginManager.getPlugin('removable-plugin')).toBeUndefined();
    });

    it('should call destroy on unregister if available', async () => {
      const destroyFn = vi.fn();
      const plugin: Plugin = {
        name: 'destroyable-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
        destroy: destroyFn,
      };

      await pluginManager.register(plugin);
      await pluginManager.unregister('destroyable-plugin');

      expect(destroyFn).toHaveBeenCalled();
    });

    it('should handle async destroy', async () => {
      let destroyed = false;
      const plugin: Plugin = {
        name: 'async-destroy-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
        destroy: async () => {
          await new Promise((resolve) => setTimeout(resolve, 10));
          destroyed = true;
        },
      };

      await pluginManager.register(plugin);
      await pluginManager.unregister('async-destroy-plugin');

      expect(destroyed).toBe(true);
    });

    it('should do nothing when unregistering non-existent plugin', async () => {
      await expect(pluginManager.unregister('non-existent')).resolves.not.toThrow();
    });
  });

  describe('getPlugin', () => {
    it('should return undefined for non-existent plugin', () => {
      expect(pluginManager.getPlugin('non-existent')).toBeUndefined();
    });

    it('should return plugin with correct type', async () => {
      interface CustomPlugin extends Plugin {
        customMethod(): void;
      }

      const plugin: CustomPlugin = {
        name: 'custom-plugin',
        version: '1.0.0',
        initialize: vi.fn(),
        customMethod: vi.fn(),
      };

      await pluginManager.register(plugin);

      const retrieved = pluginManager.getPlugin<CustomPlugin>('custom-plugin');
      expect(retrieved?.customMethod).toBeDefined();
    });
  });

  describe('getPlugins', () => {
    it('should return empty array when no plugins registered', () => {
      expect(pluginManager.getPlugins()).toEqual([]);
    });

    it('should return all registered plugins', async () => {
      const plugin1: Plugin = { name: 'plugin-1', version: '1.0.0', initialize: vi.fn() };
      const plugin2: Plugin = { name: 'plugin-2', version: '1.0.0', initialize: vi.fn() };
      const plugin3: Plugin = { name: 'plugin-3', version: '1.0.0', initialize: vi.fn() };

      await pluginManager.register(plugin1);
      await pluginManager.register(plugin2);
      await pluginManager.register(plugin3);

      const plugins = pluginManager.getPlugins();
      expect(plugins).toHaveLength(3);
      expect(plugins).toContain(plugin1);
      expect(plugins).toContain(plugin2);
      expect(plugins).toContain(plugin3);
    });
  });

  describe('hooks', () => {
    describe('addHook', () => {
      it('should add a hook callback', () => {
        const callback = vi.fn();
        pluginManager.addHook('test-event', callback);

        // Verify by triggering
        pluginManager.trigger('test-event', { data: 'test' });
        expect(callback).toHaveBeenCalledWith({ data: 'test' });
      });

      it('should return unsubscribe function', () => {
        const callback = vi.fn();
        const unsubscribe = pluginManager.addHook('test-event', callback);

        unsubscribe();
        pluginManager.trigger('test-event', { data: 'test' });

        expect(callback).not.toHaveBeenCalled();
      });

      it('should allow multiple hooks for same event', async () => {
        const callback1 = vi.fn();
        const callback2 = vi.fn();

        pluginManager.addHook('multi-event', callback1);
        pluginManager.addHook('multi-event', callback2);

        await pluginManager.trigger('multi-event', 'data');

        expect(callback1).toHaveBeenCalledWith('data');
        expect(callback2).toHaveBeenCalledWith('data');
      });
    });

    describe('removeHook', () => {
      it('should remove a specific hook callback', async () => {
        const callback1 = vi.fn();
        const callback2 = vi.fn();

        pluginManager.addHook('remove-test', callback1);
        pluginManager.addHook('remove-test', callback2);

        pluginManager.removeHook('remove-test', callback1);

        await pluginManager.trigger('remove-test', 'data');

        expect(callback1).not.toHaveBeenCalled();
        expect(callback2).toHaveBeenCalled();
      });

      it('should handle removing non-existent hook', () => {
        const callback = vi.fn();
        expect(() => pluginManager.removeHook('non-existent', callback)).not.toThrow();
      });
    });

    describe('trigger', () => {
      it('should trigger all callbacks for an event', async () => {
        const results: number[] = [];
        pluginManager.addHook('order-test', () => results.push(1));
        pluginManager.addHook('order-test', () => results.push(2));
        pluginManager.addHook('order-test', () => results.push(3));

        await pluginManager.trigger('order-test', null);

        expect(results).toEqual([1, 2, 3]);
      });

      it('should do nothing for non-existent event', async () => {
        await expect(pluginManager.trigger('non-existent', null)).resolves.not.toThrow();
      });

      it('should handle async callbacks', async () => {
        let asyncResult = '';
        pluginManager.addHook('async-event', async (data: string) => {
          await new Promise((resolve) => setTimeout(resolve, 10));
          asyncResult = data;
        });

        await pluginManager.trigger('async-event', 'async-data');

        expect(asyncResult).toBe('async-data');
      });

      it('should catch and log errors in callbacks', async () => {
        const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const successCallback = vi.fn();

        pluginManager.addHook('error-event', () => {
          throw new Error('Test error');
        });
        pluginManager.addHook('error-event', successCallback);

        await pluginManager.trigger('error-event', 'data');

        expect(errorSpy).toHaveBeenCalled();
        expect(successCallback).toHaveBeenCalled(); // Should continue despite error

        errorSpy.mockRestore();
      });
    });
  });

  describe('destroyAll', () => {
    it('should destroy all plugins', async () => {
      const destroy1 = vi.fn();
      const destroy2 = vi.fn();

      const plugin1: Plugin = { name: 'p1', version: '1.0.0', initialize: vi.fn(), destroy: destroy1 };
      const plugin2: Plugin = { name: 'p2', version: '1.0.0', initialize: vi.fn(), destroy: destroy2 };

      await pluginManager.register(plugin1);
      await pluginManager.register(plugin2);

      await pluginManager.destroyAll();

      expect(destroy1).toHaveBeenCalled();
      expect(destroy2).toHaveBeenCalled();
      expect(pluginManager.getPlugins()).toHaveLength(0);
    });

    it('should clear all hooks', async () => {
      const callback = vi.fn();
      pluginManager.addHook('test-event', callback);

      await pluginManager.destroyAll();

      await pluginManager.trigger('test-event', 'data');
      expect(callback).not.toHaveBeenCalled();
    });

    it('should handle plugins without destroy method', async () => {
      const plugin: Plugin = { name: 'no-destroy', version: '1.0.0', initialize: vi.fn() };

      await pluginManager.register(plugin);
      await expect(pluginManager.destroyAll()).resolves.not.toThrow();
    });
  });
});
