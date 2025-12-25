import { Request, Response, NextFunction } from 'express';
import { createHash } from 'crypto';
import { nanoid } from 'nanoid';
import { apiKeys, users, usageDaily } from './client.js';
import { API_KEY_PREFIX, PLAN_LIMITS, type User, type ApiKey } from './types.js';

declare global {
  namespace Express {
    interface Request {
      auth?: {
        user: User;
        apiKey: ApiKey;
        requestId: string;
      };
    }
  }
}

export function hashApiKey(key: string): string {
  return createHash('sha256').update(key).digest('hex');
}

export function generateApiKey(): { key: string; hash: string; prefix: string } {
  const randomPart = nanoid(32);
  const key = `${API_KEY_PREFIX}${randomPart}`;
  const hash = hashApiKey(key);
  const prefix = key.substring(0, 12) + '...';
  return { key, hash, prefix };
}

function extractApiKey(req: Request): string | null {
  const authHeader = req.headers.authorization;
  if (authHeader?.startsWith('Bearer ')) {
    return authHeader.substring(7);
  }

  const apiKeyHeader = req.headers['x-api-key'];
  if (typeof apiKeyHeader === 'string') {
    return apiKeyHeader;
  }

  return null;
}

export function requireApiKey() {
  return async (req: Request, res: Response, next: NextFunction): Promise<void> => {
    const requestId = nanoid(12);
    const key = extractApiKey(req);

    if (!key) {
      res.status(401).json({
        error: 'Missing API key',
        message: 'Provide API key via Authorization: Bearer <key> or X-API-Key header',
        requestId,
      });
      return;
    }

    if (!key.startsWith(API_KEY_PREFIX)) {
      res.status(401).json({
        error: 'Invalid API key format',
        requestId,
      });
      return;
    }

    try {
      const keyHash = hashApiKey(key);
      const apiKey = await apiKeys.getByHash(keyHash);

      if (!apiKey) {
        res.status(401).json({
          error: 'Invalid API key',
          requestId,
        });
        return;
      }

      if (apiKey.status !== 'active') {
        res.status(401).json({
          error: 'API key has been revoked',
          requestId,
        });
        return;
      }

      if (apiKey.expiresAt && new Date(apiKey.expiresAt) < new Date()) {
        res.status(401).json({
          error: 'API key has expired',
          requestId,
        });
        return;
      }

      const user = await users.getById(apiKey.user);

      if (!user) {
        res.status(401).json({
          error: 'User not found',
          requestId,
        });
        return;
      }

      const now = new Date();
      const monthlyUsage = await usageDaily.getMonthlyTotal(
        user.id,
        now.getFullYear(),
        now.getMonth() + 1
      );

      const planLimit = PLAN_LIMITS[user.plan]?.monthlyRequests || PLAN_LIMITS.free.monthlyRequests;

      if (monthlyUsage >= planLimit) {
        res.status(429).json({
          error: 'Monthly usage limit exceeded',
          message: `Your ${user.plan} plan allows ${planLimit} requests/month. Upgrade to continue.`,
          usage: { current: monthlyUsage, limit: planLimit },
          requestId,
        });
        return;
      }

      apiKeys.update(apiKey.id, { lastUsedAt: new Date().toISOString() }).catch(() => {});

      req.auth = {
        user,
        apiKey,
        requestId,
      };

      next();
    } catch (error) {
      console.error('Auth middleware error:', error);
      res.status(500).json({
        error: 'Authentication failed',
        requestId,
      });
    }
  };
}

export function optionalApiKey() {
  return async (req: Request, res: Response, next: NextFunction): Promise<void> => {
    const key = extractApiKey(req);

    if (!key) {
      next();
      return;
    }

    return requireApiKey()(req, res, next);
  };
}

export function requireScope(operation: string) {
  return (req: Request, res: Response, next: NextFunction): void => {
    if (!req.auth) {
      res.status(401).json({ error: 'Authentication required' });
      return;
    }

    const { apiKey, requestId } = req.auth;
    const scopes = apiKey.scopes || ['*'];

    if (scopes.includes('*') || scopes.includes(operation)) {
      next();
      return;
    }

    res.status(403).json({
      error: 'Insufficient permissions',
      message: `This API key does not have access to the '${operation}' operation`,
      requestId,
    });
  };
}
