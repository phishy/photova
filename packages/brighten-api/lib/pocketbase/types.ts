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
  key: string;
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

export const API_KEY_PREFIX = 'br_live_';
