<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ExifService
{
    /**
     * Extract EXIF metadata from image content.
     * 
     * @param string $content Raw image bytes
     * @param string $mimeType Image MIME type
     * @return array Normalized EXIF data
     */
    public function extract(string $content, string $mimeType): array
    {
        $exif = $this->readExif($content, $mimeType);
        
        if (empty($exif)) {
            return [];
        }

        return $this->normalize($exif);
    }

    /**
     * Read raw EXIF data from image content.
     */
    private function readExif(string $content, string $mimeType): array
    {
        // HEIC/HEIF requires Imagick
        if (in_array($mimeType, ['image/heic', 'image/heif'])) {
            return $this->readExifWithImagick($content);
        }

        // JPEG/TIFF can use PHP's built-in exif_read_data
        if (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/tiff'])) {
            return $this->readExifWithPhp($content);
        }

        // PNG/WebP/GIF don't have EXIF
        return [];
    }

    /**
     * Read EXIF using PHP's built-in function.
     */
    private function readExifWithPhp(string $content): array
    {
        if (!function_exists('exif_read_data')) {
            Log::warning('ExifService: exif extension not available');
            return [];
        }

        // exif_read_data needs a file path or stream
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        try {
            $exif = @exif_read_data($stream, 'ANY_TAG', true);
            fclose($stream);
            return $exif ?: [];
        } catch (\Exception $e) {
            fclose($stream);
            Log::debug('ExifService: Failed to read EXIF: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Read EXIF using Imagick (for HEIC/HEIF).
     */
    private function readExifWithImagick(string $content): array
    {
        if (!extension_loaded('imagick')) {
            Log::warning('ExifService: imagick extension not available for HEIC');
            return [];
        }

        try {
            $imagick = new ('Imagick')();
            $imagick->readImageBlob($content);
            
            $exif = [];
            $properties = $imagick->getImageProperties('exif:*');
            
            foreach ($properties as $key => $value) {
                // Convert "exif:DateTimeOriginal" to "DateTimeOriginal"
                $cleanKey = str_replace('exif:', '', $key);
                $exif[$cleanKey] = $value;
            }

            $imagick->destroy();
            return $exif;
        } catch (\Exception $e) {
            Log::debug('ExifService: Failed to read EXIF with Imagick: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Normalize raw EXIF data into a clean structure.
     */
    private function normalize(array $exif): array
    {
        $result = [];

        // Location (GPS)
        $location = $this->extractLocation($exif);
        if ($location) {
            $result['location'] = $location;
        }

        // Date/Time
        $datetime = $this->extractDateTime($exif);
        if ($datetime) {
            $result['datetime'] = $datetime;
        }

        // Camera info
        $camera = $this->extractCamera($exif);
        if ($camera) {
            $result['camera'] = $camera;
        }

        // Capture settings
        $settings = $this->extractSettings($exif);
        if ($settings) {
            $result['settings'] = $settings;
        }

        // Image dimensions from EXIF
        $dimensions = $this->extractDimensions($exif);
        if ($dimensions) {
            $result['dimensions'] = $dimensions;
        }

        return $result;
    }

    /**
     * Extract GPS location and convert to decimal degrees.
     */
    private function extractLocation(array $exif): ?array
    {
        // Check both flat structure (Imagick) and nested structure (PHP exif)
        $gps = $exif['GPS'] ?? $exif;

        $lat = $this->getGpsCoordinate($gps, 'GPSLatitude', 'GPSLatitudeRef');
        $lng = $this->getGpsCoordinate($gps, 'GPSLongitude', 'GPSLongitudeRef');

        if ($lat === null || $lng === null) {
            return null;
        }

        $location = [
            'lat' => $lat,
            'lng' => $lng,
        ];

        // Altitude
        $altitude = $this->getGpsAltitude($gps);
        if ($altitude !== null) {
            $location['altitude'] = $altitude;
        }

        // Direction/Bearing
        if (isset($gps['GPSImgDirection'])) {
            $location['direction'] = $this->parseRational($gps['GPSImgDirection']);
        }

        // Speed
        if (isset($gps['GPSSpeed'])) {
            $speed = $this->parseRational($gps['GPSSpeed']);
            $speedRef = $gps['GPSSpeedRef'] ?? 'K'; // K=km/h, M=mph, N=knots
            $location['speed'] = [
                'value' => $speed,
                'unit' => match($speedRef) {
                    'M' => 'mph',
                    'N' => 'knots',
                    default => 'km/h',
                },
            ];
        }

        return $location;
    }

    /**
     * Parse GPS coordinate from DMS (degrees/minutes/seconds) to decimal.
     */
    private function getGpsCoordinate(array $gps, string $coordKey, string $refKey): ?float
    {
        if (!isset($gps[$coordKey])) {
            return null;
        }

        $coord = $gps[$coordKey];
        $ref = $gps[$refKey] ?? null;

        // Handle string format from Imagick: "37/1, 46/1, 29.4/1" or "37, 46, 29.4"
        if (is_string($coord)) {
            $parts = array_map('trim', explode(',', $coord));
            if (count($parts) >= 3) {
                $degrees = $this->parseRational($parts[0]);
                $minutes = $this->parseRational($parts[1]);
                $seconds = $this->parseRational($parts[2]);
            } else {
                return null;
            }
        }
        // Handle array format from PHP exif: [[37,1], [46,1], [294,10]]
        elseif (is_array($coord) && count($coord) >= 3) {
            $degrees = $this->parseRational($coord[0]);
            $minutes = $this->parseRational($coord[1]);
            $seconds = $this->parseRational($coord[2]);
        } else {
            return null;
        }

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        // Convert DMS to decimal degrees
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // Apply hemisphere reference (S/W = negative)
        if ($ref === 'S' || $ref === 'W') {
            $decimal = -$decimal;
        }

        return round($decimal, 8);
    }

    /**
     * Parse GPS altitude.
     */
    private function getGpsAltitude(array $gps): ?float
    {
        if (!isset($gps['GPSAltitude'])) {
            return null;
        }

        $altitude = $this->parseRational($gps['GPSAltitude']);
        if ($altitude === null) {
            return null;
        }

        // GPSAltitudeRef: 0 = above sea level, 1 = below sea level
        $ref = $gps['GPSAltitudeRef'] ?? 0;
        if ($ref == 1 || $ref === "\x01") {
            $altitude = -$altitude;
        }

        return round($altitude, 2);
    }

    /**
     * Extract date/time information.
     */
    private function extractDateTime(array $exif): ?array
    {
        $result = [];

        // Get the EXIF section or use flat structure
        $exifSection = $exif['EXIF'] ?? $exif;
        $ifd0 = $exif['IFD0'] ?? $exif;

        // Original capture time (most reliable)
        $original = $exifSection['DateTimeOriginal'] ?? $exif['DateTimeOriginal'] ?? null;
        if ($original) {
            $result['original'] = $this->parseExifDateTime($original);
        }

        // Digitized time
        $digitized = $exifSection['DateTimeDigitized'] ?? $exif['DateTimeDigitized'] ?? null;
        if ($digitized && $digitized !== $original) {
            $result['digitized'] = $this->parseExifDateTime($digitized);
        }

        // Modified time
        $modified = $ifd0['DateTime'] ?? $exif['DateTime'] ?? null;
        if ($modified && $modified !== $original) {
            $result['modified'] = $this->parseExifDateTime($modified);
        }

        // Timezone offset if available
        $offset = $exifSection['OffsetTimeOriginal'] ?? $exif['OffsetTimeOriginal'] ?? null;
        if ($offset) {
            $result['timezone'] = $offset;
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Parse EXIF datetime string to ISO 8601.
     */
    private function parseExifDateTime(string $datetime): ?string
    {
        // EXIF format: "2024:12:25 14:30:00"
        $datetime = trim($datetime);
        if (empty($datetime) || $datetime === '0000:00:00 00:00:00') {
            return null;
        }

        // Replace EXIF date separator with standard
        $datetime = str_replace(':', '-', substr($datetime, 0, 10)) . substr($datetime, 10);

        try {
            $dt = new \DateTime($datetime);
            return $dt->format('c'); // ISO 8601
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract camera/device information.
     */
    private function extractCamera(array $exif): ?array
    {
        $ifd0 = $exif['IFD0'] ?? $exif;
        $exifSection = $exif['EXIF'] ?? $exif;

        $result = [];

        // Make/Brand
        $make = $ifd0['Make'] ?? $exif['Make'] ?? null;
        if ($make) {
            $result['make'] = trim($make);
        }

        // Model
        $model = $ifd0['Model'] ?? $exif['Model'] ?? null;
        if ($model) {
            $result['model'] = trim($model);
        }

        // Software
        $software = $ifd0['Software'] ?? $exif['Software'] ?? null;
        if ($software) {
            $result['software'] = trim($software);
        }

        // Lens info
        $lens = $exifSection['LensModel'] ?? $exif['LensModel'] ?? 
                $exifSection['LensInfo'] ?? $exif['LensInfo'] ?? null;
        if ($lens) {
            $result['lens'] = is_array($lens) ? implode(' ', array_map(fn($v) => $this->parseRational($v), $lens)) : trim($lens);
        }

        // Serial number
        $serial = $exifSection['BodySerialNumber'] ?? $exif['BodySerialNumber'] ?? null;
        if ($serial) {
            $result['serial'] = trim($serial);
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Extract capture settings (aperture, shutter, ISO, etc.).
     */
    private function extractSettings(array $exif): ?array
    {
        $exifSection = $exif['EXIF'] ?? $exif;
        $result = [];

        // Aperture (F-number)
        $fnumber = $exifSection['FNumber'] ?? $exif['FNumber'] ?? 
                   $exifSection['COMPUTED']['ApertureFNumber'] ?? null;
        if ($fnumber) {
            $result['aperture'] = $this->parseRational($fnumber);
        }

        // Shutter speed / Exposure time
        $exposure = $exifSection['ExposureTime'] ?? $exif['ExposureTime'] ?? null;
        if ($exposure) {
            $result['shutterSpeed'] = $this->formatShutterSpeed($exposure);
        }

        // ISO
        $iso = $exifSection['ISOSpeedRatings'] ?? $exif['ISOSpeedRatings'] ?? 
               $exifSection['PhotographicSensitivity'] ?? $exif['PhotographicSensitivity'] ?? null;
        if ($iso) {
            $result['iso'] = is_array($iso) ? $iso[0] : (int) $iso;
        }

        // Focal length
        $focal = $exifSection['FocalLength'] ?? $exif['FocalLength'] ?? null;
        if ($focal) {
            $result['focalLength'] = $this->parseRational($focal);
        }

        // 35mm equivalent focal length
        $focal35 = $exifSection['FocalLengthIn35mmFilm'] ?? $exif['FocalLengthIn35mmFilm'] ?? null;
        if ($focal35) {
            $result['focalLength35mm'] = (int) $focal35;
        }

        // Exposure program
        $program = $exifSection['ExposureProgram'] ?? $exif['ExposureProgram'] ?? null;
        if ($program !== null) {
            $result['exposureProgram'] = $this->getExposureProgramName((int) $program);
        }

        // Metering mode
        $metering = $exifSection['MeteringMode'] ?? $exif['MeteringMode'] ?? null;
        if ($metering !== null) {
            $result['meteringMode'] = $this->getMeteringModeName((int) $metering);
        }

        // Flash
        $flash = $exifSection['Flash'] ?? $exif['Flash'] ?? null;
        if ($flash !== null) {
            $result['flash'] = $this->getFlashInfo((int) $flash);
        }

        // White balance
        $wb = $exifSection['WhiteBalance'] ?? $exif['WhiteBalance'] ?? null;
        if ($wb !== null) {
            $result['whiteBalance'] = ((int) $wb) === 0 ? 'auto' : 'manual';
        }

        // Exposure compensation
        $compensation = $exifSection['ExposureBiasValue'] ?? $exif['ExposureBiasValue'] ?? null;
        if ($compensation) {
            $result['exposureCompensation'] = $this->parseRational($compensation);
        }

        // Scene type
        $scene = $exifSection['SceneCaptureType'] ?? $exif['SceneCaptureType'] ?? null;
        if ($scene !== null) {
            $result['sceneType'] = $this->getSceneTypeName((int) $scene);
        }

        // Orientation
        $orientation = $exif['IFD0']['Orientation'] ?? $exif['Orientation'] ?? null;
        if ($orientation !== null) {
            $result['orientation'] = (int) $orientation;
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Extract image dimensions from EXIF.
     */
    private function extractDimensions(array $exif): ?array
    {
        $exifSection = $exif['EXIF'] ?? $exif;
        $computed = $exif['COMPUTED'] ?? [];

        $width = $exifSection['ExifImageWidth'] ?? $exif['ExifImageWidth'] ?? 
                 $exifSection['PixelXDimension'] ?? $computed['Width'] ?? null;
        $height = $exifSection['ExifImageLength'] ?? $exif['ExifImageLength'] ?? 
                  $exifSection['PixelYDimension'] ?? $computed['Height'] ?? null;

        if ($width && $height) {
            return [
                'width' => (int) $width,
                'height' => (int) $height,
            ];
        }

        return null;
    }

    /**
     * Parse a rational number (fraction) from EXIF.
     * Can be "3/2", [3, 2], "1.5", or just a number.
     */
    private function parseRational(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value) && count($value) === 2) {
            $num = (float) $value[0];
            $den = (float) $value[1];
            return $den != 0 ? $num / $den : null;
        }

        if (is_string($value) && str_contains($value, '/')) {
            $parts = explode('/', $value);
            if (count($parts) === 2) {
                $num = (float) trim($parts[0]);
                $den = (float) trim($parts[1]);
                return $den != 0 ? $num / $den : null;
            }
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * Format shutter speed for display.
     */
    private function formatShutterSpeed(mixed $exposure): string
    {
        $value = $this->parseRational($exposure);
        if ($value === null) {
            return is_string($exposure) ? $exposure : '?';
        }

        // If >= 1 second, show as decimal
        if ($value >= 1) {
            return round($value, 1) . 's';
        }

        // Otherwise show as fraction
        $denominator = round(1 / $value);
        return "1/{$denominator}s";
    }

    /**
     * Get exposure program name.
     */
    private function getExposureProgramName(int $program): string
    {
        return match ($program) {
            0 => 'not defined',
            1 => 'manual',
            2 => 'auto',
            3 => 'aperture priority',
            4 => 'shutter priority',
            5 => 'creative',
            6 => 'action',
            7 => 'portrait',
            8 => 'landscape',
            default => 'unknown',
        };
    }

    /**
     * Get metering mode name.
     */
    private function getMeteringModeName(int $mode): string
    {
        return match ($mode) {
            0 => 'unknown',
            1 => 'average',
            2 => 'center-weighted',
            3 => 'spot',
            4 => 'multi-spot',
            5 => 'pattern',
            6 => 'partial',
            default => 'other',
        };
    }

    /**
     * Get flash info from flash value.
     */
    private function getFlashInfo(int $flash): array
    {
        return [
            'fired' => (bool) ($flash & 0x01),
            'mode' => match (($flash >> 3) & 0x03) {
                1 => 'on',
                2 => 'off',
                3 => 'auto',
                default => 'unknown',
            },
            'redEyeReduction' => (bool) ($flash & 0x40),
        ];
    }

    /**
     * Get scene capture type name.
     */
    private function getSceneTypeName(int $scene): string
    {
        return match ($scene) {
            0 => 'standard',
            1 => 'landscape',
            2 => 'portrait',
            3 => 'night',
            default => 'other',
        };
    }
}
