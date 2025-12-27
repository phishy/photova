import { describe, it, expect, vi, beforeEach } from 'vitest';
import { HistoryManager } from './HistoryManager';
import type { EditorState } from './types';

function createMockState(overrides: Partial<EditorState> = {}): EditorState {
  return {
    layers: [],
    activeLayerId: null,
    selectedLayerIds: [],
    activeTool: 'select',
    zoom: 1,
    pan: { x: 0, y: 0 },
    canvasSize: { width: 800, height: 600 },
    originalImageSize: { width: 800, height: 600 },
    isDirty: false,
    ...overrides,
  };
}

describe('HistoryManager', () => {
  let historyManager: HistoryManager;

  beforeEach(() => {
    historyManager = new HistoryManager();
  });

  describe('push', () => {
    it('should add entry to history', () => {
      const state = createMockState();
      historyManager.push('Initial state', state);

      expect(historyManager.getHistory()).toHaveLength(1);
      expect(historyManager.getHistory()[0].action).toBe('Initial state');
    });

    it('should emit history:change event', () => {
      const callback = vi.fn();
      historyManager.on('history:change', callback);

      const state = createMockState();
      historyManager.push('Test', state);

      expect(callback).toHaveBeenCalledWith({
        canUndo: false,
        canRedo: false,
      });
    });

    it('should truncate future history on new push after undo', () => {
      const state1 = createMockState({ zoom: 1 });
      const state2 = createMockState({ zoom: 2 });
      const state3 = createMockState({ zoom: 3 });

      historyManager.push('State 1', state1);
      historyManager.push('State 2', state2);
      historyManager.undo();
      historyManager.push('State 3', state3);

      expect(historyManager.getHistory()).toHaveLength(2);
      expect(historyManager.canRedo()).toBe(false);
    });
  });

  describe('undo', () => {
    it('should return null when no history', () => {
      const result = historyManager.undo();
      expect(result).toBeNull();
    });

    it('should return null when at beginning of history', () => {
      const state = createMockState();
      historyManager.push('Initial', state);
      
      const result = historyManager.undo();
      expect(result).toBeNull();
    });

    it('should return previous entry', () => {
      const state1 = createMockState({ zoom: 1 });
      const state2 = createMockState({ zoom: 2 });

      historyManager.push('State 1', state1);
      historyManager.push('State 2', state2);

      const result = historyManager.undo();

      expect(result).not.toBeNull();
      expect(result?.action).toBe('State 1');
    });

    it('should emit history:undo event', () => {
      const callback = vi.fn();
      const state1 = createMockState();
      const state2 = createMockState();

      historyManager.push('State 1', state1);
      historyManager.push('State 2', state2);
      historyManager.on('history:undo', callback);
      historyManager.undo();

      expect(callback).toHaveBeenCalledWith({
        entry: expect.objectContaining({ action: 'State 1' }),
      });
    });
  });

  describe('redo', () => {
    it('should return null when no future history', () => {
      const result = historyManager.redo();
      expect(result).toBeNull();
    });

    it('should return next entry after undo', () => {
      const state1 = createMockState({ zoom: 1 });
      const state2 = createMockState({ zoom: 2 });

      historyManager.push('State 1', state1);
      historyManager.push('State 2', state2);
      historyManager.undo();

      const result = historyManager.redo();

      expect(result).not.toBeNull();
      expect(result?.action).toBe('State 2');
    });

    it('should emit history:redo event', () => {
      const callback = vi.fn();
      const state1 = createMockState();
      const state2 = createMockState();

      historyManager.push('State 1', state1);
      historyManager.push('State 2', state2);
      historyManager.undo();
      historyManager.on('history:redo', callback);
      historyManager.redo();

      expect(callback).toHaveBeenCalledWith({
        entry: expect.objectContaining({ action: 'State 2' }),
      });
    });
  });

  describe('canUndo / canRedo', () => {
    it('should return false when empty', () => {
      expect(historyManager.canUndo()).toBe(false);
      expect(historyManager.canRedo()).toBe(false);
    });

    it('should return correct values', () => {
      const state1 = createMockState();
      const state2 = createMockState();

      historyManager.push('State 1', state1);
      expect(historyManager.canUndo()).toBe(false);
      expect(historyManager.canRedo()).toBe(false);

      historyManager.push('State 2', state2);
      expect(historyManager.canUndo()).toBe(true);
      expect(historyManager.canRedo()).toBe(false);

      historyManager.undo();
      expect(historyManager.canUndo()).toBe(false);
      expect(historyManager.canRedo()).toBe(true);
    });
  });

  describe('clear', () => {
    it('should clear all history', () => {
      const state = createMockState();
      historyManager.push('State 1', state);
      historyManager.push('State 2', state);
      historyManager.clear();

      expect(historyManager.getHistory()).toHaveLength(0);
      expect(historyManager.canUndo()).toBe(false);
      expect(historyManager.canRedo()).toBe(false);
    });

    it('should emit history:change event', () => {
      const callback = vi.fn();
      const state = createMockState();
      historyManager.push('State', state);
      historyManager.on('history:change', callback);
      historyManager.clear();

      expect(callback).toHaveBeenCalledWith({
        canUndo: false,
        canRedo: false,
      });
    });
  });

  describe('getCurrentState', () => {
    it('should return null when empty', () => {
      expect(historyManager.getCurrentState()).toBeNull();
    });

    it('should return current state', () => {
      const state = createMockState({ zoom: 2 });
      historyManager.push('State', state);

      const current = historyManager.getCurrentState();
      expect(current?.zoom).toBe(2);
    });
  });

  describe('batching', () => {
    it('should not add entries during batch', () => {
      const state = createMockState();

      historyManager.startBatch();
      historyManager.push('State 1', state);
      historyManager.push('State 2', state);

      expect(historyManager.getHistory()).toHaveLength(0);
    });

    it('should add single entry on endBatch', () => {
      const state = createMockState();

      historyManager.startBatch();
      historyManager.push('Ignored 1', state);
      historyManager.push('Ignored 2', state);
      historyManager.endBatch('Batched Action', state);

      expect(historyManager.getHistory()).toHaveLength(1);
      expect(historyManager.getHistory()[0].action).toBe('Batched Action');
    });

    it('should not add entry on cancelBatch', () => {
      const state = createMockState();

      historyManager.startBatch();
      historyManager.push('Ignored', state);
      historyManager.cancelBatch();

      expect(historyManager.getHistory()).toHaveLength(0);
    });
  });

  describe('maxSteps', () => {
    it('should respect maxSteps limit', () => {
      const manager = new HistoryManager(3);
      const state = createMockState();

      manager.push('State 1', state);
      manager.push('State 2', state);
      manager.push('State 3', state);
      manager.push('State 4', state);

      expect(manager.getHistory()).toHaveLength(3);
      expect(manager.getHistory()[0].action).toBe('State 2');
    });
  });

  describe('state serialization', () => {
    it('should serialize state with layers', () => {
      const state = createMockState({
        layers: [
          {
            id: 'layer-1',
            type: 'text',
            name: 'Test Layer',
            visible: true,
            locked: false,
            opacity: 1,
            blendMode: 'normal',
            transform: { x: 0, y: 0, scaleX: 1, scaleY: 1, rotation: 0, skewX: 0, skewY: 0 },
            text: 'Hello',
            fontFamily: 'Arial',
            fontSize: 24,
            fontWeight: 'normal',
            fontStyle: 'normal',
            color: '#000000',
            textAlign: 'left',
            lineHeight: 1.2,
            letterSpacing: 0,
          },
        ] as any,
      });

      historyManager.push('With layers', state);
      const current = historyManager.getCurrentState();

      expect(current?.layers).toHaveLength(1);
      expect(current?.layers[0].name).toBe('Test Layer');
    });
  });
});
