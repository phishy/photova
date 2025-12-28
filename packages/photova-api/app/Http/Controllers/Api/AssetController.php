<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeAsset;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bucket = $request->query('bucket', config('photova.storage.default'));
        $folderId = $request->query('folder_id');
        $search = $request->query('search');
        $tagIds = $request->query('tags');

        $query = $request->user()->assets()
            ->with('tags')
            ->where('bucket', $bucket);

        // When searching, ignore folder context (flatten results)
        if (!$search) {
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
        $bucket = $request->query('bucket', config('photova.storage.default'));
        $bucketConfig = config("photova.storage.buckets.{$bucket}");

        if (!$bucketConfig) {
            return response()->json(['error' => 'Invalid bucket'], 400);
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

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();
            $content = $file->get();
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
            $size = strlen($content);
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

        $storageKey = Str::uuid() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $storagePath = ($bucketConfig['path'] ?? 'assets') . '/' . $storageKey;

        $disk = $bucketConfig['disk'] ?? 'local';
        Storage::disk($disk)->put($storagePath, $content);

        $folderId = $request->input('folder_id');
        if ($folderId) {
            $folder = $request->user()->folders()->find($folderId);
            if (!$folder) {
                $folderId = null;
            }
        }

        $asset = $request->user()->assets()->create([
            'bucket' => $bucket,
            'folder_id' => $folderId,
            'storage_key' => $storageKey,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
            'metadata' => $request->input('metadata', []),
        ]);

        if (str_starts_with($mimeType, 'image/')) {
            AnalyzeAsset::dispatch($asset);
        }

        return response()->json(['asset' => $this->formatAsset($asset)], 201);
    }

    public function show(Request $request, Asset $asset): JsonResponse|StreamedResponse
    {
        $this->authorizeAsset($request, $asset);

        if ($request->boolean('download')) {
            return $this->download($asset);
        }

        return response()->json(['asset' => $this->formatAsset($asset)]);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $bucketConfig = config("photova.storage.buckets.{$asset->bucket}");
        $disk = $bucketConfig['disk'] ?? 'local';

        if ($request->has('image')) {
            $imageData = $request->input('image');

            if (preg_match('/^data:([^;]+);base64,(.+)$/', $imageData, $matches)) {
                $mimeType = $matches[1];
                $content = base64_decode($matches[2]);
            } else {
                $content = base64_decode($imageData);
                $mimeType = $asset->mime_type;
            }

            $storagePath = ($bucketConfig['path'] ?? 'assets') . '/' . $asset->storage_key;
            Storage::disk($disk)->put($storagePath, $content);

            $asset->update([
                'mime_type' => $mimeType,
                'size' => strlen($content),
            ]);

            if (str_starts_with($mimeType, 'image/')) {
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

        $bucketConfig = config("photova.storage.buckets.{$asset->bucket}");
        $disk = $bucketConfig['disk'] ?? 'local';
        $storagePath = ($bucketConfig['path'] ?? 'assets') . '/' . $asset->storage_key;

        Storage::disk($disk)->delete($storagePath);
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

    public function publicDownload(Asset $asset): StreamedResponse
    {
        return $this->download($asset);
    }

    private function download(Asset $asset): StreamedResponse
    {
        $bucketConfig = config("photova.storage.buckets.{$asset->bucket}");
        $disk = $bucketConfig['disk'] ?? 'local';
        $storagePath = ($bucketConfig['path'] ?? 'assets') . '/' . $asset->storage_key;

        return Storage::disk($disk)->download($storagePath, $asset->filename, [
            'Content-Type' => $asset->mime_type,
        ]);
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

        return [
            'id' => $asset->id,
            'bucket' => $asset->bucket,
            'folderId' => $asset->folder_id,
            'filename' => $asset->filename,
            'mimeType' => $asset->mime_type,
            'size' => $asset->size,
            'metadata' => $asset->metadata,
            'tags' => $tags,
            'created' => $asset->created_at->toIso8601String(),
            'updated' => $asset->updated_at->toIso8601String(),
        ];
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
}
