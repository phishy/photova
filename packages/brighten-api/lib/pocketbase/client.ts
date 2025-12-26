import PocketBase from 'pocketbase';
import type { User, ApiKey, UsageLog, UsageDaily } from './types';

let pbClient: PocketBase | null = null;
let adminAuthenticated = false;
let adminCredentials: { email: string; password: string } | null = null;

export function getPocketBase(): PocketBase {
  if (!pbClient) {
    pbClient = new PocketBase(process.env.POCKETBASE_URL || 'http://127.0.0.1:8090');
    pbClient.autoCancellation(false);
    
    if (process.env.POCKETBASE_ADMIN_EMAIL && process.env.POCKETBASE_ADMIN_PASSWORD) {
      adminCredentials = {
        email: process.env.POCKETBASE_ADMIN_EMAIL,
        password: process.env.POCKETBASE_ADMIN_PASSWORD,
      };
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
    throw new Error('Admin credentials not configured');
  }
  
  await pb.collection('_superusers').authWithPassword(
    adminCredentials.email,
    adminCredentials.password
  );
  adminAuthenticated = true;
}

let schemaUpdated = false;

async function ensureKeyFieldExists(): Promise<void> {
  if (schemaUpdated) return;
  
  const pb = getPocketBase();
  await ensureAdminAuth();
  
  try {
    const collection = await pb.collections.getOne('api_keys');
    const hasKeyField = collection.fields?.some((f: { name: string }) => f.name === 'key');
    
    if (!hasKeyField) {
      const fields = [...(collection.fields || [])];
      fields.push({
        id: 'text_key_full',
        name: 'key',
        type: 'text',
        system: false,
        hidden: false,
        presentable: false,
        required: false,
      } as never);
      await pb.collections.update('api_keys', { fields });
    }
    schemaUpdated = true;
  } catch {
    schemaUpdated = true;
  }
}

export const users = {
  async getById(id: string): Promise<User> {
    await ensureAdminAuth();
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

  async create(data: { email: string; password: string; name: string }): Promise<User> {
    return getPocketBase().collection('users').create<User>({
      ...data,
      plan: 'free',
      monthlyLimit: 100,
      verified: false,
      passwordConfirm: data.password,
    });
  },

  async update(id: string, data: Partial<User>): Promise<User> {
    return getPocketBase().collection('users').update<User>(id, data);
  },

  async authenticate(email: string, password: string) {
    return getPocketBase().collection('users').authWithPassword(email, password);
  },
};

export const apiKeys = {
  async create(data: Partial<ApiKey>): Promise<ApiKey> {
    await ensureKeyFieldExists();
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
      headers: { Authorization: pb.authStore.token },
    });
    
    if (!response.ok) {
      throw new Error('Failed to list API keys');
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

  async getByUserAndDateRange(userId: string, startDate: string, endDate: string): Promise<UsageDaily[]> {
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
