import { NextRequest, NextResponse } from 'next/server';
import { getSession, generateApiKey } from '@/lib/auth';
import { apiKeys } from '@/lib/pocketbase';

export async function GET() {
  const session = await getSession();
  if (!session) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }
  
  const keys = await apiKeys.listByUser(session.user.id);
  return NextResponse.json({ keys });
}

export async function POST(request: NextRequest) {
  const session = await getSession();
  if (!session) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }
  
  const { name } = await request.json();
  const { key, hash, prefix } = generateApiKey();
  
  const apiKey = await apiKeys.create({
    user: session.user.id,
    name: name || 'API Key',
    key: key,
    keyHash: hash,
    keyPrefix: prefix,
    status: 'active',
    scopes: ['*'],
  });
  
  return NextResponse.json({ key, id: apiKey.id, name: apiKey.name });
}
