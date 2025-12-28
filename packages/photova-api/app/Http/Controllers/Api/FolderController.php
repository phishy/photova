<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bucket = $request->query('bucket', config('photova.storage.default'));
        $parentId = $request->query('parent_id');

        $query = $request->user()->folders()->where('bucket', $bucket);

        if ($request->has('parent_id')) {
            $query->where('parent_id', $parentId ?: null);
        }

        $folders = $query->orderBy('name')
            ->get()
            ->map(fn ($folder) => $this->formatFolder($folder));

        return response()->json(['folders' => $folders]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|uuid|exists:folders,id',
        ]);

        $bucket = $request->query('bucket', config('photova.storage.default'));

        if ($validated['parent_id'] ?? null) {
            $parent = Folder::find($validated['parent_id']);
            if ($parent->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }
        }

        $folder = $request->user()->folders()->create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'bucket' => $bucket,
        ]);

        return response()->json(['folder' => $this->formatFolder($folder)], 201);
    }

    public function show(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeFolder($request, $folder);

        return response()->json(['folder' => $this->formatFolder($folder)]);
    }

    public function update(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeFolder($request, $folder);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'sometimes|nullable|uuid',
        ]);

        if (isset($validated['parent_id'])) {
            if ($validated['parent_id'] === $folder->id) {
                return response()->json(['error' => 'Cannot move folder into itself'], 400);
            }

            if ($validated['parent_id']) {
                $parent = Folder::find($validated['parent_id']);
                if (!$parent || $parent->user_id !== $request->user()->id) {
                    return response()->json(['error' => 'Invalid parent folder'], 400);
                }

                if ($this->isDescendant($folder, $validated['parent_id'])) {
                    return response()->json(['error' => 'Cannot move folder into its own descendant'], 400);
                }
            }
        }

        $folder->update($validated);

        return response()->json(['folder' => $this->formatFolder($folder->fresh())]);
    }

    public function destroy(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeFolder($request, $folder);

        $folder->delete();

        return response()->json(['message' => 'Folder deleted']);
    }

    public function moveAssets(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeFolder($request, $folder);

        $validated = $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'uuid|exists:assets,id',
        ]);

        $updated = Asset::whereIn('id', $validated['asset_ids'])
            ->where('user_id', $request->user()->id)
            ->update(['folder_id' => $folder->id]);

        return response()->json(['moved' => $updated]);
    }

    public function moveAssetsToRoot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'uuid|exists:assets,id',
        ]);

        $updated = Asset::whereIn('id', $validated['asset_ids'])
            ->where('user_id', $request->user()->id)
            ->update(['folder_id' => null]);

        return response()->json(['moved' => $updated]);
    }

    private function authorizeFolder(Request $request, Folder $folder): void
    {
        if ($folder->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }

    private function isDescendant(Folder $folder, string $targetId): bool
    {
        $children = $folder->children;
        foreach ($children as $child) {
            if ($child->id === $targetId || $this->isDescendant($child, $targetId)) {
                return true;
            }
        }
        return false;
    }

    private function formatFolder(Folder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parentId' => $folder->parent_id,
            'bucket' => $folder->bucket,
            'created' => $folder->created_at->toIso8601String(),
            'updated' => $folder->updated_at->toIso8601String(),
        ];
    }
}
