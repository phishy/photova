import { NextRequest, NextResponse } from 'next/server';
import { validateApiKey } from '@/lib/auth';
import { executeReplicate, SUPPORTED_OPERATIONS, type OperationType } from '@/lib/providers';
import { usageLogs, usageDaily } from '@/lib/pocketbase';

export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ operation: string }> }
) {
  const startTime = Date.now();
  const { operation } = await params;
  
  if (!SUPPORTED_OPERATIONS.includes(operation as OperationType)) {
    return NextResponse.json({ error: `Unknown operation: ${operation}` }, { status: 400 });
  }
  
  const authHeader = request.headers.get('authorization') || request.headers.get('x-api-key');
  const auth = await validateApiKey(authHeader);
  
  if (!auth) {
    return NextResponse.json({ error: 'Invalid or missing API key' }, { status: 401 });
  }
  
  try {
    const body = await request.json();
    const { image, options } = body;
    
    if (!image) {
      return NextResponse.json({ error: 'Image required' }, { status: 400 });
    }
    
    const base64Match = image.match(/^data:([^;]+);base64,(.+)$/);
    if (!base64Match) {
      return NextResponse.json({ error: 'Invalid image format. Expected base64 data URI.' }, { status: 400 });
    }
    
    const mimeType = base64Match[1];
    const imageBuffer = Buffer.from(base64Match[2], 'base64');
    
    const result = await executeReplicate(operation as OperationType, {
      image: imageBuffer,
      mimeType,
      options,
    });
    
    const latencyMs = Date.now() - startTime;
    
    logUsage(auth.user.id, auth.apiKey.id, operation, 'success', latencyMs, auth.requestId);
    
    const base64Result = result.image.toString('base64');
    return NextResponse.json({
      image: `data:${result.mimeType};base64,${base64Result}`,
      metadata: result.metadata,
    });
  } catch (error) {
    const latencyMs = Date.now() - startTime;
    const message = error instanceof Error ? error.message : 'Unknown error';
    
    logUsage(auth.user.id, auth.apiKey.id, operation, 'error', latencyMs, auth.requestId, message);
    
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

function logUsage(
  userId: string,
  apiKeyId: string,
  operation: string,
  status: 'success' | 'error',
  latencyMs: number,
  requestId: string,
  errorMessage?: string
) {
  console.log(`[Usage] ${operation} ${status} user=${userId} key=${apiKeyId} latency=${latencyMs}ms`);
  
  usageLogs.create({
    user: userId,
    apiKey: apiKeyId,
    operation,
    status,
    latencyMs,
    requestId,
    errorMessage,
  }).catch(err => {
    console.error('[Usage] Failed to create usage_log:', err instanceof Error ? err.message : err);
  });

  const today = new Date().toISOString().split('T')[0];
  usageDaily.upsert(userId, today, operation, {
    requestCount: 1,
    errorCount: status === 'error' ? 1 : 0,
    totalLatencyMs: latencyMs,
  }).catch(err => {
    console.error('[Usage] Failed to upsert usage_daily:', err instanceof Error ? err.message : err);
  });
}
