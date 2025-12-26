import { Router, Request, Response } from 'express';
import PocketBase from 'pocketbase';
import { getPocketBase, apiKeys, users } from '../auth/client.js';
import { generateApiKey } from '../auth/middleware.js';
import { getUsageSummary, getTimeSeries, getCurrentMonthUsage } from '../usage/service.js';
import { PLAN_LIMITS } from '../auth/types.js';

export function createAuthRoutes(): Router {
  const router = Router();

  router.post('/signup', async (req: Request, res: Response) => {
    const { email, password, name } = req.body;

    if (!email || !password) {
      res.status(400).json({ error: 'Email and password are required' });
      return;
    }

    try {
      const pb = getPocketBase();
      const user = await pb.collection('users').create({
        email,
        password,
        passwordConfirm: password,
        name: name || email.split('@')[0],
        plan: 'free',
        monthlyLimit: PLAN_LIMITS.free.monthlyRequests,
        verified: false,
      });

      await pb.collection('users').requestVerification(email);

      res.status(201).json({
        id: user.id,
        email: user.email,
        name: user.name,
        plan: 'free',
        message: 'Account created. Please check your email to verify.',
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Signup failed';
      res.status(400).json({ error: message });
    }
  });

  router.post('/login', async (req: Request, res: Response) => {
    const { email, password } = req.body;

    if (!email || !password) {
      res.status(400).json({ error: 'Email and password are required' });
      return;
    }

    try {
      // Use a separate PocketBase client for user auth to avoid overwriting admin auth
      const adminPb = getPocketBase();
      const userPb = new PocketBase(adminPb.baseURL);
      const authData = await userPb.collection('users').authWithPassword(email, password);

      res.json({
        token: authData.token,
        user: {
          id: authData.record.id,
          email: authData.record.email,
          name: authData.record.name,
          plan: authData.record.plan,
          verified: authData.record.verified,
        },
      });
    } catch (error) {
      res.status(401).json({ error: 'Invalid credentials' });
    }
  });

  router.post('/logout', async (_req: Request, res: Response) => {
    res.json({ success: true });
  });

  router.get('/me', async (req: Request, res: Response) => {
    const authHeader = req.headers.authorization;
    if (!authHeader?.startsWith('Bearer ')) {
      res.status(401).json({ error: 'Not authenticated' });
      return;
    }

    try {
      const token = authHeader.substring(7);
      const payload = JSON.parse(atob(token.split('.')[1]));
      
      if (!payload.exp || payload.exp * 1000 < Date.now()) {
        res.status(401).json({ error: 'Invalid or expired token' });
        return;
      }

      const pb = getPocketBase();
      const user = await pb.collection('users').getOne(payload.id);
      const usage = await getCurrentMonthUsage(user.id);

      res.json({
        id: user.id,
        email: user.email,
        name: user.name,
        plan: user.plan,
        verified: user.verified,
        usage,
      });
    } catch (error) {
      res.status(401).json({ error: 'Not authenticated' });
    }
  });

  router.patch('/me', async (req: Request, res: Response) => {
    const authHeader = req.headers.authorization;
    if (!authHeader?.startsWith('Bearer ')) {
      res.status(401).json({ error: 'Not authenticated' });
      return;
    }

    const { name } = req.body;

    try {
      const token = authHeader.substring(7);
      const payload = JSON.parse(atob(token.split('.')[1]));
      
      if (!payload.exp || payload.exp * 1000 < Date.now()) {
        res.status(401).json({ error: 'Invalid or expired token' });
        return;
      }

      const updated = await users.update(payload.id, { name });

      res.json({
        id: updated.id,
        email: updated.email,
        name: updated.name,
        plan: updated.plan,
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Update failed';
      res.status(400).json({ error: message });
    }
  });

  return router;
}

export function createApiKeysRoutes(): Router {
  const router = Router();

  const requireAuth = async (req: Request, res: Response): Promise<{ userId: string; token: string } | null> => {
    const authHeader = req.headers.authorization;
    if (!authHeader?.startsWith('Bearer ')) {
      res.status(401).json({ error: 'Not authenticated' });
      return null;
    }

    try {
      const token = authHeader.substring(7);
      const payload = JSON.parse(atob(token.split('.')[1]));
      
      if (!payload.exp || payload.exp * 1000 < Date.now()) {
        res.status(401).json({ error: 'Invalid or expired token' });
        return null;
      }

      return { userId: payload.id, token };
    } catch {
      res.status(401).json({ error: 'Invalid or expired token' });
      return null;
    }
  };

  router.get('/', async (req: Request, res: Response) => {
    const auth = await requireAuth(req, res);
    if (!auth) return;

    try {
      const keys = await apiKeys.listByUser(auth.userId);

      res.json({
        keys: keys.map((k) => ({
          id: k.id,
          name: k.name,
          prefix: k.keyPrefix,
          status: k.status,
          scopes: k.scopes,
          lastUsedAt: k.lastUsedAt,
          expiresAt: k.expiresAt,
          created: k.created,
        })),
      });
    } catch (error) {
      console.error('Failed to list keys:', error);
      const message = error instanceof Error ? error.message : 'Failed to list keys';
      const stack = error instanceof Error ? error.stack : undefined;
      res.status(500).json({ error: message, debug: { stack, name: (error as Error)?.name } });
    }
  });

  router.post('/', async (req: Request, res: Response) => {
    const auth = await requireAuth(req, res);
    if (!auth) return;

    const { name, scopes, expiresAt } = req.body;

    try {
      const { key, hash, prefix } = generateApiKey();

      const apiKey = await apiKeys.create({
        user: auth.userId,
        name: name || 'Default API Key',
        keyHash: hash,
        keyPrefix: prefix,
        status: 'active',
        scopes: scopes || ['*'],
        expiresAt,
      });

      res.status(201).json({
        id: apiKey.id,
        key,
        name: apiKey.name,
        prefix: apiKey.keyPrefix,
        scopes: apiKey.scopes,
        expiresAt: apiKey.expiresAt,
        created: apiKey.created,
        message: 'Store this key securely. It will not be shown again.',
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to create key';
      res.status(500).json({ error: message });
    }
  });

  router.patch('/:id', async (req: Request, res: Response) => {
    const auth = await requireAuth(req, res);
    if (!auth) return;

    const { id } = req.params;
    const { name, scopes, status } = req.body;

    try {
      const existingKey = await apiKeys.getById(id);

      if (existingKey.user !== auth.userId) {
        res.status(403).json({ error: 'Not authorized' });
        return;
      }

      const updates: Record<string, unknown> = {};
      if (name !== undefined) updates.name = name;
      if (scopes !== undefined) updates.scopes = scopes;
      if (status !== undefined) updates.status = status;

      const updated = await apiKeys.update(id, updates);

      res.json({
        id: updated.id,
        name: updated.name,
        prefix: updated.keyPrefix,
        status: updated.status,
        scopes: updated.scopes,
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to update key';
      res.status(500).json({ error: message });
    }
  });

  router.delete('/:id', async (req: Request, res: Response) => {
    const auth = await requireAuth(req, res);
    if (!auth) return;

    const { id } = req.params;

    try {
      const existingKey = await apiKeys.getById(id);

      if (existingKey.user !== auth.userId) {
        res.status(403).json({ error: 'Not authorized' });
        return;
      }

      await apiKeys.delete(id);
      res.json({ success: true });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to delete key';
      res.status(500).json({ error: message });
    }
  });

  router.post('/:id/regenerate', async (req: Request, res: Response) => {
    const auth = await requireAuth(req, res);
    if (!auth) return;

    const { id } = req.params;

    try {
      const existingKey = await apiKeys.getById(id);

      if (existingKey.user !== auth.userId) {
        res.status(403).json({ error: 'Not authorized' });
        return;
      }

      const { key, hash, prefix } = generateApiKey();

      const updated = await apiKeys.update(id, {
        keyHash: hash,
        keyPrefix: prefix,
      });

      res.json({
        id: updated.id,
        key,
        name: updated.name,
        prefix: updated.keyPrefix,
        message: 'Store this key securely. It will not be shown again.',
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to regenerate key';
      res.status(500).json({ error: message });
    }
  });

  return router;
}

export function createUsageRoutes(): Router {
  const router = Router();

  const requireAuth = async (req: Request, res: Response): Promise<string | null> => {
    const authHeader = req.headers.authorization;
    if (!authHeader?.startsWith('Bearer ')) {
      res.status(401).json({ error: 'Not authenticated' });
      return null;
    }

    try {
      const token = authHeader.substring(7);
      const payload = JSON.parse(atob(token.split('.')[1]));
      
      if (!payload.exp || payload.exp * 1000 < Date.now()) {
        res.status(401).json({ error: 'Invalid or expired token' });
        return null;
      }

      return payload.id;
    } catch {
      res.status(401).json({ error: 'Invalid or expired token' });
      return null;
    }
  };

  router.get('/summary', async (req: Request, res: Response) => {
    const userId = await requireAuth(req, res);
    if (!userId) return;

    const { start, end } = req.query;

    const now = new Date();
    const defaultStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const defaultEnd = now.toISOString().split('T')[0];

    try {
      const summary = await getUsageSummary(
        userId,
        (start as string) || defaultStart,
        (end as string) || defaultEnd
      );
      res.json(summary);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to get usage summary';
      res.status(500).json({ error: message });
    }
  });

  router.get('/timeseries', async (req: Request, res: Response) => {
    const userId = await requireAuth(req, res);
    if (!userId) return;

    const { start, end, metric } = req.query;

    const now = new Date();
    const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    const defaultStart = thirtyDaysAgo.toISOString().split('T')[0];
    const defaultEnd = now.toISOString().split('T')[0];

    try {
      const data = await getTimeSeries(
        userId,
        (start as string) || defaultStart,
        (end as string) || defaultEnd,
        (metric as 'requests' | 'errors' | 'latency') || 'requests'
      );
      res.json({ data });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to get time series';
      res.status(500).json({ error: message });
    }
  });

  router.get('/current', async (req: Request, res: Response) => {
    const userId = await requireAuth(req, res);
    if (!userId) return;

    try {
      const usage = await getCurrentMonthUsage(userId);
      res.json(usage);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to get current usage';
      res.status(500).json({ error: message });
    }
  });

  return router;
}
