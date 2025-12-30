<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Share;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ShareController extends Controller
{
    public function __construct(
        private StorageService $storage
    ) {}

    /**
     * List all shares for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $shares = $request->user()->shares()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($share) => $this->formatShare($share));

        return response()->json(['shares' => $shares]);
    }

    /**
     * Create a new share
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'uuid',
            'name' => 'nullable|string|max:255',
            'expires_in' => 'nullable|string|in:never,24h,7d,30d',
            'password' => 'nullable|string|min:4',
            'allow_download' => 'boolean',
            'allow_zip' => 'boolean',
        ]);

        // Verify all assets belong to the user
        $assetIds = $validated['asset_ids'];
        $validAssets = $request->user()->assets()
            ->whereIn('id', $assetIds)
            ->pluck('id')
            ->toArray();

        if (count($validAssets) !== count($assetIds)) {
            return response()->json(['error' => 'One or more assets not found'], 404);
        }

        // Calculate expiration
        $expiresAt = match ($validated['expires_in'] ?? 'never') {
            '24h' => now()->addHours(24),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            default => null,
        };

        $share = $request->user()->shares()->create([
            'name' => $validated['name'] ?? null,
            'asset_ids' => $validAssets,
            'expires_at' => $expiresAt,
            'password' => $validated['password'] ?? null,
            'allow_download' => $validated['allow_download'] ?? true,
            'allow_zip' => $validated['allow_zip'] ?? true,
        ]);

        return response()->json([
            'share' => $this->formatShare($share),
            'url' => $share->getUrl(),
        ], 201);
    }

    /**
     * Get share details
     */
    public function show(Request $request, Share $share): JsonResponse
    {
        if ($share->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['share' => $this->formatShare($share)]);
    }

    /**
     * Update share settings
     */
    public function update(Request $request, Share $share): JsonResponse
    {
        if ($share->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'expires_in' => 'nullable|string|in:never,24h,7d,30d',
            'password' => 'nullable|string|min:4',
            'remove_password' => 'boolean',
            'allow_download' => 'boolean',
            'allow_zip' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $share->name = $validated['name'];
        }

        if (isset($validated['expires_in'])) {
            $share->expires_at = match ($validated['expires_in']) {
                '24h' => now()->addHours(24),
                '7d' => now()->addDays(7),
                '30d' => now()->addDays(30),
                default => null,
            };
        }

        if (isset($validated['password'])) {
            $share->password = $validated['password'];
        }

        if ($validated['remove_password'] ?? false) {
            $share->password = null;
        }

        if (isset($validated['allow_download'])) {
            $share->allow_download = $validated['allow_download'];
        }

        if (isset($validated['allow_zip'])) {
            $share->allow_zip = $validated['allow_zip'];
        }

        $share->save();

        return response()->json(['share' => $this->formatShare($share)]);
    }

    /**
     * Delete a share
     */
    public function destroy(Request $request, Share $share): JsonResponse
    {
        if ($share->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $share->delete();

        return response()->json(['message' => 'Share deleted']);
    }

    /**
     * Public: Get share data for viewing
     */
    public function publicShow(Request $request, string $slug): JsonResponse
    {
        $share = Share::where('slug', $slug)->first();

        if (!$share) {
            return response()->json(['error' => 'Share not found'], 404);
        }

        if ($share->isExpired()) {
            return response()->json(['error' => 'Share has expired'], 410);
        }

        // Check password if protected
        if ($share->isPasswordProtected()) {
            $password = $request->input('password') ?? $request->header('X-Share-Password');
            
            if (!$password) {
                return response()->json([
                    'error' => 'Password required',
                    'password_required' => true,
                ], 401);
            }

            if (!$share->checkPassword($password)) {
                return response()->json(['error' => 'Invalid password'], 401);
            }

            // Store password in session for subsequent thumbnail/download requests
            session(['share_password_' . $share->id => $password]);
        }

        // Increment view count
        $share->incrementViewCount();

        // Get assets
        $assets = $share->getAssets()->map(fn ($asset) => [
            'id' => $asset->id,
            'filename' => $asset->filename,
            'mimeType' => $asset->mime_type,
            'size' => $asset->size,
        ]);

        return response()->json([
            'share' => [
                'name' => $share->name,
                'assetCount' => count($share->asset_ids),
                'allowDownload' => $share->allow_download,
                'allowZip' => $share->allow_zip,
                'expiresAt' => $share->expires_at?->toIso8601String(),
            ],
            'assets' => $assets,
        ]);
    }

    /**
     * Public: Get asset thumbnail for share
     */
    public function publicThumbnail(Request $request, string $slug, string $assetId)
    {
        $share = Share::where('slug', $slug)->first();

        if (!$share || $share->isExpired()) {
            abort(404);
        }

        if (!in_array($assetId, $share->asset_ids)) {
            abort(404);
        }

        // Password check via session or header
        if ($share->isPasswordProtected()) {
            $password = $request->header('X-Share-Password') ?? session('share_password_' . $share->id);
            if (!$password || !$share->checkPassword($password)) {
                abort(401);
            }
        }

        $asset = Asset::where('id', $assetId)
            ->where('user_id', $share->user_id)
            ->first();

        if (!$asset || !str_starts_with($asset->mime_type, 'image/')) {
            abort(404);
        }

        $width = (int) $request->query('w', 400);
        $height = (int) $request->query('h', 400);
        $width = max(16, min(1200, $width));
        $height = max(16, min(1200, $height));

        $content = $this->storage->retrieve($asset);
        if (!$content) {
            abort(404);
        }

        $resized = $this->resizeImage($content, $width, $height, $asset->mime_type);

        if ($resized === null) {
            // Format not supported by GD and Imagick not available
            abort(415, 'Image format not supported for thumbnail generation');
        }

        return response($resized, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => strlen($resized),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Public: Download single asset from share
     */
    public function publicDownload(Request $request, string $slug, string $assetId): StreamedResponse
    {
        $share = Share::where('slug', $slug)->first();

        if (!$share || $share->isExpired()) {
            abort(404);
        }

        if (!$share->allow_download) {
            abort(403, 'Downloads are not allowed for this share');
        }

        if (!in_array($assetId, $share->asset_ids)) {
            abort(404);
        }

        if ($share->isPasswordProtected()) {
            $password = $request->header('X-Share-Password') ?? session('share_password_' . $share->id);
            if (!$password || !$share->checkPassword($password)) {
                abort(401);
            }
        }

        $asset = Asset::where('id', $assetId)
            ->where('user_id', $share->user_id)
            ->first();

        if (!$asset) {
            abort(404);
        }

        $content = $this->storage->retrieve($asset);
        if (!$content) {
            abort(404);
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $asset->filename, [
            'Content-Type' => $asset->mime_type,
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Public: Download all assets as ZIP
     */
    public function publicZip(Request $request, string $slug): StreamedResponse
    {
        $share = Share::where('slug', $slug)->first();

        if (!$share || $share->isExpired()) {
            abort(404);
        }

        if (!$share->allow_zip) {
            abort(403, 'ZIP downloads are not allowed for this share');
        }

        if ($share->isPasswordProtected()) {
            $password = $request->header('X-Share-Password') ?? session('share_password_' . $share->id);
            if (!$password || !$share->checkPassword($password)) {
                abort(401);
            }
        }

        $assets = $share->getAssets();

        if ($assets->isEmpty()) {
            abort(404, 'No assets found');
        }

        $zipName = ($share->name ?? 'shared-files') . '.zip';

        return $this->streamZip($assets, $zipName);
    }

    /**
     * Authenticated: Download selected assets as ZIP
     */
    public function downloadZip(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'uuid',
        ]);

        $assets = $request->user()->assets()
            ->whereIn('id', $validated['asset_ids'])
            ->get();

        if ($assets->isEmpty()) {
            abort(404, 'No assets found');
        }

        $zipName = 'download-' . now()->format('Y-m-d-His') . '.zip';

        return $this->streamZip($assets, $zipName);
    }

    private function streamZip($assets, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($assets) {
            $tempFile = tempnam(sys_get_temp_dir(), 'zip');
            $zip = new ZipArchive();
            
            if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create ZIP file');
            }

            $usedNames = [];
            foreach ($assets as $asset) {
                $content = $this->storage->retrieve($asset);
                if ($content) {
                    // Handle duplicate filenames
                    $name = $asset->filename;
                    $counter = 1;
                    while (in_array($name, $usedNames)) {
                        $extension = pathinfo($asset->filename, PATHINFO_EXTENSION);
                        $basename = pathinfo($asset->filename, PATHINFO_FILENAME);
                        $name = $basename . '-' . $counter . '.' . $extension;
                        $counter++;
                    }
                    $usedNames[] = $name;
                    
                    $zip->addFromString($name, $content);
                }
            }

            $zip->close();

            readfile($tempFile);
            unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function formatShare(Share $share): array
    {
        return [
            'id' => $share->id,
            'slug' => $share->slug,
            'name' => $share->name,
            'url' => $share->getUrl(),
            'assetIds' => $share->asset_ids,
            'assetCount' => count($share->asset_ids),
            'expiresAt' => $share->expires_at?->toIso8601String(),
            'isExpired' => $share->isExpired(),
            'hasPassword' => $share->isPasswordProtected(),
            'allowDownload' => $share->allow_download,
            'allowZip' => $share->allow_zip,
            'viewCount' => $share->view_count,
            'created' => $share->created_at->toIso8601String(),
        ];
    }

    private function resizeImage(string $content, int $width, int $height, string $mimeType = ''): ?string
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
            // GD doesn't support this format and Imagick not available
            return null;
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
}
