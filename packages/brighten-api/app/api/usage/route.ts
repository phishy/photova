import { NextRequest, NextResponse } from 'next/server';
import { getSession } from '@/lib/auth';
import { usageDaily } from '@/lib/pocketbase';

export async function GET(request: NextRequest) {
  const session = await getSession();
  if (!session) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }
  
  const { searchParams } = new URL(request.url);
  const days = parseInt(searchParams.get('days') || '30');
  
  const endDate = new Date();
  const startDate = new Date();
  startDate.setDate(startDate.getDate() - days);
  
  const records = await usageDaily.getByUserAndDateRange(
    session.user.id,
    startDate.toISOString().split('T')[0],
    endDate.toISOString().split('T')[0]
  );
  
  const byOperation: Record<string, { requests: number; errors: number; totalLatency: number }> = {};
  const byDay: Record<string, { requests: number; errors: number }> = {};
  let totalRequests = 0;
  let totalErrors = 0;
  let totalLatency = 0;

  for (const record of records) {
    if (!byOperation[record.operation]) {
      byOperation[record.operation] = { requests: 0, errors: 0, totalLatency: 0 };
    }
    byOperation[record.operation].requests += record.requestCount;
    byOperation[record.operation].errors += record.errorCount;
    byOperation[record.operation].totalLatency += record.totalLatencyMs;

    if (!byDay[record.date]) {
      byDay[record.date] = { requests: 0, errors: 0 };
    }
    byDay[record.date].requests += record.requestCount;
    byDay[record.date].errors += record.errorCount;

    totalRequests += record.requestCount;
    totalErrors += record.errorCount;
    totalLatency += record.totalLatencyMs;
  }

  return NextResponse.json({
    period: { 
      start: startDate.toISOString().split('T')[0], 
      end: endDate.toISOString().split('T')[0] 
    },
    totalRequests,
    totalErrors,
    avgLatencyMs: totalRequests > 0 ? Math.round(totalLatency / totalRequests) : 0,
    byOperation: Object.fromEntries(
      Object.entries(byOperation).map(([op, stats]) => [
        op,
        {
          requests: stats.requests,
          errors: stats.errors,
          avgLatencyMs: stats.requests > 0 ? Math.round(stats.totalLatency / stats.requests) : 0,
        },
      ])
    ),
    byDay: Object.entries(byDay)
      .map(([date, stats]) => ({ date, ...stats }))
      .sort((a, b) => a.date.localeCompare(b.date)),
  });
}
