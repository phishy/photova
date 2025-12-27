<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsageDaily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UsageController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $startOfMonth = Carbon::now()->startOfMonth();

        $monthlyUsage = UsageDaily::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('SUM(request_count) as total_requests')
            ->selectRaw('SUM(error_count) as total_errors')
            ->selectRaw('SUM(total_latency_ms) as total_latency')
            ->first();

        $byOperation = UsageDaily::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('operation, SUM(request_count) as requests, SUM(error_count) as errors, SUM(total_latency_ms) as total_latency')
            ->groupBy('operation')
            ->get()
            ->keyBy('operation')
            ->map(fn ($row) => [
                'requests' => (int) $row->requests,
                'errors' => (int) $row->errors,
                'avgLatencyMs' => $row->requests > 0 ? round($row->total_latency / $row->requests) : 0,
            ]);

        return response()->json([
            'summary' => [
                'totalRequests' => (int) ($monthlyUsage->total_requests ?? 0),
                'totalErrors' => (int) ($monthlyUsage->total_errors ?? 0),
                'averageLatencyMs' => $monthlyUsage->total_requests > 0
                    ? round($monthlyUsage->total_latency / $monthlyUsage->total_requests)
                    : 0,
                'monthlyLimit' => $user->monthly_limit,
                'byOperation' => $byOperation,
            ],
        ]);
    }

    public function timeseries(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = $request->query('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $data = UsageDaily::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($row) => $row->date->format('Y-m-d'))
            ->map(fn ($rows) => [
                'requests' => $rows->sum('request_count'),
                'errors' => $rows->sum('error_count'),
            ]);

        $timeseries = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $timeseries[] = [
                'date' => $date,
                'requests' => $data[$date]['requests'] ?? 0,
                'errors' => $data[$date]['errors'] ?? 0,
            ];
        }

        return response()->json(['timeseries' => $timeseries]);
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $startOfMonth = Carbon::now()->startOfMonth();

        $usage = UsageDaily::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('SUM(request_count) as total')
            ->value('total') ?? 0;

        return response()->json([
            'current' => [
                'used' => (int) $usage,
                'limit' => $user->monthly_limit,
                'remaining' => max(0, $user->monthly_limit - $usage),
                'period' => [
                    'start' => $startOfMonth->toIso8601String(),
                    'end' => Carbon::now()->endOfMonth()->toIso8601String(),
                ],
            ],
        ]);
    }
}
