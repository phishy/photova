import { describe, it, expect, vi, beforeEach } from 'vitest';
import { LayerManager } from './LayerManager';

function createMockCanvas(): HTMLCanvasElement {
  const canvas = document.createElement('canvas');
  canvas.width = 100;
  canvas.height = 100;
  return canvas;
}

function createMockImage(): HTMLImageElement {
  const img = document.createElement('img');
  Object.defineProperty(img, 'naturalWidth', { value: 100 });
  Object.defineProperty(img, 'naturalHeight', { value: 100 });
  return img;
}

describe('LayerManager', () => {
  let layerManager: LayerManager;

  beforeEach(() => {
    layerManager = new LayerManager();
  });

  describe('addImageLayer', () => {
    it('should add an image layer', () => {
      const canvas = createMockCanvas();
      const layer = layerManager.addImageLayer(canvas, { name: 'Test Image' });

      expect(layer.id).toBeDefined();
      expect(layer.type).toBe('image');
      expect(layer.name).toBe('Test Image');
      expect(layer.source).toBe(canvas);
    });

    it('should emit layer:add event', () => {
      const callback = vi.fn();
      layerManager.on('layer:add', callback);

      const canvas = createMockCanvas();
      layerManager.addImageLayer(canvas);

      expect(callback).toHaveBeenCalledTimes(1);
      expect(callback).toHaveBeenCalledWith(expect.objectContaining({
        layer: expect.objectContaining({ type: 'image' }),
      }));
    });

    it('should use default name if not provided', () => {
      const canvas = createMockCanvas();
      const layer = layerManager.addImageLayer(canvas);

      expect(layer.name).toBe('Image Layer');
    });
  });

  describe('addTextLayer', () => {
    it('should add a text layer', () => {
      const layer = layerManager.addTextLayer('Hello World', { fontSize: 24 });

      expect(layer.type).toBe('text');
      expect(layer.text).toBe('Hello World');
      expect(layer.fontSize).toBe(24);
    });

    it('should use default font settings', () => {
      const layer = layerManager.addTextLayer('Test');

      expect(layer.fontFamily).toBe('Arial');
      expect(layer.fontSize).toBe(24);
      expect(layer.color).toBe('#000000');
    });
  });

  describe('addShapeLayer', () => {
    it('should add a rectangle shape layer', () => {
      const layer = layerManager.addShapeLayer('rectangle', { fill: '#ff0000' });

      expect(layer.type).toBe('shape');
      expect(layer.shapeType).toBe('rectangle');
      expect(layer.fill).toBe('#ff0000');
    });

    it('should add an ellipse shape layer', () => {
      const layer = layerManager.addShapeLayer('ellipse');

      expect(layer.shapeType).toBe('ellipse');
    });
  });

  describe('addDrawingLayer', () => {
    it('should add a drawing layer with empty paths', () => {
      const layer = layerManager.addDrawingLayer({ name: 'My Drawing' });

      expect(layer.type).toBe('drawing');
      expect(layer.paths).toEqual([]);
      expect(layer.name).toBe('My Drawing');
    });
  });

  describe('getLayer', () => {
    it('should get layer by id', () => {
      const layer = layerManager.addTextLayer('Test');
      const retrieved = layerManager.getLayer(layer.id);

      expect(retrieved).toBe(layer);
    });

    it('should return undefined for non-existent layer', () => {
      const retrieved = layerManager.getLayer('nonexistent');
      expect(retrieved).toBeUndefined();
    });
  });

  describe('getLayers', () => {
    it('should return all layers in order', () => {
      layerManager.addTextLayer('First');
      layerManager.addTextLayer('Second');
      layerManager.addTextLayer('Third');

      const layers = layerManager.getLayers();

      expect(layers).toHaveLength(3);
      expect(layers[0].name).toContain('Text');
      expect(layers[1].name).toContain('Text');
      expect(layers[2].name).toContain('Text');
    });

    it('should return empty array when no layers', () => {
      expect(layerManager.getLayers()).toEqual([]);
    });
  });

  describe('updateLayer', () => {
    it('should update layer properties', () => {
      const layer = layerManager.addTextLayer('Test', { fontSize: 24 });
      layerManager.updateLayer(layer.id, { fontSize: 48, color: '#ff0000' });

      const updated = layerManager.getLayer(layer.id);
      expect(updated?.fontSize).toBe(48);
      expect(updated?.color).toBe('#ff0000');
    });

    it('should emit layer:update event', () => {
      const callback = vi.fn();
      const layer = layerManager.addTextLayer('Test');
      layerManager.on('layer:update', callback);

      layerManager.updateLayer(layer.id, { opacity: 0.5 });

      expect(callback).toHaveBeenCalledWith({
        layerId: layer.id,
        changes: { opacity: 0.5 },
      });
    });

    it('should not emit event for non-existent layer', () => {
      const callback = vi.fn();
      layerManager.on('layer:update', callback);

      layerManager.updateLayer('nonexistent', { opacity: 0.5 });

      expect(callback).not.toHaveBeenCalled();
    });
  });

  describe('removeLayer', () => {
    it('should remove layer', () => {
      const layer = layerManager.addTextLayer('Test');
      layerManager.removeLayer(layer.id);

      expect(layerManager.getLayer(layer.id)).toBeUndefined();
      expect(layerManager.getLayers()).toHaveLength(0);
    });

    it('should emit layer:remove event', () => {
      const callback = vi.fn();
      const layer = layerManager.addTextLayer('Test');
      layerManager.on('layer:remove', callback);

      layerManager.removeLayer(layer.id);

      expect(callback).toHaveBeenCalledWith({ layerId: layer.id });
    });

    it('should not emit event for non-existent layer', () => {
      const callback = vi.fn();
      layerManager.on('layer:remove', callback);

      layerManager.removeLayer('nonexistent');

      expect(callback).not.toHaveBeenCalled();
    });
  });

  describe('layer selection', () => {
    it('should select layer', () => {
      const layer = layerManager.addTextLayer('Test');
      layerManager.selectLayer(layer.id);

      expect(layerManager.getActiveId()).toBe(layer.id);
      expect(layerManager.getActiveLayer()).toBe(layer);
    });

    it('should emit layer:select event', () => {
      const callback = vi.fn();
      const layer = layerManager.addTextLayer('Test');
      layerManager.on('layer:select', callback);

      layerManager.selectLayer(layer.id);

      expect(callback).toHaveBeenCalledWith({
        layerIds: [layer.id],
      });
    });

    it('should add to selection with addToSelection flag', () => {
      const layer1 = layerManager.addTextLayer('Test 1');
      const layer2 = layerManager.addTextLayer('Test 2');

      layerManager.selectLayer(layer1.id);
      layerManager.selectLayer(layer2.id, true);

      expect(layerManager.getSelectedIds()).toContain(layer1.id);
      expect(layerManager.getSelectedIds()).toContain(layer2.id);
    });

    it('should replace selection without addToSelection flag', () => {
      const layer1 = layerManager.addTextLayer('Test 1');
      const layer2 = layerManager.addTextLayer('Test 2');

      layerManager.selectLayer(layer1.id);
      layerManager.selectLayer(layer2.id);

      expect(layerManager.getSelectedIds()).not.toContain(layer1.id);
      expect(layerManager.getSelectedIds()).toContain(layer2.id);
    });

    it('should deselect layer', () => {
      const layer = layerManager.addTextLayer('Test');
      layerManager.selectLayer(layer.id);
      layerManager.deselectLayer(layer.id);

      expect(layerManager.getSelectedIds()).not.toContain(layer.id);
    });

    it('should clear selection', () => {
      const layer1 = layerManager.addTextLayer('Test 1');
      const layer2 = layerManager.addTextLayer('Test 2');

      layerManager.selectLayer(layer1.id);
      layerManager.selectLayer(layer2.id, true);
      layerManager.clearSelection();

      expect(layerManager.getSelectedIds()).toHaveLength(0);
      expect(layerManager.getActiveId()).toBeNull();
    });
  });

  describe('layer ordering', () => {
    it('should reorder layers', () => {
      const layer1 = layerManager.addTextLayer('Layer 1');
      const layer2 = layerManager.addTextLayer('Layer 2');
      const layer3 = layerManager.addTextLayer('Layer 3');

      layerManager.reorderLayers([layer3.id, layer1.id, layer2.id]);

      const layers = layerManager.getLayers();
      expect(layers[0].id).toBe(layer3.id);
      expect(layers[1].id).toBe(layer1.id);
      expect(layers[2].id).toBe(layer2.id);
    });

    it('should move layer up', () => {
      const layer1 = layerManager.addTextLayer('Layer 1');
      const layer2 = layerManager.addTextLayer('Layer 2');

      layerManager.moveLayerUp(layer1.id);

      const layers = layerManager.getLayers();
      expect(layers[0].id).toBe(layer2.id);
      expect(layers[1].id).toBe(layer1.id);
    });

    it('should move layer down', () => {
      const layer1 = layerManager.addTextLayer('Layer 1');
      const layer2 = layerManager.addTextLayer('Layer 2');

      layerManager.moveLayerDown(layer2.id);

      const layers = layerManager.getLayers();
      expect(layers[0].id).toBe(layer2.id);
      expect(layers[1].id).toBe(layer1.id);
    });
  });

  describe('duplicateLayer', () => {
    it('should duplicate layer', () => {
      const layer = layerManager.addTextLayer('Original', { fontSize: 32 });
      const duplicate = layerManager.duplicateLayer(layer.id);

      expect(duplicate).not.toBeNull();
      expect(duplicate?.id).not.toBe(layer.id);
      expect(duplicate?.name).toContain('Copy');
      expect(duplicate?.text).toBe('Original');
    });

    it('should return null for non-existent layer', () => {
      const result = layerManager.duplicateLayer('nonexistent');
      expect(result).toBeNull();
    });
  });

  describe('clear', () => {
    it('should clear all layers', () => {
      layerManager.addTextLayer('Layer 1');
      layerManager.addTextLayer('Layer 2');
      layerManager.clear();

      expect(layerManager.getLayers()).toHaveLength(0);
      expect(layerManager.getActiveId()).toBeNull();
    });
  });

  describe('layer properties', () => {
    it('should have correct default properties', () => {
      const layer = layerManager.addTextLayer('Test');

      expect(layer.visible).toBe(true);
      expect(layer.locked).toBe(false);
      expect(layer.opacity).toBe(1);
      expect(layer.blendMode).toBe('normal');
      expect(layer.transform).toEqual({
        x: 0,
        y: 0,
        scaleX: 1,
        scaleY: 1,
        rotation: 0,
        skewX: 0,
        skewY: 0,
      });
    });
  });
});
