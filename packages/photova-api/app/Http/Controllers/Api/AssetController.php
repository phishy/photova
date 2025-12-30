<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeAsset;
use App\Models\Asset;
use App\Models\AssetAnalytic;
use App\Models\StorageBucket;
use App\Services\ExifService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function __construct(
        private StorageService $storage,
        private ExifService $exif
    ) {}

    public function insights(Request $request): JsonResponse
    {
        $assets = $request->user()->assets()->get();

        $totalAssets = $assets->count();
        $analyzedAssets = $assets->filter(fn($a) => !empty($a->metadata['caption']))->count();
        $totalSize = $assets->sum('size');

        $wordCounts = [];
        $stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'it', 'its', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'we', 'they', 'what', 'which', 'who', 'whom', 'whose', 'where', 'when', 'why', 'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just', 'can', 'into', 'from', 'up', 'down', 'out', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'any', 'about', 'as', 'by'];

        foreach ($assets as $asset) {
            $caption = $asset->metadata['caption'] ?? '';
            if (!$caption) continue;

            $words = preg_split('/[\s,.\-!?;:()"\'\[\]]+/', strtolower($caption));
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) < 2 || in_array($word, $stopWords)) continue;
                $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
            }
        }

        arsort($wordCounts);
        $topWords = array_slice($wordCounts, 0, 50, true);

        $mimeTypes = $assets->groupBy('mime_type')->map->count()->toArray();

        $recentlyAnalyzed = $assets
            ->filter(fn($a) => !empty($a->metadata['analyzed_at']))
            ->sortByDesc(fn($a) => $a->metadata['analyzed_at'])
            ->take(10)
            ->map(fn($a) => [
                'id' => $a->id,
                'filename' => $a->filename,
                'caption' => $a->metadata['caption'] ?? null,
                'analyzedAt' => $a->metadata['analyzed_at'] ?? null,
            ])
            ->values();

        return response()->json([
            'stats' => [
                'totalAssets' => $totalAssets,
                'analyzedAssets' => $analyzedAssets,
                'analyzedPercent' => $totalAssets > 0 ? round(($analyzedAssets / $totalAssets) * 100) : 0,
                'totalSize' => $totalSize,
            ],
            'wordCloud' => $topWords,
            'mimeTypes' => $mimeTypes,
            'recentlyAnalyzed' => $recentlyAnalyzed,
        ]);
    }

    public function geo(Request $request): JsonResponse
    {
        $assets = $request->user()->assets()
            ->whereRaw("metadata->'exif'->'location'->>'lat' IS NOT NULL")
            ->whereRaw("metadata->'exif'->'location'->>'lng' IS NOT NULL")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'filename' => $asset->filename,
                'lat' => (float) $asset->metadata['exif']['location']['lat'],
                'lng' => (float) $asset->metadata['exif']['location']['lng'],
                'altitude' => $asset->metadata['exif']['location']['altitude'] ?? null,
                'takenAt' => $asset->metadata['exif']['datetime']['original'] ?? null,
                'camera' => $asset->metadata['exif']['camera']['model'] ?? null,
            ]);

        $north = $request->query('north');
        $south = $request->query('south');
        $east = $request->query('east');
        $west = $request->query('west');

        if ($north !== null && $south !== null && $east !== null && $west !== null) {
            $north = (float) $north;
            $south = (float) $south;
            $east = (float) $east;
            $west = (float) $west;

            $assets = $assets->filter(function ($asset) use ($north, $south, $east, $west) {
                $lat = $asset['lat'];
                $lng = $asset['lng'];

                if ($lat < $south || $lat > $north) {
                    return false;
                }

                if ($west <= $east) {
                    return $lng >= $west && $lng <= $east;
                } else {
                    return $lng >= $west || $lng <= $east;
                }
            })->values();
        }

        $bounds = null;
        if ($assets->isNotEmpty()) {
            $lats = $assets->pluck('lat');
            $lngs = $assets->pluck('lng');
            $bounds = [
                'north' => $lats->max(),
                'south' => $lats->min(),
                'east' => $lngs->max(),
                'west' => $lngs->min(),
            ];
        }

        return response()->json([
            'assets' => $assets,
            'bounds' => $bounds,
            'count' => $assets->count(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $storageBucketId = $request->query('storage_bucket_id');
        $folderId = $request->query('folder_id');
        $search = $request->query('search');
        $tagIds = $request->query('tags');
        $mimeType = $request->query('mime_type');

        $query = $request->user()->assets()->with('tags');

        // Filter by storage bucket
        if ($storageBucketId === 'system') {
            $query->whereNull('storage_bucket_id');
        } elseif ($storageBucketId) {
            $query->where('storage_bucket_id', $storageBucketId);
        }

        // When searching or filtering by mime type, ignore folder context (flatten results)
        if (!$search && !$mimeType) {
            if ($folderId === 'root' || $folderId === '') {
                $query->whereNull('folder_id');
            } elseif ($folderId) {
                $query->where('folder_id', $folderId);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'ilike', '%' . $search . '%')
                  ->orWhereRaw("metadata::text ilike ?", ['%' . $search . '%']);
            });
        }

        if ($mimeType) {
            $query->where('mime_type', $mimeType);
        }

        if ($tagIds) {
            $tagIdArray = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
            $query->whereHas('tags', function ($q) use ($tagIdArray) {
                $q->whereIn('tags.id', $tagIdArray);
            });
        }

        $assets = $query->orderByDesc('created_at')
            ->get()
            ->map(fn ($asset) => $this->formatAsset($asset));

        return response()->json(['assets' => $assets]);
    }

    public function store(Request $request): JsonResponse
    {
        // Resolve storage bucket - use specific bucket, user's default, or system
        $bucket = null;
        $storageBucketId = $request->input('storage_bucket_id');
        
        if ($storageBucketId && $storageBucketId !== 'system') {
            $bucket = $request->user()->storageBuckets()->find($storageBucketId);
            if (!$bucket) {
                return response()->json(['error' => 'Storage bucket not found'], 404);
            }
            if (!$bucket->is_active) {
                return response()->json(['error' => 'Storage bucket is not active'], 422);
            }
        } elseif ($storageBucketId !== 'system') {
            // Use user's default bucket if set
            $bucket = $request->user()->getDefaultStorageBucket();
        }

        // Check for PHP upload errors (file too large, etc.)
        if ($request->hasFile('file') && !$request->file('file')->isValid()) {
            $error = $request->file('file')->getError();
            $message = match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large. Maximum upload size is ' . ini_get('upload_max_filesize'),
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temp directory',
                UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension',
                default => 'Upload failed with error code ' . $error,
            };
            return response()->json(['error' => $message], 413);
        }

        $content = null;
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $content = file_get_contents($file->getRealPath());
            
            try {
                $result = $this->storage->store($request->user(), $file, $filename, $bucket);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to store file: ' . $e->getMessage()], 500);
            }
        } elseif ($request->has('image')) {
            $imageData = $request->input('image');
            
            if (preg_match('/^data:([^;]+);base64,(.+)$/', $imageData, $matches)) {
                $mimeType = $matches[1];
                $content = base64_decode($matches[2]);
            } else {
                $content = base64_decode($imageData);
                $mimeType = 'image/png';
            }

            $extension = $this->getExtensionFromMime($mimeType);
            $filename = $request->input('filename', 'upload.' . $extension);
            
            try {
                $result = $this->storage->store($request->user(), $content, $filename, $bucket);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to store file: ' . $e->getMessage()], 500);
            }
        } else {
            // Check if request body was likely dropped due to exceeding post_max_size
            $contentLength = $request->header('Content-Length');
            $postMaxSize = $this->parseSize(ini_get('post_max_size'));
            
            if ($contentLength && (int)$contentLength > $postMaxSize) {
                return response()->json([
                    'error' => 'File too large. Maximum upload size is ' . ini_get('post_max_size')
                ], 413);
            }
            
            return response()->json(['error' => 'No file or image provided'], 400);
        }

        $folderId = $request->input('folder_id');
        if ($folderId) {
            $folder = $request->user()->folders()->find($folderId);
            if (!$folder) {
                $folderId = null;
            }
        }

        $metadata = $request->input('metadata', []);
        
        if ($content && str_starts_with($mimeType, 'image/')) {
            $exifData = $this->exif->extract($content, $mimeType);
            if (!empty($exifData)) {
                $metadata['exif'] = $exifData;
            }
        }

        $asset = $request->user()->assets()->create([
            'storage_bucket_id' => $result['storage_bucket_id'],
            'folder_id' => $folderId,
            'storage_key' => $result['storage_key'],
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $result['size'],
            'metadata' => $metadata,
        ]);

        if (str_starts_with($mimeType, 'image/') && $this->shouldAutoAnalyze($asset)) {
            AnalyzeAsset::dispatch($asset);
        }

        return response()->json(['asset' => $this->formatAsset($asset)], 201);
    }

    public function show(Request $request, Asset $asset): JsonResponse|StreamedResponse|Response
    {
        $this->authorizeAsset($request, $asset);

        if ($request->boolean('download')) {
            return $this->download($asset);
        }

        if ($request->boolean('raw') || $request->boolean('inline')) {
            return $this->serve($asset);
        }

        return response()->json(['asset' => $this->formatAsset($asset)]);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        if ($request->has('image')) {
            $imageData = $request->input('image');

            if (preg_match('/^data:([^;]+);base64,(.+)$/', $imageData, $matches)) {
                $mimeType = $matches[1];
                $content = base64_decode($matches[2]);
            } else {
                $content = base64_decode($imageData);
                $mimeType = $asset->mime_type;
            }

            $this->storage->delete($asset);
            
            try {
                $result = $this->storage->store(
                    $request->user(),
                    $content,
                    $asset->filename,
                    $asset->storageBucket
                );
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to update file: ' . $e->getMessage()], 500);
            }

            $asset->update([
                'storage_key' => $result['storage_key'],
                'mime_type' => $mimeType,
                'size' => $result['size'],
            ]);

            if (str_starts_with($mimeType, 'image/') && $this->shouldAutoAnalyze($asset)) {
                AnalyzeAsset::dispatch($asset);
            }
        }

        if ($request->has('filename')) {
            $asset->update(['filename' => $request->input('filename')]);
        }

        if ($request->has('metadata')) {
            $existingMetadata = $asset->metadata ?? [];
            $newMetadata = array_merge($existingMetadata, $request->input('metadata'));
            $asset->update(['metadata' => $newMetadata]);
        }

        $asset->refresh();

        return response()->json(['asset' => $this->formatAsset($asset)]);
    }

    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $this->storage->delete($asset);
        $asset->delete();

        return response()->json(['message' => 'Asset deleted']);
    }

    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'uuid|exists:assets,id',
            'folder_id' => 'nullable|uuid',
        ]);

        $folderId = $validated['folder_id'] ?? null;

        if ($folderId) {
            $folder = $request->user()->folders()->find($folderId);
            if (!$folder) {
                return response()->json(['error' => 'Folder not found'], 404);
            }
        }

        $updated = Asset::whereIn('id', $validated['asset_ids'])
            ->where('user_id', $request->user()->id)
            ->update(['folder_id' => $folderId]);

        return response()->json(['moved' => $updated]);
    }

    public function share(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $hours = $request->input('hours', 24);
        $hours = min(max($hours, 1), 168);

        $expiresAt = now()->addHours($hours);
        $url = URL::temporarySignedRoute('assets.public', $expiresAt, ['asset' => $asset->id]);

        return response()->json([
            'url' => $url,
            'expiresAt' => $expiresAt->toIso8601String(),
        ]);
    }

    public function publicDownload(Request $request, Asset $asset): StreamedResponse|Response
    {
        if ($request->boolean('inline') || $request->boolean('raw')) {
            return $this->serve($asset);
        }
        
        return $this->download($asset);
    }

    public function thumbnail(Request $request, Asset $asset): Response
    {
        $this->authorizeAsset($request, $asset);

        $width = (int) $request->query('w', 200);
        $height = (int) $request->query('h', 200);
        $width = max(16, min(1200, $width));
        $height = max(16, min(1200, $height));

        if (!str_starts_with($asset->mime_type, 'image/')) {
            abort(400, 'Asset is not an image');
        }

        // Check cache first
        $cacheKey = $this->getThumbnailCacheKey($asset, $width, $height);
        if (Storage::disk('thumbs')->exists($cacheKey)) {
            $resized = Storage::disk('thumbs')->get($cacheKey);
            return response($resized, 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => strlen($resized),
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'X-Thumbnail-Cache' => 'HIT',
            ]);
        }

        $content = $this->storage->retrieve($asset);

        if ($content === null) {
            abort(404, 'File not found');
        }

        $resized = $this->resizeImage($content, $width, $height, $asset->mime_type);

        // Store in cache
        Storage::disk('thumbs')->put($cacheKey, $resized);

        return response($resized, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($resized),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Thumbnail-Cache' => 'MISS',
        ]);
    }

    private function getThumbnailCacheKey(Asset $asset, int $width, int $height): string
    {
        // Include updated_at timestamp in cache key to auto-invalidate when asset changes
        $timestamp = $asset->updated_at->timestamp;
        return "{$asset->id}/{$width}x{$height}_{$timestamp}.jpg";
    }

    public function clearThumbnailCache(Asset $asset): void
    {
        $cacheDir = $asset->id;
        if (Storage::disk('thumbs')->exists($cacheDir)) {
            Storage::disk('thumbs')->deleteDirectory($cacheDir);
        }
    }

    private function resizeImage(string $content, int $width, int $height, string $mimeType): string
    {
        // Use Imagick for HEIC/HEIF (GD doesn't support these formats)
        if (in_array($mimeType, ['image/heic', 'image/heif']) && extension_loaded('imagick')) {
            return $this->resizeImageWithImagick($content, $width, $height);
        }

        // Try GD first
        $source = @imagecreatefromstring($content);

        // Fall back to Imagick if GD fails (e.g., unsupported format)
        if ($source === false) {
            if (extension_loaded('imagick')) {
                return $this->resizeImageWithImagick($content, $width, $height);
            }
            throw new \RuntimeException('Failed to create image from content');
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $srcRatio = $srcWidth / $srcHeight;
        $dstRatio = $width / $height;

        if ($srcRatio > $dstRatio) {
            $newHeight = $height;
            $newWidth = (int) ($height * $srcRatio);
        } else {
            $newWidth = $width;
            $newHeight = (int) ($width / $srcRatio);
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        $cropX = (int) (($newWidth - $width) / 2);
        $cropY = (int) (($newHeight - $height) / 2);
        $cropped = imagecrop($resized, ['x' => $cropX, 'y' => $cropY, 'width' => $width, 'height' => $height]);

        ob_start();
        imagejpeg($cropped ?: $resized, null, 85);
        $output = ob_get_clean();

        imagedestroy($source);
        imagedestroy($resized);
        if ($cropped) {
            imagedestroy($cropped);
        }

        return $output;
    }

    /**
     * Resize image using ImageMagick (supports HEIC, HEIF, and other formats GD doesn't handle)
     */
    private function resizeImageWithImagick(string $content, int $width, int $height): string
    {
        // Use dynamic instantiation to avoid static analysis errors (imagick is a runtime extension)
        $imagick = new ('Imagick')();
        $imagick->readImageBlob($content);
        
        // Handle orientation from EXIF data
        $imagick->autoOrient();

        $srcWidth = $imagick->getImageWidth();
        $srcHeight = $imagick->getImageHeight();

        $srcRatio = $srcWidth / $srcHeight;
        $dstRatio = $width / $height;

        if ($srcRatio > $dstRatio) {
            $newHeight = $height;
            $newWidth = (int) ($height * $srcRatio);
        } else {
            $newWidth = $width;
            $newHeight = (int) ($width / $srcRatio);
        }

        // Resize (use FILTER_LANCZOS = 22)
        $imagick->resizeImage($newWidth, $newHeight, 22, 1);

        // Crop to exact dimensions (center crop)
        $cropX = (int) (($newWidth - $width) / 2);
        $cropY = (int) (($newHeight - $height) / 2);
        $imagick->cropImage($width, $height, $cropX, $cropY);

        // Convert to JPEG
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(85);
        $imagick->stripImage(); // Remove metadata for smaller file size

        $output = $imagick->getImageBlob();
        $imagick->destroy();

        return $output;
    }

    private function download(Asset $asset): StreamedResponse
    {
        $content = $this->storage->retrieve($asset);
        
        if ($content === null) {
            abort(404, 'File not found');
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $asset->filename, [
            'Content-Type' => $asset->mime_type,
            'Content-Length' => strlen($content),
        ]);
    }

    private function serve(Asset $asset): Response
    {
        $content = $this->storage->retrieve($asset);
        
        if ($content === null) {
            abort(404, 'File not found');
        }

        $mimeType = $asset->mime_type;

        // Convert HEIC/HEIF to JPEG for browser display (browsers don't support HEIC natively)
        if (in_array($mimeType, ['image/heic', 'image/heif']) && extension_loaded('imagick')) {
            $content = $this->convertToJpeg($content);
            $mimeType = 'image/jpeg';
        }

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Convert image content to JPEG format using ImageMagick
     */
    private function convertToJpeg(string $content): string
    {
        $imagick = new ('Imagick')();
        $imagick->readImageBlob($content);
        $imagick->autoOrient();
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(92);
        $output = $imagick->getImageBlob();
        $imagick->destroy();

        return $output;
    }

    private function authorizeAsset(Request $request, Asset $asset): void
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }

    private function formatAsset(Asset $asset): array
    {
        $tags = $asset->relationLoaded('tags') 
            ? $asset->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->toArray()
            : [];

        $result = [
            'id' => $asset->id,
            'storageBucketId' => $asset->storage_bucket_id,
            'folderId' => $asset->folder_id,
            'filename' => $asset->filename,
            'mimeType' => $asset->mime_type,
            'size' => $asset->size,
            'metadata' => $asset->metadata,
            'tags' => $tags,
            'viewCount' => $asset->view_count ?? 0,
            'downloadCount' => $asset->download_count ?? 0,
            'lastViewedAt' => $asset->last_viewed_at?->toIso8601String(),
            'created' => $asset->created_at->toIso8601String(),
            'updated' => $asset->updated_at->toIso8601String(),
        ];

        $location = $asset->metadata['exif']['location'] ?? null;
        if ($location && isset($location['lat'], $location['lng'])) {
            $result['location'] = [
                'lat' => $location['lat'],
                'lng' => $location['lng'],
            ];
        }

        return $result;
    }

    private function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'bin',
        };
    }

    private function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtolower(substr($size, -1));
        $value = (int) $size;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function shouldAutoAnalyze(Asset $asset): bool
    {
        if ($asset->storage_bucket_id === null) {
            return true;
        }

        $bucket = $asset->storageBucket;
        return $bucket?->auto_analyze ?? true;
    }

    public function analytics(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $days = (int) $request->query('days', 30);
        $days = max(1, min(365, $days));
        $since = now()->subDays($days);

        $events = $asset->analytics()
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'eventType' => $event->event_type,
                'source' => $event->source,
                'shareId' => $event->share_id,
                'ipAddress' => $event->ip_address,
                'country' => $event->country,
                'city' => $event->city,
                'timestamp' => $event->created_at->toIso8601String(),
            ]);

        $summary = $asset->analytics()
            ->where('created_at', '>=', $since)
            ->selectRaw('event_type, source, COUNT(*) as count')
            ->groupBy('event_type', 'source')
            ->get();

        $byType = [];
        $bySource = [];
        foreach ($summary as $row) {
            $byType[$row->event_type] = ($byType[$row->event_type] ?? 0) + $row->count;
            $bySource[$row->source] = ($bySource[$row->source] ?? 0) + $row->count;
        }

        $timeseries = $asset->analytics()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE(created_at) as date, event_type, COUNT(*) as count")
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($group) => [
                'date' => $group->first()->date,
                'views' => $group->firstWhere('event_type', AssetAnalytic::EVENT_VIEW)?->count ?? 0,
                'downloads' => $group->firstWhere('event_type', AssetAnalytic::EVENT_DOWNLOAD)?->count ?? 0,
            ])
            ->values();

        return response()->json([
            'asset' => [
                'id' => $asset->id,
                'filename' => $asset->filename,
                'viewCount' => $asset->view_count,
                'downloadCount' => $asset->download_count,
                'lastViewedAt' => $asset->last_viewed_at?->toIso8601String(),
            ],
            'summary' => [
                'views' => $byType[AssetAnalytic::EVENT_VIEW] ?? 0,
                'downloads' => $byType[AssetAnalytic::EVENT_DOWNLOAD] ?? 0,
                'total' => array_sum($byType),
            ],
            'bySource' => $bySource,
            'timeseries' => $timeseries,
            'events' => $events,
        ]);
    }

    public function analyticsAggregate(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $days = max(1, min(365, $days));
        $since = now()->subDays($days);

        $assetIds = $request->user()->assets()->pluck('id');

        $summary = AssetAnalytic::whereIn('asset_id', $assetIds)
            ->where('created_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        $timeseries = AssetAnalytic::whereIn('asset_id', $assetIds)
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE(created_at) as date, event_type, COUNT(*) as count")
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($group) => [
                'date' => $group->first()->date,
                'views' => $group->firstWhere('event_type', AssetAnalytic::EVENT_VIEW)?->count ?? 0,
                'downloads' => $group->firstWhere('event_type', AssetAnalytic::EVENT_DOWNLOAD)?->count ?? 0,
            ])
            ->values();

        $topAssets = $request->user()->assets()
            ->orderByDesc('view_count')
            ->limit(10)
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'filename' => $asset->filename,
                'viewCount' => $asset->view_count,
                'downloadCount' => $asset->download_count,
            ]);

        return response()->json([
            'summary' => [
                'views' => $summary[AssetAnalytic::EVENT_VIEW] ?? 0,
                'downloads' => $summary[AssetAnalytic::EVENT_DOWNLOAD] ?? 0,
                'total' => array_sum($summary),
            ],
            'timeseries' => $timeseries,
            'topAssets' => $topAssets,
        ]);
    }
}
