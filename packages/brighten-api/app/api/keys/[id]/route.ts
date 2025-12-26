import { NextRequest, NextResponse } from 'next/server';
import { getSession } from '@/lib/auth';
import { apiKeys } from '@/lib/pocketbase';

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getSession();
  if (!session) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }
  
  const { id } = await params;
  const { name, status } = await request.json();
  
  const key = await apiKeys.getById(id);
  if (key.user !== session.user.id) {
    return NextResponse.json({ error: 'Not found' }, { status: 404 });
  }
  
  const updated = await apiKeys.update(id, { name, status });
  return NextResponse.json(updated);
}

export async function DELETE(
  _request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getSession();
  if (!session) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }
  
  const { id } = await params;
  const key = await apiKeys.getById(id);
  if (key.user !== session.user.id) {
    return NextResponse.json({ error: 'Not found' }, { status: 404 });
  }
  
  await apiKeys.delete(id);
  return NextResponse.json({ success: true });
}
