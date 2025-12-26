import { NextResponse } from 'next/server';
import { cookies } from 'next/headers';
import { users, apiKeys, getPocketBase } from '@/lib/pocketbase';
import { getSession } from '@/lib/auth';

export const dynamic = 'force-dynamic';

function decodeJWT(token: string): { id?: string; exp?: number } | null {
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    const payload = JSON.parse(Buffer.from(parts[1], 'base64').toString());
    return payload;
  } catch {
    return null;
  }
}

export async function GET() {
  const debug: Record<string, unknown> = {};
  
  try {
    const cookieStore = await cookies();
    const token = cookieStore.get('token')?.value;
    debug.hasToken = !!token;
    debug.tokenPreview = token ? token.substring(0, 50) + '...' : null;
    
    if (token) {
      const payload = decodeJWT(token);
      debug.payload = payload;
      debug.isExpired = payload?.exp ? payload.exp * 1000 < Date.now() : 'no exp';
      
      if (payload?.id) {
        try {
          const user = await users.getById(payload.id);
          debug.userFound = true;
          debug.userEmail = user.email;
          debug.userId = user.id;
        } catch (err) {
          debug.userFound = false;
          debug.userError = err instanceof Error ? err.message : String(err);
        }
      }
    }
    
    const session = await getSession();
    debug.sessionHelper = session ? { userId: session.user.id, email: session.user.email } : null;
    
    debug.envCheck = {
      hasPocketBaseUrl: !!process.env.POCKETBASE_URL,
      pocketBaseUrl: process.env.POCKETBASE_URL || 'http://127.0.0.1:8090',
      hasAdminEmail: !!process.env.POCKETBASE_ADMIN_EMAIL,
      hasAdminPassword: !!process.env.POCKETBASE_ADMIN_PASSWORD,
    };
    
    try {
      const pb = getPocketBase();
      const health = await fetch(`${pb.baseURL}/api/health`);
      debug.pocketbaseHealth = health.ok ? 'ok' : `error: ${health.status}`;
    } catch (err) {
      debug.pocketbaseHealth = err instanceof Error ? err.message : String(err);
    }
    
    try {
      const pb = getPocketBase();
      const adminEmail = process.env.POCKETBASE_ADMIN_EMAIL;
      const adminPassword = process.env.POCKETBASE_ADMIN_PASSWORD;
      
      if (adminEmail && adminPassword) {
        await pb.collection('_superusers').authWithPassword(adminEmail, adminPassword);
        debug.adminAuth = 'success';
        
        const collections = await pb.collections.getFullList();
        debug.collections = collections.map(c => c.name);
        
        try {
          const logs = await pb.collection('usage_logs').getList(1, 10, { sort: '-created' });
          debug.usageLogs = {
            totalItems: logs.totalItems,
            recentLogs: logs.items.map(l => ({
              id: l.id,
              operation: l.operation,
              status: l.status,
              created: l.created,
            })),
          };
        } catch (err) {
          debug.usageLogsError = err instanceof Error ? err.message : String(err);
        }
        
        try {
          const daily = await pb.collection('usage_daily').getList(1, 10, { sort: '-date' });
          debug.usageDaily = {
            totalItems: daily.totalItems,
            recentDays: daily.items.map(d => ({
              id: d.id,
              date: d.date,
              operation: d.operation,
              requestCount: d.requestCount,
            })),
          };
        } catch (err) {
          debug.usageDailyError = err instanceof Error ? err.message : String(err);
        }
        
        if (session) {
          try {
            const keys = await apiKeys.listByUser(session.user.id);
            debug.userApiKeys = keys.map(k => ({
              id: k.id,
              name: k.name,
              keyPrefix: k.keyPrefix,
              hasFullKey: !!k.key,
              status: k.status,
              lastUsedAt: k.lastUsedAt,
            }));
          } catch (err) {
            debug.userApiKeysError = err instanceof Error ? err.message : String(err);
          }
        }
      } else {
        debug.adminAuth = 'missing credentials';
      }
    } catch (err) {
      debug.adminAuth = 'failed';
      debug.adminAuthError = err instanceof Error ? err.message : String(err);
    }
    
  } catch (err) {
    debug.error = err instanceof Error ? err.message : String(err);
  }
  
  return NextResponse.json(debug);
}
