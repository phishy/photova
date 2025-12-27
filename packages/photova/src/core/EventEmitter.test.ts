import { describe, it, expect, vi } from 'vitest';
import { EventEmitter } from './EventEmitter';

interface TestEvents {
  'test:event': { value: number };
  'test:string': string;
  'test:void': undefined;
}

describe('EventEmitter', () => {
  it('should subscribe and emit events', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback = vi.fn();

    emitter.on('test:event', callback);
    emitter.emit('test:event', { value: 42 });

    expect(callback).toHaveBeenCalledWith({ value: 42 });
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it('should handle multiple listeners for same event', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback1 = vi.fn();
    const callback2 = vi.fn();

    emitter.on('test:event', callback1);
    emitter.on('test:event', callback2);
    emitter.emit('test:event', { value: 10 });

    expect(callback1).toHaveBeenCalledWith({ value: 10 });
    expect(callback2).toHaveBeenCalledWith({ value: 10 });
  });

  it('should unsubscribe using returned function', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback = vi.fn();

    const unsubscribe = emitter.on('test:event', callback);
    emitter.emit('test:event', { value: 1 });
    expect(callback).toHaveBeenCalledTimes(1);

    unsubscribe();
    emitter.emit('test:event', { value: 2 });
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it('should unsubscribe using off method', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback = vi.fn();

    emitter.on('test:event', callback);
    emitter.emit('test:event', { value: 1 });
    expect(callback).toHaveBeenCalledTimes(1);

    emitter.off('test:event', callback);
    emitter.emit('test:event', { value: 2 });
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it('should fire once listener only once', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback = vi.fn();

    emitter.once('test:event', callback);
    emitter.emit('test:event', { value: 1 });
    emitter.emit('test:event', { value: 2 });

    expect(callback).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledWith({ value: 1 });
  });

  it('should remove all listeners for specific event', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback1 = vi.fn();
    const callback2 = vi.fn();

    emitter.on('test:event', callback1);
    emitter.on('test:event', callback2);
    emitter.removeAllListeners('test:event');
    emitter.emit('test:event', { value: 1 });

    expect(callback1).not.toHaveBeenCalled();
    expect(callback2).not.toHaveBeenCalled();
  });

  it('should remove all listeners when no event specified', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback1 = vi.fn();
    const callback2 = vi.fn();

    emitter.on('test:event', callback1);
    emitter.on('test:string', callback2);
    emitter.removeAllListeners();
    emitter.emit('test:event', { value: 1 });
    emitter.emit('test:string', 'hello');

    expect(callback1).not.toHaveBeenCalled();
    expect(callback2).not.toHaveBeenCalled();
  });

  it('should return correct listener count', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback1 = vi.fn();
    const callback2 = vi.fn();

    expect(emitter.listenerCount('test:event')).toBe(0);

    emitter.on('test:event', callback1);
    expect(emitter.listenerCount('test:event')).toBe(1);

    emitter.on('test:event', callback2);
    expect(emitter.listenerCount('test:event')).toBe(2);

    emitter.off('test:event', callback1);
    expect(emitter.listenerCount('test:event')).toBe(1);
  });

  it('should handle errors in listeners without breaking other listeners', () => {
    const emitter = new EventEmitter<TestEvents>();
    const errorCallback = vi.fn(() => {
      throw new Error('Test error');
    });
    const normalCallback = vi.fn();
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

    emitter.on('test:event', errorCallback);
    emitter.on('test:event', normalCallback);
    emitter.emit('test:event', { value: 1 });

    expect(errorCallback).toHaveBeenCalled();
    expect(normalCallback).toHaveBeenCalled();
    expect(consoleError).toHaveBeenCalled();

    consoleError.mockRestore();
  });

  it('should handle emitting events with no listeners', () => {
    const emitter = new EventEmitter<TestEvents>();
    expect(() => emitter.emit('test:event', { value: 1 })).not.toThrow();
  });

  it('should handle off for non-existent listener', () => {
    const emitter = new EventEmitter<TestEvents>();
    const callback = vi.fn();
    expect(() => emitter.off('test:event', callback)).not.toThrow();
  });
});
