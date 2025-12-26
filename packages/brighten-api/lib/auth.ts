import { cookies } from 'next/headers';
import { getPocketBase, users, apiKeys } from './pocketbase';
import { API_KEY_PREFIX } from './pocketbase/types';
import { createHash } from 'crypto';
import { nanoid } from 'nanoid';

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

export async function getSession() {
  const cookieStore = await cookies();
  const token = cookieStore.get('token')?.value;
  
  if (!token) return null;
  
  try {
    const payload = decodeJWT(token);
    if (!payload?.id) return null;
    
    if (payload.exp && payload.exp * 1000 < Date.now()) return null;
    
    const user = await users.getById(payload.id);
    return { user, token };
  } catch {
    return null;
  }
}

export async function validateApiKey(authHeader: string | null) {
  if (!authHeader) return null;
  
  const key = authHeader.startsWith('Bearer ') 
    ? authHeader.slice(7) 
    : authHeader;
    
  if (!key.startsWith(API_KEY_PREFIX)) return null;
  
  const keyHash = createHash('sha256').update(key).digest('hex');
  const apiKey = await apiKeys.getByHash(keyHash);
  
  if (!apiKey || apiKey.status !== 'active') return null;
  
  const user = await users.getById(apiKey.user);
  
  await apiKeys.update(apiKey.id, { lastUsedAt: new Date().toISOString() });
  
  return { user, apiKey, requestId: nanoid(12) };
}

export function generateApiKey(): { key: string; hash: string; prefix: string } {
  const key = API_KEY_PREFIX + nanoid(32);
  const hash = createHash('sha256').update(key).digest('hex');
  const prefix = key.slice(0, 16) + '...';
  return { key, hash, prefix };
}
