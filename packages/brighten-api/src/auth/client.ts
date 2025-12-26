import PocketBase from 'pocketbase';
import type { User, ApiKey, UsageLog, UsageDaily } from './types.js';

let pbClient: PocketBase | null = null;
let adminAuthenticated = false;
let adminCredentials: { email: string; password: string } | null = null;

export interface PocketBaseConfig {
  url: string;
  adminEmail?: string;
  adminPassword?: string;
}

export async function initPocketBase(config: PocketBaseConfig): Promise<PocketBase> {
  pbClient = new PocketBase(config.url);
  pbClient.autoCancellation(false);

  if (config.adminEmail && config.adminPassword) {
    adminCredentials = { email: config.adminEmail, password: config.adminPassword };
    try {
      await pbClient.collection('_superusers').authWithPassword(
        config.adminEmail,
        config.adminPassword
      );
      adminAuthenticated = true;
      console.log('PocketBase admin authenticated successfully');
    } catch (error) {
      console.error('Failed to authenticate PocketBase admin:', error);
    }
  }

  return pbClient;
}

async function ensureAdminAuth(): Promise<void> {
  const pb = getPocketBase();
  
  if (pb.authStore.isValid && adminAuthenticated) {
    return;
  }
  
  if (!adminCredentials) {
    throw new Error('Admin credentials not configured. Cannot access admin-only resources.');
  }
  
  try {
    await pb.collection('_superusers').authWithPassword(
      adminCredentials.email,
      adminCredentials.password
    );
    adminAuthenticated = true;
  } catch (error) {
    adminAuthenticated = false;
    throw new Error('Failed to authenticate as admin: ' + (error instanceof Error ? error.message : String(error)));
  }
}

export function isAdminAuthenticated(): boolean {
  return adminAuthenticated;
}

export function getPocketBase(): PocketBase {
  if (!pbClient) {
    throw new Error('PocketBase not initialized. Call initPocketBase() first.');
  }
  return pbClient;
}

export async function authenticateAdmin(email: string, password: string): Promise<void> {
  const pb = getPocketBase();
  await pb.collection('_superusers').authWithPassword(email, password);
}

export const users = {
  async getById(id: string): Promise<User> {
    return getPocketBase().collection('users').getOne<User>(id);
  },

  async getByEmail(email: string): Promise<User | null> {
    try {
      return await getPocketBase()
        .collection('users')
        .getFirstListItem<User>(`email="${email}"`);
    } catch {
      return null;
    }
  },

  async update(id: string, data: Partial<User>): Promise<User> {
    return getPocketBase().collection('users').update<User>(id, data);
  },
};

export const apiKeys = {
  async create(data: Partial<ApiKey>): Promise<ApiKey> {
    await ensureAdminAuth();
    return getPocketBase().collection('api_keys').create<ApiKey>(data);
  },

  async getById(id: string): Promise<ApiKey> {
    await ensureAdminAuth();
    return getPocketBase().collection('api_keys').getOne<ApiKey>(id);
  },

  async getByHash(keyHash: string): Promise<ApiKey | null> {
    await ensureAdminAuth();
    try {
      return await getPocketBase()
        .collection('api_keys')
        .getFirstListItem<ApiKey>(`keyHash="${keyHash}"`);
    } catch {
      return null;
    }
  },

  async listByUser(userId: string): Promise<ApiKey[]> {
    const pb = getPocketBase();
    await ensureAdminAuth();
    
    const rawUrl = `${pb.baseURL}/api/collections/api_keys/records?page=1&perPage=100&filter=user="${userId}"`;
    
    const response = await fetch(rawUrl, {
      headers: {
        Authorization: pb.authStore.token,
      },
    });
    
    if (!response.ok) {
      const error = (await response.json()) as { message?: string };
      throw new Error(error.message || 'Failed to list API keys');
    }
    
    const result = (await response.json()) as { items: ApiKey[] };
    return result.items.sort((a, b) => 
      new Date(b.created).getTime() - new Date(a.created).getTime()
    );
  },

  async update(id: string, data: Partial<ApiKey>): Promise<ApiKey> {
    await ensureAdminAuth();
    return getPocketBase().collection('api_keys').update<ApiKey>(id, data);
  },

  async delete(id: string): Promise<boolean> {
    await ensureAdminAuth();
    return getPocketBase().collection('api_keys').delete(id);
  },
};

export const usageLogs = {
  async create(data: Partial<UsageLog>): Promise<UsageLog> {
    await ensureAdminAuth();
    return getPocketBase().collection('usage_logs').create<UsageLog>(data);
  },

  async listByUser(
    userId: string,
    options?: { limit?: number; offset?: number; startDate?: string; endDate?: string }
  ): Promise<UsageLog[]> {
    const { limit = 100, offset = 0, startDate, endDate } = options || {};

    let filter = `user="${userId}"`;
    if (startDate) filter += ` && created>="${startDate}"`;
    if (endDate) filter += ` && created<="${endDate}"`;

    const result = await getPocketBase()
      .collection('usage_logs')
      .getList<UsageLog>(Math.floor(offset / limit) + 1, limit, {
        filter,
        sort: '-created',
      });
    return result.items;
  },
};

export const usageDaily = {
  async upsert(userId: string, date: string, operation: string, data: Partial<UsageDaily>): Promise<UsageDaily> {
    await ensureAdminAuth();
    const pb = getPocketBase();
    try {
      const existing = await pb
        .collection('usage_daily')
        .getFirstListItem<UsageDaily>(`user="${userId}" && date="${date}" && operation="${operation}"`);

      return await pb.collection('usage_daily').update<UsageDaily>(existing.id, {
        requestCount: existing.requestCount + (data.requestCount || 0),
        errorCount: existing.errorCount + (data.errorCount || 0),
        totalLatencyMs: existing.totalLatencyMs + (data.totalLatencyMs || 0),
      });
    } catch {
      return await pb.collection('usage_daily').create<UsageDaily>({
        user: userId,
        date,
        operation,
        requestCount: data.requestCount || 0,
        errorCount: data.errorCount || 0,
        totalLatencyMs: data.totalLatencyMs || 0,
      });
    }
  },

  async getByUserAndDateRange(
    userId: string,
    startDate: string,
    endDate: string
  ): Promise<UsageDaily[]> {
    await ensureAdminAuth();
    const result = await getPocketBase()
      .collection('usage_daily')
      .getList<UsageDaily>(1, 1000, {
        filter: `user="${userId}" && date>="${startDate}" && date<="${endDate}"`,
        sort: 'date',
      });
    return result.items;
  },

  async getMonthlyTotal(userId: string, year: number, month: number): Promise<number> {
    const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
    const endDate = `${year}-${String(month).padStart(2, '0')}-31`;

    const records = await this.getByUserAndDateRange(userId, startDate, endDate);
    return records.reduce((sum, r) => sum + r.requestCount, 0);
  },
};
