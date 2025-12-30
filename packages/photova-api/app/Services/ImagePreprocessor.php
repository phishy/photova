<?php

namespace App\Services;

use Exception;

class ImagePreprocessor
{
    /**
     * @param string $image Base64 image (with or without data URI prefix)
     * @param array $config ['downscale' => bool, 'max_width' => int, 'max_height' => int]
     * @return string Processed base64 image (same format as input)
     */
    public function process(string $image, array $config): string
    {
        if (empty($config) || empty($config['downscale'])) {
            return $image;
        }

        $maxWidth = $config['max_width'] ?? 1024;
        $maxHeight = $config['max_height'] ?? 1024;

        [$hasDataUri, $mimeType, $base64Data] = $this->parseDataUri($image);

        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            throw new Exception('Failed to decode base64 image');
        }

        $gdImage = @imagecreatefromstring($imageData);
        if ($gdImage === false) {
            throw new Exception('Failed to create image from data');
        }

        $origWidth = imagesx($gdImage);
        $origHeight = imagesy($gdImage);

        if ($origWidth <= $maxWidth && $origHeight <= $maxHeight) {
            imagedestroy($gdImage);
            return $image;
        }

        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = (int) round($origWidth * $ratio);
        $newHeight = (int) round($origHeight * $ratio);

        $newImage = $this->createResampledImage($gdImage, $origWidth, $origHeight, $newWidth, $newHeight, $mimeType);

        $outputData = $this->encodeImage($newImage, $mimeType);

        imagedestroy($gdImage);
        imagedestroy($newImage);

        $outputBase64 = base64_encode($outputData);

        return $hasDataUri ? "data:{$mimeType};base64,{$outputBase64}" : $outputBase64;
    }

    private function parseDataUri(string $image): array
    {
        if (!str_starts_with($image, 'data:')) {
            return [false, 'image/png', $image];
        }

        if (preg_match('/^data:([^;]+);base64,(.+)$/', $image, $matches)) {
            return [true, $matches[1], $matches[2]];
        }

        return [true, 'image/png', $image];
    }

    private function createResampledImage($source, int $srcW, int $srcH, int $dstW, int $dstH, string $mimeType)
    {
        $newImage = imagecreatetruecolor($dstW, $dstH);

        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        return $newImage;
    }

    private function encodeImage($image, string $mimeType): string
    {
        ob_start();

        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, null, 85),
            'image/webp' => imagewebp($image, null, 85),
            default => imagepng($image, null, 6),
        };

        return ob_get_clean();
    }
}
