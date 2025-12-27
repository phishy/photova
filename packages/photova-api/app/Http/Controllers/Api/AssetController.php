<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $assets = $request->user()->assets()
            ->where('bucket', $bucket)
            ->orderByDesc('created_at')
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
            return response()->json(['error' => 'No file or image provided'], 400);
        }

        $storageKey = Str::uuid() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $storagePath = ($bucketConfig['path'] ?? 'assets') . '/' . $storageKey;

        $disk = $bucketConfig['disk'] ?? 'local';
        Storage::disk($disk)->put($storagePath, $content);

        $asset = $request->user()->assets()->create([
            'bucket' => $bucket,
            'storage_key' => $storageKey,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
            'metadata' => $request->input('metadata', []),
        ]);

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
        return [
            'id' => $asset->id,
            'bucket' => $asset->bucket,
            'filename' => $asset->filename,
            'mimeType' => $asset->mime_type,
            'size' => $asset->size,
            'metadata' => $asset->metadata,
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
}
