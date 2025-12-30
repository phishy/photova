<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAnalytic;
use App\Models\Share;
use App\Models\ShareAnalytic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $days = (int) $request->query('days', 30);
        $days = max(1, min(365, $days));
        $since = now()->subDays($days);

        $stats = [
            'users' => [
                'total' => User::count(),
                'new' => User::where('created_at', '>=', $since)->count(),
            ],
            'assets' => [
                'total' => Asset::count(),
                'new' => Asset::where('created_at', '>=', $since)->count(),
                'totalSize' => Asset::sum('size'),
            ],
            'shares' => [
                'total' => Share::count(),
                'active' => Share::where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
                'new' => Share::where('created_at', '>=', $since)->count(),
            ],
        ];

        $shareAnalytics = ShareAnalytic::where('created_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        $assetAnalytics = AssetAnalytic::where('created_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        $stats['analytics'] = [
            'shareViews' => $shareAnalytics[ShareAnalytic::EVENT_VIEW] ?? 0,
            'shareDownloads' => $shareAnalytics[ShareAnalytic::EVENT_DOWNLOAD] ?? 0,
            'shareZipDownloads' => $shareAnalytics[ShareAnalytic::EVENT_ZIP_DOWNLOAD] ?? 0,
            'assetViews' => $assetAnalytics[AssetAnalytic::EVENT_VIEW] ?? 0,
            'assetDownloads' => $assetAnalytics[AssetAnalytic::EVENT_DOWNLOAD] ?? 0,
        ];

        return response()->json($stats);
    }

    public function timeseries(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $days = (int) $request->query('days', 30);
        $days = max(1, min(365, $days));
        $since = now()->subDays($days);

        $shareTimeseries = ShareAnalytic::where('created_at', '>=', $since)
            ->selectRaw("DATE(created_at) as date, event_type, COUNT(*) as count")
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($group) => [
                'date' => $group->first()->date,
                'views' => $group->firstWhere('event_type', ShareAnalytic::EVENT_VIEW)?->count ?? 0,
                'downloads' => $group->firstWhere('event_type', ShareAnalytic::EVENT_DOWNLOAD)?->count ?? 0,
                'zipDownloads' => $group->firstWhere('event_type', ShareAnalytic::EVENT_ZIP_DOWNLOAD)?->count ?? 0,
            ])
            ->values();

        $userSignups = User::where('created_at', '>=', $since)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $assetUploads = Asset::where('created_at', '>=', $since)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return response()->json([
            'shareAnalytics' => $shareTimeseries,
            'userSignups' => $userSignups,
            'assetUploads' => $assetUploads,
        ]);
    }

    public function topUsers(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $days = (int) $request->query('days', 30);
        $since = now()->subDays($days);

        $topByAssets = User::withCount('assets')
            ->orderByDesc('assets_count')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'assetCount' => $user->assets_count,
            ]);

        $topByShares = User::withCount(['shares' => function ($q) use ($since) {
                $q->where('created_at', '>=', $since);
            }])
            ->orderByDesc('shares_count')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'shareCount' => $user->shares_count,
            ]);

        $topByViews = DB::table('shares')
            ->join('users', 'shares.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', DB::raw('SUM(shares.view_count) as total_views'))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_views')
            ->limit(10)
            ->get();

        return response()->json([
            'byAssets' => $topByAssets,
            'byShares' => $topByShares,
            'byViews' => $topByViews,
        ]);
    }

    public function topAssets(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $topByViews = Asset::with('user:id,name,email')
            ->orderByDesc('view_count')
            ->limit(20)
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'filename' => $asset->filename,
                'viewCount' => $asset->view_count,
                'downloadCount' => $asset->download_count,
                'user' => $asset->user ? [
                    'id' => $asset->user->id,
                    'name' => $asset->user->name,
                ] : null,
            ]);

        $topByDownloads = Asset::with('user:id,name,email')
            ->orderByDesc('download_count')
            ->limit(20)
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'filename' => $asset->filename,
                'viewCount' => $asset->view_count,
                'downloadCount' => $asset->download_count,
                'user' => $asset->user ? [
                    'id' => $asset->user->id,
                    'name' => $asset->user->name,
                ] : null,
            ]);

        return response()->json([
            'byViews' => $topByViews,
            'byDownloads' => $topByDownloads,
        ]);
    }

    public function topShares(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $topShares = Share::with('user:id,name,email')
            ->orderByDesc('view_count')
            ->limit(20)
            ->get()
            ->map(fn ($share) => [
                'id' => $share->id,
                'name' => $share->name,
                'slug' => $share->slug,
                'viewCount' => $share->view_count,
                'assetCount' => count($share->asset_ids),
                'isExpired' => $share->isExpired(),
                'analytics' => $share->getAnalyticsSummary(),
                'user' => $share->user ? [
                    'id' => $share->user->id,
                    'name' => $share->user->name,
                ] : null,
                'created' => $share->created_at->toIso8601String(),
            ]);

        return response()->json(['shares' => $topShares]);
    }
}
