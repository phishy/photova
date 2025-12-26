import { usageLogs, usageDaily } from '../auth/client.js';
import type { UsageLog } from '../auth/types.js';

export interface LogUsageParams {
  userId: string;
  apiKeyId: string;
  operation: string;
  status: 'success' | 'error';
  latencyMs: number;
  requestId: string;
  errorMessage?: string;
  metadata?: Record<string, unknown>;
}

export async function logUsage(params: LogUsageParams): Promise<UsageLog> {
  const { userId, apiKeyId, operation, status, latencyMs, requestId, errorMessage, metadata } = params;

  const log = await usageLogs.create({
    user: userId,
    apiKey: apiKeyId,
    operation,
    status,
    latencyMs,
    requestId,
    errorMessage,
    metadata,
  });

  const today = new Date().toISOString().split('T')[0];
  usageDaily
    .upsert(userId, today, operation, {
      requestCount: 1,
      errorCount: status === 'error' ? 1 : 0,
      totalLatencyMs: latencyMs,
    })
    .catch((err) => {
      console.error('Failed to update daily aggregate:', err);
    });

  return log;
}

export interface UsageSummary {
  period: { start: string; end: string };
  totalRequests: number;
  totalErrors: number;
  avgLatencyMs: number;
  byOperation: Record<string, { requests: number; errors: number; avgLatencyMs: number }>;
  byDay: Array<{ date: string; requests: number; errors: number }>;
}

export async function getUsageSummary(
  userId: string,
  startDate: string,
  endDate: string
): Promise<UsageSummary> {
  const dailyRecords = await usageDaily.getByUserAndDateRange(userId, startDate, endDate);

  const byOperation: Record<string, { requests: number; errors: number; totalLatency: number }> = {};
  const byDayMap: Record<string, { requests: number; errors: number }> = {};

  let totalRequests = 0;
  let totalErrors = 0;
  let totalLatency = 0;

  for (const record of dailyRecords) {
    if (!byOperation[record.operation]) {
      byOperation[record.operation] = { requests: 0, errors: 0, totalLatency: 0 };
    }
    byOperation[record.operation].requests += record.requestCount;
    byOperation[record.operation].errors += record.errorCount;
    byOperation[record.operation].totalLatency += record.totalLatencyMs;

    if (!byDayMap[record.date]) {
      byDayMap[record.date] = { requests: 0, errors: 0 };
    }
    byDayMap[record.date].requests += record.requestCount;
    byDayMap[record.date].errors += record.errorCount;

    totalRequests += record.requestCount;
    totalErrors += record.errorCount;
    totalLatency += record.totalLatencyMs;
  }

  const byOperationFormatted: Record<string, { requests: number; errors: number; avgLatencyMs: number }> = {};
  for (const [op, stats] of Object.entries(byOperation)) {
    byOperationFormatted[op] = {
      requests: stats.requests,
      errors: stats.errors,
      avgLatencyMs: stats.requests > 0 ? Math.round(stats.totalLatency / stats.requests) : 0,
    };
  }

  const byDay = Object.entries(byDayMap)
    .map(([date, stats]) => ({ date, ...stats }))
    .sort((a, b) => a.date.localeCompare(b.date));

  return {
    period: { start: startDate, end: endDate },
    totalRequests,
    totalErrors,
    avgLatencyMs: totalRequests > 0 ? Math.round(totalLatency / totalRequests) : 0,
    byOperation: byOperationFormatted,
    byDay,
  };
}

export async function getCurrentMonthUsage(userId: string): Promise<{
  used: number;
  resetsAt: string;
}> {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth() + 1;

  const used = await usageDaily.getMonthlyTotal(userId, year, month);
  const nextMonth = new Date(year, month, 1);
  const resetsAt = nextMonth.toISOString();

  return {
    used,
    resetsAt,
  };
}

export interface TimeSeriesPoint {
  timestamp: string;
  value: number;
}

export async function getTimeSeries(
  userId: string,
  startDate: string,
  endDate: string,
  metric: 'requests' | 'errors' | 'latency' = 'requests'
): Promise<TimeSeriesPoint[]> {
  const dailyRecords = await usageDaily.getByUserAndDateRange(userId, startDate, endDate);

  const byDay: Record<string, number> = {};

  for (const record of dailyRecords) {
    if (!byDay[record.date]) {
      byDay[record.date] = 0;
    }

    switch (metric) {
      case 'requests':
        byDay[record.date] += record.requestCount;
        break;
      case 'errors':
        byDay[record.date] += record.errorCount;
        break;
      case 'latency':
        if (record.requestCount > 0) {
          const avgLatency = record.totalLatencyMs / record.requestCount;
          byDay[record.date] = (byDay[record.date] + avgLatency) / 2 || avgLatency;
        }
        break;
    }
  }

  const result: TimeSeriesPoint[] = [];
  const start = new Date(startDate);
  const end = new Date(endDate);

  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    const dateStr = d.toISOString().split('T')[0];
    result.push({
      timestamp: dateStr,
      value: byDay[dateStr] || 0,
    });
  }

  return result;
}
