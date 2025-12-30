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
        $days = $request->query('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $monthlyUsage = UsageDaily::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('SUM(request_count) as total_requests')
            ->selectRaw('SUM(error_count) as total_errors')
            ->selectRaw('SUM(total_latency_ms) as total_latency')
            ->selectRaw('SUM(total_cost) as total_cost')
            ->selectRaw('SUM(total_price) as total_revenue')
            ->first();

        $byOperation = UsageDaily::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('operation, source, SUM(request_count) as requests, SUM(error_count) as errors, SUM(total_latency_ms) as total_latency, SUM(total_cost) as cost, SUM(total_price) as revenue')
            ->groupBy('operation', 'source')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->operation . ($row->source ? ':' . $row->source : '') => [
                    'operation' => $row->operation,
                    'source' => $row->source,
                    'requests' => (int) $row->requests,
                    'errors' => (int) $row->errors,
                    'avgLatencyMs' => $row->requests > 0 ? round($row->total_latency / $row->requests) : 0,
                    'cost' => (float) ($row->cost ?? 0),
                    'revenue' => (float) ($row->revenue ?? 0),
                ],
            ]);

        $totalCost = (float) ($monthlyUsage->total_cost ?? 0);
        $totalRevenue = (float) ($monthlyUsage->total_revenue ?? 0);

        return response()->json([
            'summary' => [
                'totalRequests' => (int) ($monthlyUsage->total_requests ?? 0),
                'totalErrors' => (int) ($monthlyUsage->total_errors ?? 0),
                'averageLatencyMs' => $monthlyUsage->total_requests > 0
                    ? round($monthlyUsage->total_latency / $monthlyUsage->total_requests)
                    : 0,
                'monthlyLimit' => $user->monthly_limit,
                'totalCost' => $totalCost,
                'totalRevenue' => $totalRevenue,
                'totalMargin' => $totalRevenue - $totalCost,
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
                'cost' => (float) $rows->sum('total_cost'),
                'revenue' => (float) $rows->sum('total_price'),
            ]);

        $timeseries = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $timeseries[] = [
                'date' => $date,
                'requests' => $data[$date]['requests'] ?? 0,
                'errors' => $data[$date]['errors'] ?? 0,
                'cost' => $data[$date]['cost'] ?? 0,
                'revenue' => $data[$date]['revenue'] ?? 0,
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
            ->selectRaw('SUM(total_price) as total_price')
            ->first();

        $totalRequests = (int) ($usage->total ?? 0);
        $totalPrice = (float) ($usage->total_price ?? 0);

        return response()->json([
            'current' => [
                'used' => $totalRequests,
                'limit' => $user->monthly_limit,
                'remaining' => max(0, $user->monthly_limit - $totalRequests),
                'totalPrice' => $totalPrice,
                'period' => [
                    'start' => $startOfMonth->toIso8601String(),
                    'end' => Carbon::now()->endOfMonth()->toIso8601String(),
                ],
            ],
        ]);
    }
}
