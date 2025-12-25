import type { RecordModel } from 'pocketbase';

export interface User extends RecordModel {
  email: string;
  name: string;
  avatar?: string;
  plan: 'free' | 'pro' | 'enterprise';
  monthlyLimit: number;
  verified: boolean;
}

export interface ApiKey extends RecordModel {
  user: string;
  name: string;
  keyHash: string;
  keyPrefix: string;
  status: 'active' | 'revoked';
  lastUsedAt?: string;
  expiresAt?: string;
  scopes: string[];
}

export interface UsageLog extends RecordModel {
  user: string;
  apiKey: string;
  operation: string;
  status: 'success' | 'error';
  latencyMs: number;
  requestId: string;
  errorMessage?: string;
  metadata?: Record<string, unknown>;
}

export interface UsageDaily extends RecordModel {
  user: string;
  date: string;
  operation: string;
  requestCount: number;
  errorCount: number;
  totalLatencyMs: number;
}

export const PLAN_LIMITS: Record<string, { monthlyRequests: number; rateLimit: number }> = {
  free: { monthlyRequests: 100, rateLimit: 10 },
  pro: { monthlyRequests: 10000, rateLimit: 100 },
  enterprise: { monthlyRequests: 1000000, rateLimit: 1000 },
};

export const API_KEY_PREFIX = 'br_live_';

export interface AuthenticatedRequest {
  user: User;
  apiKey: ApiKey;
  requestId: string;
}
