<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = $request->user()->tags()
            ->withCount('assets')
            ->orderBy('name')
            ->get()
            ->map(fn ($tag) => $this->formatTag($tag));

        return response()->json(['tags' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $existing = $request->user()->tags()->where('name', $validated['name'])->first();
        if ($existing) {
            return response()->json(['error' => 'Tag already exists'], 409);
        }

        $tag = $request->user()->tags()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6e7681',
        ]);

        return response()->json(['tag' => $this->formatTag($tag)], 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $this->authorizeTag($request, $tag);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if (isset($validated['name'])) {
            $existing = $request->user()->tags()
                ->where('name', $validated['name'])
                ->where('id', '!=', $tag->id)
                ->first();
            if ($existing) {
                return response()->json(['error' => 'Tag name already exists'], 409);
            }
        }

        $tag->update($validated);

        return response()->json(['tag' => $this->formatTag($tag->fresh())]);
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        $this->authorizeTag($request, $tag);

        $tag->delete();

        return response()->json(['message' => 'Tag deleted']);
    }

    public function attachToAsset(Request $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'uuid|exists:tags,id',
        ]);

        $userTagIds = $request->user()->tags()->whereIn('id', $validated['tag_ids'])->pluck('id');
        $asset->tags()->syncWithoutDetaching($userTagIds);

        return response()->json(['message' => 'Tags attached']);
    }

    public function detachFromAsset(Request $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'uuid|exists:tags,id',
        ]);

        $asset->tags()->detach($validated['tag_ids']);

        return response()->json(['message' => 'Tags detached']);
    }

    public function setAssetTags(Request $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'tag_ids' => 'present|array',
            'tag_ids.*' => 'uuid|exists:tags,id',
        ]);

        $userTagIds = $request->user()->tags()->whereIn('id', $validated['tag_ids'])->pluck('id');
        $asset->tags()->sync($userTagIds);

        return response()->json(['message' => 'Tags updated']);
    }

    private function authorizeTag(Request $request, Tag $tag): void
    {
        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }

    private function formatTag(Tag $tag): array
    {
        return [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
            'assetCount' => $tag->assets_count ?? $tag->assets()->count(),
            'created' => $tag->created_at->toIso8601String(),
            'updated' => $tag->updated_at->toIso8601String(),
        ];
    }
}
