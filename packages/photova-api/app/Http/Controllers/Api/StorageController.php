<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\UserStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storages = $request->user()
            ->storages()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => $this->formatStorage($s));

        return response()->json(['storages' => $storages]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:s3,r2,gcs,ftp,sftp,local',
            'config' => 'required|array',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->storages()->update(['is_default' => false]);
        }

        $storage = $request->user()->storages()->create([
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'config' => $validated['config'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'storage' => $this->formatStorage($storage),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $storage = $request->user()->storages()->findOrFail($id);

        return response()->json([
            'storage' => $this->formatStorage($storage),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $storage = $request->user()->storages()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'driver' => 'string|in:s3,r2,gcs,ftp,sftp,local',
            'config' => 'array',
            'is_default' => 'boolean',
        ]);

        if (($validated['is_default'] ?? false) && !$storage->is_default) {
            $request->user()->storages()->update(['is_default' => false]);
        }

        if (isset($validated['config'])) {
            $existingConfig = $storage->config ?? [];
            $newConfig = $validated['config'];
            
            foreach ($newConfig as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $existingConfig[$key] = $value;
            }
            $validated['config'] = $existingConfig;
        }

        $storage->update($validated);

        return response()->json([
            'storage' => $this->formatStorage($storage->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $storage = $request->user()->storages()->findOrFail($id);
        $storage->delete();

        return response()->json(null, 204);
    }

    public function drivers(): JsonResponse
    {
        return response()->json([
            'drivers' => UserStorage::getDrivers(),
        ]);
    }

    public function test(Request $request, string $id): JsonResponse
    {
        $storage = $request->user()->storages()->findOrFail($id);

        try {
            $disk = $storage->getDisk();
            $disk->directories('/');

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function scan(Request $request, string $id): JsonResponse
    {
        $storage = $request->user()->storages()->findOrFail($id);

        $validated = $request->validate([
            'path' => 'string|nullable',
            'recursive' => 'boolean',
        ]);

        $path = $validated['path'] ?? '';
        $recursive = $validated['recursive'] ?? false;

        try {
            $disk = $storage->getDisk();
            
            $files = $recursive 
                ? $disk->allFiles($path)
                : $disk->files($path);

            $directories = $recursive
                ? $disk->allDirectories($path)
                : $disk->directories($path);

            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
            
            $items = [];
            
            foreach ($directories as $dir) {
                $items[] = [
                    'type' => 'directory',
                    'path' => $dir,
                    'name' => basename($dir),
                ];
            }

            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $isImage = in_array($ext, $imageExtensions);
                
                $items[] = [
                    'type' => 'file',
                    'path' => $file,
                    'name' => basename($file),
                    'extension' => $ext,
                    'is_image' => $isImage,
                    'size' => $disk->size($file),
                    'last_modified' => $disk->lastModified($file),
                ];
            }

            $storage->update(['last_scanned_at' => now()]);

            return response()->json([
                'path' => $path,
                'items' => $items,
                'total_files' => count($files),
                'total_directories' => count($directories),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Scan failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function import(Request $request, string $id): JsonResponse
    {
        $storage = $request->user()->storages()->findOrFail($id);

        $validated = $request->validate([
            'files' => 'required|array|min:1|max:100',
            'files.*' => 'required|string',
            'folder_id' => 'nullable|uuid|exists:folders,id',
        ]);

        try {
            $disk = $storage->getDisk();
            $imported = [];
            $errors = [];

            foreach ($validated['files'] as $filePath) {
                try {
                    if (!$disk->exists($filePath)) {
                        $errors[] = ['path' => $filePath, 'error' => 'File not found'];
                        continue;
                    }

                    $content = $disk->get($filePath);
                    $filename = basename($filePath);
                    $mimeType = $disk->mimeType($filePath);
                    $size = $disk->size($filePath);

                    $storageKey = 'assets/' . Str::uuid() . '/' . $filename;
                    Storage::disk('local')->put($storageKey, $content);

                    $asset = Asset::create([
                        'user_id' => $request->user()->id,
                        'bucket' => 'assets',
                        'folder_id' => $validated['folder_id'] ?? null,
                        'storage_key' => $storageKey,
                        'filename' => $filename,
                        'mime_type' => $mimeType,
                        'size' => $size,
                        'metadata' => [
                            'imported_from' => $storage->name,
                            'original_path' => $filePath,
                        ],
                    ]);

                    $imported[] = [
                        'path' => $filePath,
                        'asset_id' => $asset->id,
                        'filename' => $filename,
                    ];
                } catch (\Exception $e) {
                    $errors[] = ['path' => $filePath, 'error' => $e->getMessage()];
                }
            }

            return response()->json([
                'imported' => $imported,
                'errors' => $errors,
                'total_imported' => count($imported),
                'total_errors' => count($errors),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    private function formatStorage(UserStorage $storage): array
    {
        return [
            'id' => $storage->id,
            'name' => $storage->name,
            'driver' => $storage->driver,
            'config' => $storage->safe_config,
            'is_default' => $storage->is_default,
            'last_scanned_at' => $storage->last_scanned_at?->toIso8601String(),
            'created_at' => $storage->created_at->toIso8601String(),
            'updated_at' => $storage->updated_at->toIso8601String(),
        ];
    }
}
