import { NextResponse } from 'next/server';
import { getSession } from '@/lib/auth';
import { usageDaily } from '@/lib/pocketbase';

export const dynamic = 'force-dynamic';

export async function GET() {
  const session = await getSession();
  
  if (!session) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }
  
  const now = new Date();
  const used = await usageDaily.getMonthlyTotal(session.user.id, now.getFullYear(), now.getMonth() + 1);
  
  return NextResponse.json({
    id: session.user.id,
    email: session.user.email,
    name: session.user.name,
    plan: session.user.plan,
    usage: { used },
  });
}
