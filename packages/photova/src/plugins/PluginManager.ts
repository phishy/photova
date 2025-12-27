import type { Editor } from '../core/Editor';

export interface PluginContext {
  editor: Editor;
}

export interface Plugin {
  name: string;
  version: string;
  initialize(context: PluginContext): void | Promise<void>;
  destroy?(): void | Promise<void>;
}

export type PluginFactory = () => Plugin;

export type HookCallback<T = unknown> = (data: T) => void | Promise<void>;

export class PluginManager {
  private plugins: Map<string, Plugin> = new Map();
  private hooks: Map<string, Set<HookCallback>> = new Map();
  private editor: Editor | null = null;

  setEditor(editor: Editor): void {
    this.editor = editor;
  }

  async register(pluginOrFactory: Plugin | PluginFactory): Promise<void> {
    const plugin = typeof pluginOrFactory === 'function' ? pluginOrFactory() : pluginOrFactory;

    if (this.plugins.has(plugin.name)) {
      console.warn(`Plugin "${plugin.name}" is already registered`);
      return;
    }

    if (this.editor) {
      await plugin.initialize({ editor: this.editor });
    }

    this.plugins.set(plugin.name, plugin);
  }

  async unregister(name: string): Promise<void> {
    const plugin = this.plugins.get(name);
    if (!plugin) return;

    if (plugin.destroy) {
      await plugin.destroy();
    }

    this.plugins.delete(name);
  }

  getPlugin<T extends Plugin = Plugin>(name: string): T | undefined {
    return this.plugins.get(name) as T | undefined;
  }

  getPlugins(): Plugin[] {
    return [...this.plugins.values()];
  }

  addHook<T = unknown>(event: string, callback: HookCallback<T>): () => void {
    if (!this.hooks.has(event)) {
      this.hooks.set(event, new Set());
    }
    this.hooks.get(event)!.add(callback as HookCallback);

    return () => this.removeHook(event, callback);
  }

  removeHook<T = unknown>(event: string, callback: HookCallback<T>): void {
    const callbacks = this.hooks.get(event);
    if (callbacks) {
      callbacks.delete(callback as HookCallback);
    }
  }

  async trigger<T = unknown>(event: string, data: T): Promise<void> {
    const callbacks = this.hooks.get(event);
    if (!callbacks) return;

    for (const callback of callbacks) {
      try {
        await callback(data);
      } catch (error) {
        console.error(`Error in hook "${event}":`, error);
      }
    }
  }

  async destroyAll(): Promise<void> {
    for (const plugin of this.plugins.values()) {
      if (plugin.destroy) {
        await plugin.destroy();
      }
    }
    this.plugins.clear();
    this.hooks.clear();
  }
}
