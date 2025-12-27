<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = $request->user()->apiKeys()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($key) => $this->formatApiKey($key));

        return response()->json(['keys' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'sometimes|array',
            'expires_at' => 'sometimes|nullable|date|after:now',
        ]);

        $plainKey = $this->generateApiKey();
        $prefix = substr($plainKey, 0, 20);

        $apiKey = $request->user()->apiKeys()->create([
            'name' => $validated['name'],
            'key_prefix' => $prefix,
            'key_hash' => Hash::make($plainKey),
            'key' => $plainKey,
            'scopes' => $validated['scopes'] ?? [],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'key' => $this->formatApiKey($apiKey),
            'plainKey' => $plainKey,
        ], 201);
    }

    public function show(Request $request, ApiKey $key): JsonResponse
    {
        $this->authorizeKey($request, $key);

        return response()->json(['key' => $this->formatApiKey($key)]);
    }

    public function update(Request $request, ApiKey $key): JsonResponse
    {
        $this->authorizeKey($request, $key);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,revoked',
            'scopes' => 'sometimes|array',
        ]);

        $key->update($validated);

        return response()->json(['key' => $this->formatApiKey($key->fresh())]);
    }

    public function destroy(Request $request, ApiKey $key): JsonResponse
    {
        $this->authorizeKey($request, $key);

        $key->delete();

        return response()->json(['message' => 'API key deleted']);
    }

    public function regenerate(Request $request, ApiKey $key): JsonResponse
    {
        $this->authorizeKey($request, $key);

        $plainKey = $this->generateApiKey();
        $prefix = substr($plainKey, 0, 20);

        $key->update([
            'key_prefix' => $prefix,
            'key_hash' => Hash::make($plainKey),
            'key' => $plainKey,
        ]);

        return response()->json([
            'key' => $this->formatApiKey($key->fresh()),
            'plainKey' => $plainKey,
        ]);
    }

    private function authorizeKey(Request $request, ApiKey $key): void
    {
        if ($key->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }

    private function generateApiKey(): string
    {
        return ApiKey::PREFIX . Str::random(32);
    }

    private function formatApiKey(ApiKey $key): array
    {
        $result = [
            'id' => $key->id,
            'name' => $key->name,
            'keyPrefix' => $key->key_prefix,
            'status' => $key->status,
            'scopes' => $key->scopes,
            'lastUsedAt' => $key->last_used_at?->toIso8601String(),
            'expiresAt' => $key->expires_at?->toIso8601String(),
            'created' => $key->created_at->toIso8601String(),
            'updated' => $key->updated_at->toIso8601String(),
        ];

        if ($key->key) {
            $result['key'] = $key->key;
        }

        return $result;
    }
}
