import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { injectStyles } from './styles';

describe('EditorUI', () => {
  let container: HTMLElement;

  beforeEach(() => {
    container = document.createElement('div');
    container.id = 'test-editor';
    document.body.appendChild(container);
    injectStyles();
  });

  afterEach(() => {
    container.remove();
    const styles = document.getElementById('brighten-styles');
    if (styles) styles.remove();
  });

  describe('AI Processing Rainbow Border', () => {
    it('should have ai-processing glow styles defined', () => {
      const styles = document.getElementById('brighten-styles');
      expect(styles).toBeTruthy();
      expect(styles?.textContent).toContain('brighten-ai-border');
      expect(styles?.textContent).toContain('conic-gradient');
    });

    it('should include rainbow gradient colors in ai-border styles', () => {
      const styles = document.getElementById('brighten-styles');
      const styleContent = styles?.textContent || '';
      
      expect(styleContent).toContain('#BC82F3');
      expect(styleContent).toContain('#F5B9EA');
      expect(styleContent).toContain('#8D9FFF');
      expect(styleContent).toContain('#FF6778');
      expect(styleContent).toContain('#FFBA71');
    });

    it('should include rotation animation for ai-border', () => {
      const styles = document.getElementById('brighten-styles');
      const styleContent = styles?.textContent || '';
      
      expect(styleContent).toContain('brighten-ai-border-rotate');
      expect(styleContent).toContain('animation');
    });

    it('should have pointer-events: none on ai-border to not block interactions', () => {
      const styles = document.getElementById('brighten-styles');
      const styleContent = styles?.textContent || '';
      
      expect(styleContent).toMatch(/\.brighten-ai-border[\s\S]*pointer-events:\s*none/);
    });

    it('should use CSS mask to show only border (not fill)', () => {
      const styles = document.getElementById('brighten-styles');
      const styleContent = styles?.textContent || '';
      
      expect(styleContent).toContain('mask:');
      expect(styleContent).toContain('mask-composite');
    });
  });
});
