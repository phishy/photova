import { describe, it, expect, vi } from 'vitest';
import request from 'supertest';
import { createServer } from './index.js';
import type { Config } from '../config/schema.js';

vi.mock('../router/index.js', () => {
  return {
    OperationRouter: class {
      execute = vi.fn().mockResolvedValue({
        image: Buffer.from('processed-image'),
        mimeType: 'image/png',
        metadata: {
          provider: 'mock',
          processingTime: 100,
        },
      });
      
      getAvailableOperations = vi.fn().mockReturnValue(['background-remove', 'unblur']);
      getProviderForOperation = vi.fn().mockReturnValue('replicate');
    },
  };
});

describe('Server', () => {
  const createConfig = (): Config => ({
    server: { port: 3000, host: '0.0.0.0' },
    auth: { enabled: false },
    operations: {
      'background-remove': { provider: 'replicate' },
    },
    providers: {
      replicate: { api_key: 'test-key', timeout: 30000 },
    },
  });

  describe('GET /api/health', () => {
    it('should return ok status', async () => {
      const app = createServer(createConfig());
      
      const response = await request(app).get('/api/health');
      
      expect(response.status).toBe(200);
      expect(response.body).toEqual({ status: 'ok', auth: false });
    });
  });

  describe('GET /api/operations', () => {
    it('should return list of available operations', async () => {
      const app = createServer(createConfig());
      
      const response = await request(app).get('/api/operations');
      
      expect(response.status).toBe(200);
      expect(response.body.operations).toHaveLength(2);
      expect(response.body.operations[0]).toHaveProperty('name');
      expect(response.body.operations[0]).toHaveProperty('provider');
    });
  });

  describe('POST /api/v1/:operation', () => {
    it('should process image with data URI', async () => {
      const app = createServer(createConfig());
      const base64Image = Buffer.from('test-image').toString('base64');
      
      const response = await request(app)
        .post('/api/v1/background-remove')
        .send({ image: `data:image/png;base64,${base64Image}` });
      
      expect(response.status).toBe(200);
      expect(response.body.image).toMatch(/^data:image\/png;base64,/);
      expect(response.body.metadata).toBeDefined();
      expect(response.body.metadata.provider).toBe('mock');
    });

    it('should process raw base64 image', async () => {
      const app = createServer(createConfig());
      const base64Image = Buffer.from('test-image').toString('base64');
      
      const response = await request(app)
        .post('/api/v1/background-remove')
        .send({ image: base64Image });
      
      expect(response.status).toBe(200);
      expect(response.body.image).toBeDefined();
    });

    it('should return 400 when image is missing', async () => {
      const app = createServer(createConfig());
      
      const response = await request(app)
        .post('/api/v1/background-remove')
        .send({});
      
      expect(response.status).toBe(400);
      expect(response.body.error).toBe('Image is required');
    });

    it('should pass options to router', async () => {
      const app = createServer(createConfig());
      const base64Image = Buffer.from('test-image').toString('base64');
      
      const response = await request(app)
        .post('/api/v1/unblur')
        .send({
          image: `data:image/png;base64,${base64Image}`,
          options: { scale: 2 },
        });
      
      expect(response.status).toBe(200);
    });
  });
});
